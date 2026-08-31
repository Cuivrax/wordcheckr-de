<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\LengthCombinedLinks depuis list_counts (list_type 'length_start_end',
 * D-027, voir reports/query-plans/length-combined-links.md) -- meme principe que
 * App\Search\LetterCombinedLinksBuilder (D-024) et App\Search\PositionLinksBuilder (D-023bis) :
 * une seule requete triviale, aucun GROUP BY sur `terms` au runtime.
 *
 * list_key est toujours "{longueur}:{debut}:{fin}" pour 'length_start_end'. Les deux sens de
 * lecture restent efficaces malgre le joker en tete cote "buildForEnd()" (`LIKE '{N}:%:{Y}'`) :
 * le prefixe litteral "{N}:" borne deja la recherche a une seule longueur (au plus 676 lignes,
 * 26 debuts x 26 fins), memes conditions de cout que le joker en tete deja accepte pour
 * App\Search\LetterCombinedLinksBuilder::buildForEnd() sur la table list_counts entiere (13 846
 * lignes au total, tous list_type confondus -- sans rapport avec le risque de SCAN sur `terms`,
 * 838 180 lignes, que ce projet interdit par ailleurs).
 *
 * Budget runtime : 1 requete SQLite par page (buildForStart() OU buildForEnd(), jamais les deux
 * sur la meme page -- une page ne peut jamais avoir a la fois "commencant" seul ET "terminant"
 * seul).
 */
final class LengthCombinedLinksBuilder
{
    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, audit NO GO 2026-08-31 -- meme discipline que
     * D-DE-024/SuffixExtensionLinksBuilder::EXTERNAL_DUPLICATE_SUFFIXES, decision a journaliser
     * par la session principale dans docs/DECISIONS.md) : cette liste de 292 clés
     * "{longueur}:{début}:{fin}" etait calculee sur storage/dictionary_fr.sqlite (838 180 termes
     * francais, D-041 cote francais) et copiee telle quelle lors du portage du depot (git
     * archive) -- jamais revalidee pour l'allemand. Videe plutot que conservee : une liste de
     * cles francaises filtrerait des combinaisons allemandes par pure coincidence de format, sans
     * aucun rapport avec un vrai doublon allemand -- pire que ne rien filtrer du tout.
     *
     * Cible App\Seo\Family::WORD_LIST_COMBINED, qui n'a AUCUNE ligne dans storage/seo_de.sqlite
     * a ce jour (2026-08-31 -- famille pas encore deployee, voir app/Seo/Family.php) : aucune
     * page de ce builder n'est donc indexee actuellement, le risque decrit par l'audit (amputer
     * le maillage d'une page deja indexee) est nul pour cette famille precise tant qu'elle
     * n'est pas ouverte. Le calcul REEL d'un equivalent allemand reste a faire dans une passe
     * separee au moment de l'ouverture de ce palier -- ce champ ne bloque de toute facon QUE
     * l'affichage du lien "explorer plus loin", jamais une decision d'indexation (calculee
     * independamment par storage/seo_de.sqlite).
     *
     * @var list<string>
     */
    public const EXTERNAL_DUPLICATE_KEYS = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /** Pour une page /mots/{N}-lettres/commencant/{X} : liens vers .../commencant/{X}/terminant/{Y}. */
    public function buildForStart(int $length, string $startLetter): LengthCombinedLinks
    {
        return $this->build($length . ':' . $startLetter . ':%', $length, fromStart: true);
    }

    /** Pour une page /mots/{N}-lettres/terminant/{Y} : liens vers .../commencant/{X}/terminant/{Y}. */
    public function buildForEnd(int $length, string $endLetter): LengthCombinedLinks
    {
        return $this->build($length . ':%:' . $endLetter, $length, fromStart: false);
    }

    private function build(string $likePattern, int $length, bool $fromStart): LengthCombinedLinks
    {
        $statement = $this->connection->pdo()->prepare(
            "SELECT list_key, count FROM list_counts WHERE list_type = 'length_start_end' AND list_key LIKE ?"
        );
        $statement->execute([$likePattern]);

        $links = [];

        foreach ($statement as $row) {
            $key = (string) $row['list_key'];

            if (in_array($key, self::EXTERNAL_DUPLICATE_KEYS, true)) {
                continue;
            }

            [, $start, $end] = explode(':', $key, 3);
            $other = $fromStart ? $end : $start;

            // D-DE-010 : "-lettres"/"commencant"/"terminant" -> "-buchstaben"/"beginnend-mit"/
            // "endend-mit" (localisation d'URL, voir docs/DECISIONS.md).
            // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- $start/$end
            // peuvent contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
            $url = WordListFilters::fromPath(
                $length . '-buchstaben/beginnend-mit/' . mb_strtolower($start, 'UTF-8') . '/endend-mit/' . mb_strtolower($end, 'UTF-8')
            )?->canonicalUrl();

            if ($url !== null) {
                $links[] = ['letter' => $other, 'url' => $url, 'count' => (int) $row['count']];
            }
        }

        usort($links, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);

        return new LengthCombinedLinks(links: $links, queryCount: 1);
    }
}
