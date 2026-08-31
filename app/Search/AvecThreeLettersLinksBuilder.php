<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\AvecThreeLettersLinks depuis list_counts (list_type 'length_with_triple'),
 * meme principe que App\Search\AvecTwoLettersLinksBuilder (palier 2) -- une seule requete
 * triviale, aucun calcul sur `terms` au runtime (voir scripts/build_explore_hub_counts.php pour
 * la mesure qui impose ce detour).
 *
 * list_key est toujours "{longueur}:{lettre1}:{lettre2}:{lettre3}" avec
 * lettre1 < lettre2 < lettre3 ALPHABETIQUEMENT (une seule ligne par triplet non ordonne -- jamais
 * les six permutations stockees separement). Depuis une page palier 2 "avec {X} {Y}" (deux
 * lettres source, deja triees alphabetiquement par WordListFilters::fromPath(), X < Y), la paire
 * source peut occuper TROIS positions differentes dans le triplet trie stocke, selon ou tombe la
 * troisieme lettre (partenaire) dans l'ordre alphabetique :
 *
 *   partenaire < X < Y   -> triplet stocke "{longueur}:{partenaire}:{X}:{Y}" (X,Y = lettre2,lettre3)
 *   X < partenaire < Y   -> triplet stocke "{longueur}:{X}:{partenaire}:{Y}" (X,Y = lettre1,lettre3)
 *   X < Y < partenaire   -> triplet stocke "{longueur}:{X}:{Y}:{partenaire}" (X,Y = lettre1,lettre2)
 *
 * Trois motifs LIKE distincts, combines par un seul OR dans une seule requete (jamais trois
 * executions separees) -- contrairement au palier 2 (deux motifs seulement, une paire n'a que
 * deux positions possibles dans une paire triee). Le second motif ("{longueur}:{X}:%:{Y}") est le
 * seul des trois a placer le joker entre deux lettres fixes plutot qu'en tete ou en queue -- reste
 * un LIKE valide (SQLite ne restreint pas '%' a une position), verifie par force brute dans
 * tests/Search/AvecThreeLettersLinksBuilderTest.php.
 *
 * L'URL cible est TOUJOURS construite via WordListFilters::fromPath()->canonicalUrl(), jamais
 * assemblee a la main : ksort() y trie deja les lettres "avec" par cle alphabetique (D-022), donc
 * peu importe l'ordre dans lequel $letter1/$letter2/le partenaire sont passes a fromPath() ici,
 * l'URL rendue est toujours la forme canonique (lettre1 < lettre2 < lettre3).
 *
 * Deux filtres anti-doublon, appliques dans build() ci-dessous (analyse independante data-engine,
 * 2026-08-20, demandee en parallele du meme calcul cote seo-registry avant toute application
 * registre/sitemap -- meme discipline que D-037/D-038/D-039) : DUPLICATE_PARENT_KEYS (doublon avec
 * l'une des trois pages parentes palier 2, ET transitivement avec une page parente palier 1 --
 * preuve mathematique : un triplet ne peut jamais dupliquer une lettre seule sans DEJA dupliquer
 * l'une de ses trois paires, MOTS(triplet) subset MOTS(paire) subset MOTS(lettre seule) --
 * verifie sur les 28 827 triplets reels, 0 cas de duplication "lettre seule" sans duplication de
 * paire correspondante, exactement comme la preuve le predit) et SIBLING_DUPLICATE_KEYS (doublon
 * entre pages SOEURS du palier 3, meme longueur).
 */
final class AvecThreeLettersLinksBuilder
{
    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, audit NO GO 2026-08-31 -- meme discipline que
     * D-DE-024/SuffixExtensionLinksBuilder::EXTERNAL_DUPLICATE_SUFFIXES, decision a journaliser
     * par la session principale dans docs/DECISIONS.md) : ces 426 quadruplets etaient calcules
     * sur storage/dictionary_fr.sqlite (838 180 termes francais), en grande partie via la regle
     * orthographique Q/U propre au francais (ex. "Q:U" apparait dans plus de 60% des cles) --
     * sans aucun rapport avec l'allemand, qui n'a pas cette regle. Copies tels quels lors du
     * portage du depot (git archive), jamais revalides. Videe plutot que conservee.
     *
     * Cible App\Seo\Family::WORD_LIST_AVEC_THREE_LETTERS, qui n'a AUCUNE ligne dans
     * storage/seo_de.sqlite a ce jour (2026-08-31 -- famille pas encore deployee, voir
     * app/Seo/Family.php) : aucune page de ce builder n'est donc indexee actuellement. Le calcul
     * REEL d'un equivalent allemand reste a faire dans une passe separee au moment de
     * l'ouverture de ce palier.
     *
     * @var list<string>
     */
    private const DUPLICATE_PARENT_KEYS = [];

    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, meme discipline que DUPLICATE_PARENT_KEYS
     * ci-dessus) : ces 234 clés etaient calculees sur storage/dictionary_fr.sqlite (D-038 cote
     * francais) et copiees telles quelles lors du portage du depot -- jamais revalidees pour
     * l'allemand. Videe plutot que conservee.
     *
     * Cible App\Seo\Family::WORD_LIST_AVEC_THREE_LETTERS, qui n'a AUCUNE ligne dans
     * storage/seo_de.sqlite a ce jour (2026-08-31, meme situation que DUPLICATE_PARENT_KEYS
     * ci-dessus) : aucune page de ce builder n'est indexee actuellement.
     *
     * @var list<string>
     */
    private const SIBLING_DUPLICATE_KEYS = [];

    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, meme discipline que DUPLICATE_PARENT_KEYS
     * ci-dessus) : ces 666 quadruplets etaient calcules sur storage/dictionary_fr.sqlite (D-041
     * cote francais) et copies tels quels lors du portage du depot -- jamais revalides pour
     * l'allemand. Videe plutot que conservee.
     *
     * Cible App\Seo\Family::WORD_LIST_AVEC_THREE_LETTERS, qui n'a AUCUNE ligne dans
     * storage/seo_de.sqlite a ce jour (2026-08-31, meme situation que DUPLICATE_PARENT_KEYS/
     * SIBLING_DUPLICATE_KEYS ci-dessus) : aucune page de ce builder n'est indexee actuellement.
     *
     * @var list<string>
     */
    private const EXTERNAL_DUPLICATE_KEYS = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * $letter1 et $letter2 : les deux lettres "avec" de la page palier 2 source, dans n'importe
     * quel ordre (triees ici par defense, meme si l'appelant les passe deja triees -- WordListFilters
     * ksort() garantit deja $letter1 < $letter2 quand elles viennent de $filters->withLetters).
     */
    public function build(int $length, string $letter1, string $letter2): AvecThreeLettersLinks
    {
        $pair = [$letter1, $letter2];
        sort($pair, SORT_STRING);
        [$x, $y] = $pair;

        $statement = $this->connection->pdo()->prepare(
            "SELECT list_key, count FROM list_counts WHERE list_type = 'length_with_triple'"
            . ' AND (list_key LIKE ? OR list_key LIKE ? OR list_key LIKE ?)'
        );
        $statement->execute([
            $length . ':' . $x . ':' . $y . ':%',
            $length . ':' . $x . ':%:' . $y,
            $length . ':%:' . $x . ':' . $y,
        ]);

        $links = [];

        foreach ($statement as $row) {
            $key = (string) $row['list_key'];

            if (
                in_array($key, self::DUPLICATE_PARENT_KEYS, true)
                || in_array($key, self::SIBLING_DUPLICATE_KEYS, true)
                || in_array($key, self::EXTERNAL_DUPLICATE_KEYS, true)
            ) {
                continue;
            }

            $parts = explode(':', $key, 4);
            $triple = [$parts[1], $parts[2], $parts[3]];

            $partner = null;
            foreach ($triple as $candidate) {
                if ($candidate !== $x && $candidate !== $y) {
                    $partner = $candidate;
                    break;
                }
            }

            if ($partner === null) {
                // Defensif, jamais attendu : $x et $y sont toujours distincts (page palier 2
                // source), donc exactement une des trois lettres du triplet stocke n'est ni $x
                // ni $y. Ignore silencieusement plutot que de produire un lien incorrect.
                continue;
            }

            $count = (int) $row['count'];
            // D-DE-010 : "-lettres" -> "-buchstaben" (localisation d'URL, voir docs/DECISIONS.md).
            // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- ces lettres
            // peuvent contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
            // D-DE-015 : "avec" -> WordListFilters::KEYWORD_WITH ("mit-buchstaben").
            $path = $length . '-buchstaben/' . WordListFilters::KEYWORD_WITH . '/' . mb_strtolower($x, 'UTF-8')
                . '/' . mb_strtolower($y, 'UTF-8') . '/' . mb_strtolower($partner, 'UTF-8');
            $url = WordListFilters::fromPath($path)?->canonicalUrl();

            if ($url !== null) {
                $links[] = ['letter' => $partner, 'url' => $url, 'count' => $count];
            }
        }

        usort($links, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);

        return new AvecThreeLettersLinks(links: $links, queryCount: 1);
    }
}
