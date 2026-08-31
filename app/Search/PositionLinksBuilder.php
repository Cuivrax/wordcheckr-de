<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\PositionLinks depuis list_counts (D-023bis), meme principe que
 * App\Search\LengthLinksBuilder -- une seule requete triviale, aucun calcul sur `terms` au
 * runtime (voir scripts/build_explore_hub_counts.php pour la mesure qui impose ce detour).
 *
 * list_key est toujours "{longueur}:{lettre}:{position}" pour 'length_with_position' -- le
 * filtre `list_key LIKE '{longueur}:{lettre}:%'` reste un prefixe exact, servi par l'index de
 * cle primaire.
 */
final class PositionLinksBuilder
{
    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, audit NO GO 2026-08-31 -- meme discipline que
     * D-DE-024/SuffixExtensionLinksBuilder::EXTERNAL_DUPLICATE_SUFFIXES, decision a journaliser
     * par la session principale dans docs/DECISIONS.md) : ces 2 clés ("13:W:10", "15:W:10")
     * etaient des doublons structurels francais precis (13/15 lettres, lettre W, position 10 --
     * lie a des mots francais specifiques comme "SASK") et copiees telles quelles lors du
     * portage du depot (git archive) -- jamais revalidees pour l'allemand. Videe plutot que
     * conservee : ces cles filtreraient des combinaisons allemandes par pure coincidence de
     * format, sans aucun rapport avec un vrai doublon allemand.
     *
     * Cible App\Seo\Family::WORD_LIST_POSITION, qui n'a AUCUNE ligne dans storage/seo_de.sqlite
     * a ce jour (2026-08-31 -- famille pas encore deployee, voir app/Seo/Family.php) : aucune
     * page de ce builder n'est donc indexee actuellement, le risque decrit par l'audit (amputer
     * le maillage d'une page deja indexee) est nul pour cette famille precise tant qu'elle n'est
     * pas ouverte. Le calcul REEL d'un equivalent allemand reste a faire dans une passe separee
     * au moment de l'ouverture de ce palier.
     *
     * @var list<string>
     */
    public const EXTERNAL_DUPLICATE_KEYS = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function build(int $length, string $letter): PositionLinks
    {
        $statement = $this->connection->pdo()->prepare(
            "SELECT list_key, count FROM list_counts WHERE list_type = 'length_with_position' AND list_key LIKE ?"
        );
        $statement->execute([$length . ':' . $letter . ':%']);

        $links = [];

        foreach ($statement as $row) {
            $key = (string) $row['list_key'];

            if (in_array($key, self::EXTERNAL_DUPLICATE_KEYS, true)) {
                continue;
            }

            $parts = explode(':', $key);
            $position = (int) $parts[2];
            $count = (int) $row['count'];

            // D-DE-010 : "-lettres"/"commencant"/"terminant" -> "-buchstaben"/"beginnend-mit"/
            // "endend-mit" (localisation d'URL, voir docs/DECISIONS.md).
            // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- $letter peut
            // contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
            // D-DE-015 : segments tires des constantes WordListFilters::KEYWORD_*, plus jamais
            // ecrits a la main ("position" reste "position", cognate allemand).
            $path = match (true) {
                $position === 1 => $length . '-buchstaben/' . WordListFilters::KEYWORD_PREFIX . '/' . mb_strtolower($letter, 'UTF-8'),
                $position === $length => $length . '-buchstaben/' . WordListFilters::KEYWORD_SUFFIX . '/' . mb_strtolower($letter, 'UTF-8'),
                default => $length . '-buchstaben/' . WordListFilters::KEYWORD_POSITION . '/' . $position . '/' . mb_strtolower($letter, 'UTF-8'),
            };

            $url = WordListFilters::fromPath($path)?->canonicalUrl();

            if ($url !== null) {
                $links[] = ['position' => $position, 'url' => $url, 'count' => $count];
            }
        }

        usort($links, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return new PositionLinks(links: $links, queryCount: 1);
    }
}
