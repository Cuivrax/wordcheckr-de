<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Resultat de /wortsuche/{lettres} (Phase 2, docs/08 ; D-DE-009 : localise depuis
 * /jouer), consomme par la couche de rendu
 * (app/View/, hors perimetre de cet agent -- structure prete a rendre, meme principe
 * que TermPage pour /mot/{mot}).
 *
 * $capped = true signale un chevalet dont le nombre de signatures candidates depasse
 * RackSolver::SIGNATURE_CEILING : aucune requete SQLite n'a ete executee, $matches
 * reste vide et $totalMatches est null (inconnu, pas zero -- ne pas confondre "trop de
 * possibilites" avec "aucun resultat trouve"). C'est un resultat distinct, pas une
 * erreur (decision du coordinateur) : le routeur rend toujours la vue normale, jamais
 * une reponse d'erreur pour ce cas.
 *
 * Chaque entree de $matches est necessairement admise (is_admitted = 1, schema.sql) :
 * RackSolver filtre en base -- "quel mot puis-je jouer" ne repond qu'avec des mots
 * effectivement jouables. Le modele a trois statuts reste ferme (CLAUDE.md) ; aucun
 * champ "status" n'est necessaire ici puisque la seule valeur possible serait toujours
 * "admitted". isOds8/isOds9 (noms herites du site francais, double lexique ODS8/ODS9) :
 * conserves tels quels uniquement pour compatibilite avec app/View/play.php (hors
 * perimetre de cet agent) -- RackSolver leur assigne desormais la MEME valeur
 * (is_admitted, lexique allemand unique), voir son docblock de classe.
 */
final class RackPage
{
    /**
     * @param array<string, int> $letterCounts lettres connues et leur multiplicite
     * @param list<array{normalized: string, slug: string, score: int, length: int, isOds8: bool, isOds9: bool}> $matches
     *        limitee a $displayLimit entrees, triee score decroissant puis longueur
     *        decroissante puis ordre alphabetique
     */
    public function __construct(
        public readonly string $slug,
        public readonly array $letterCounts,
        public readonly int $jokerCount,
        public readonly bool $capped,
        public readonly array $matches,
        public readonly ?int $totalMatches,
        public readonly bool $truncated,
        public readonly int $displayLimit,
        public readonly int $candidateSignatureCount,
        public readonly int $queryCount,
    ) {
    }
}
