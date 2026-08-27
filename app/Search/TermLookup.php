<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Recherche d'un terme exact, mot precedent, mot suivant.
 *
 * Budget : au plus 2 requetes SQLite par appel a find() (lookupRow() + neighbours()
 * fusionnee en UNE requete UNION ALL), toutes avec LIMIT 1 par cote, toutes servies par
 * l'index UNIQUE sur normalized (sqlite_autoindex_terms_1) -- aucun scan, voir
 * reports/query-plans/de-import-baseline.md. Une forme d'entree invalide n'engendre
 * aucune requete : find() renvoie null avant toute ouverture de curseur.
 *
 * ADAPTATION ALLEMANDE (voir schema.sql) : is_ods8/is_ods9 (double lexique francais)
 * fusionnes en un unique is_admitted -- $isOds8 et $isOds9 refletent tous deux la MEME
 * valeur is_admitted, jamais deux sources distinctes. Champ conserve sous ces deux noms
 * (pas renomme) uniquement pour rester compatible sans modification avec app/View/word.php
 * et app/View/play.php (hors perimetre de cet agent, CLAUDE.md), qui lisent
 * ->isOds8/->isOds9 pour deux pastilles distinctes -- CONNU ET SIGNALE EXPLICITEMENT
 * (rapport AFTER) : ces pastilles afficheront le texte "ODS8"/"ODS9", incorrect pour
 * l'allemand (aucune liste "ODS" allemande n'existe), tant qu'un passage frontend/
 * microcopy dedie n'aura pas adapte ce gabarit -- non fait ici, hors perimetre de
 * data-engine. pos/pos_secondary/gender (D-018 francais) restent presents sur TermPage
 * (meme raison de compatibilite) mais TOUJOURS nuls : aucune colonne pos/pos_secondary/
 * gender dans schema.sql allemand (aucune source de nature grammaticale retenue cette
 * passe) -- app/View/word.php gere deja nativement ce cas (verifie : ~12,3 % des termes
 * francais sont deja dans cet etat aujourd'hui, section masquee si $page->pos === null).
 */
final class TermLookup
{
    /**
     * @param array<string, int> $tileScores
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly array $tileScores,
    ) {
    }

    /**
     * Normalise l'entree brute et construit la fiche mot correspondante.
     *
     * Renvoie null si la forme normalisee n'est pas un terme valide (2 a 15 lettres
     * A-ZÄÖÜ) : c'est une erreur de saisie, pas un statut de terme (le modele a trois
     * statuts reste ferme), et elle n'engendre aucune requete SQLite. Au routeur de
     * traduire ce null en reponse 404.
     */
    public function find(string $rawInput): ?TermPage
    {
        $normalized = Normalizer::normalize($rawInput);

        if (!Normalizer::isValid($normalized)) {
            return null;
        }

        $row = $this->lookupRow($normalized);
        $found = $row !== null;

        // Un seul indicateur reel (is_admitted) -- voir docblock de classe.
        $isAdmitted = $found && (int) $row['is_admitted'] === 1;

        $status = match (true) {
            $isAdmitted => TermPage::STATUS_ADMITTED,
            // Jamais atteint dans cette passe (aucune source "reel mais non admis" pour
            // l'allemand -- voir data/raw/PROVENANCE.md) : toute ligne presente en base a
            // is_admitted = 1 par construction (source unique). Conserve pour le modele a
            // trois statuts ferme (CLAUDE.md), pas une branche morte a supprimer.
            $found => TermPage::STATUS_FRENCH_NOT_ADMITTED,
            default => TermPage::STATUS_UNKNOWN,
        };

        $letters = $this->tiles($normalized);
        $score = $found ? (int) $row['score'] : Normalizer::score($normalized, $this->tileScores);
        // mb_strlen (pas strlen) : normalized peut contenir Ä/Ö/Ü, codees sur deux octets
        // UTF-8 -- strlen() compterait des OCTETS, pas des lettres.
        $length = $found ? (int) $row['length'] : mb_strlen($normalized);
        [$previousWord, $nextWord] = $this->neighbours($normalized);

        return new TermPage(
            normalized: $normalized,
            // mb_strtolower (pas strtolower) : $normalized peut contenir Ä/Ö/Ü --
            // strtolower() (ASCII uniquement) les laisserait en majuscule dans le slug.
            slug: mb_strtolower($normalized, 'UTF-8'),
            found: $found,
            status: $status,
            score: $score,
            length: $length,
            isOds8: $isAdmitted,
            isOds9: $isAdmitted,
            letters: $letters,
            previousWord: $previousWord,
            nextWord: $nextWord,
            // Aucune colonne pos/pos_secondary/gender en base allemande cette passe --
            // toujours nul, voir docblock de classe.
            pos: null,
            posSecondary: null,
            gender: null,
        );
    }

    /**
     * @return array{display_term: string, score: string|int, length: string|int, is_admitted: string|int}|null
     */
    private function lookupRow(string $normalized): ?array
    {
        $statement = $this->connection->pdo()->prepare(
            'SELECT display_term, score, length, is_admitted FROM terms WHERE normalized = ? LIMIT 1'
        );
        $statement->execute([$normalized]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Mot precedent et suivant en UNE SEULE requete (deux SELECT bornes chacun par leur
     * propre ORDER BY/LIMIT, combines par UNION ALL). Chaque cote reste servi par l'index
     * unique sur normalized (verifie par EXPLAIN QUERY PLAN, reports/query-plans/
     * de-import-baseline.md) -- aucune ligne ne peut jamais satisfaire les deux conditions
     * a la fois (< et > au meme normalized), donc aucune ambiguite a departager entre les
     * 0, 1 ou 2 lignes renvoyees : normalized < $normalized est toujours le precedent,
     * normalized > $normalized est toujours le suivant.
     *
     * @return array{0: ?string, 1: ?string} [previousWord, nextWord]
     */
    private function neighbours(string $normalized): array
    {
        $statement = $this->connection->pdo()->prepare(
            'SELECT normalized FROM (SELECT normalized FROM terms WHERE normalized < ? ORDER BY normalized DESC LIMIT 1)'
            . ' UNION ALL '
            . 'SELECT normalized FROM (SELECT normalized FROM terms WHERE normalized > ? ORDER BY normalized ASC LIMIT 1)'
        );
        $statement->execute([$normalized, $normalized]);

        $previousWord = null;
        $nextWord = null;

        foreach ($statement->fetchAll() as $row) {
            if ($row['normalized'] < $normalized) {
                $previousWord = $row['normalized'];
            } else {
                $nextWord = $row['normalized'];
            }
        }

        return [$previousWord, $nextWord];
    }

    /**
     * Defense en profondeur : une lettre absente de $this->tileScores leve plutot que de
     * produire une valeur nulle silencieuse -- voir la meme regle dans
     * Normalizer::score().
     *
     * mb_str_split() (pas str_split()) : $normalized peut contenir Ä/Ö/Ü, codees sur deux
     * octets UTF-8 chacune -- str_split() les aurait coupees en deux octets isoles.
     *
     * @return list<array{letter: string, value: int}>
     */
    private function tiles(string $normalized): array
    {
        $tiles = [];

        foreach (mb_str_split($normalized) as $letter) {
            if (!array_key_exists($letter, $this->tileScores)) {
                throw new \InvalidArgumentException(sprintf('Lettre sans valeur de tuile : %s', $letter));
            }

            $tiles[] = ['letter' => $letter, 'value' => $this->tileScores[$letter]];
        }

        return $tiles;
    }
}
