<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\ExploreHub depuis la table list_counts, precalculee hors ligne par
 * scripts/build_explore_hub_counts_de.php.
 *
 * Mesure qui a impose ce detour (pas de GROUP BY direct au runtime) : un GROUP BY sur
 * substr(normalized,1,1) / substr(reversed,1,1) n'a aucun index disponible sur l'expression
 * calculee -- 245 ms et 215 ms mesures sur les 838 180 lignes reelles cote francais (SCAN
 * complet + TEMP B-TREE), tres au-dessus du budget TTFB p95 < 250 ms pour une seule page
 * (CLAUDE.md).
 *
 * CORRECTIF C1 (audit NO GO, 2026-08-31) : le docblock ci-dessus affirmait a tort "la table
 * list_counts (66 lignes fixes) rend cette lecture triviale" -- ce chiffre decrivait l'etat
 * initial du site francais avant l'ouverture des paliers combinatoires (D-DE-018 a D-DE-027,
 * schema.sql `list_type` desormais a 19/19 valeurs) : list_counts contient reellement
 * 123 471 lignes cote allemand (19 list_type), dont seules 72 sont utiles a ce hub (14
 * 'length' + 29 'start' + 29 'end'). L'ancien SELECT * FROM list_counts (sans WHERE, via
 * PDO::query(), jamais prepare -- la SEULE occurrence de ->query() dans tout app/, tout le
 * reste utilisant systematiquement prepare()) lisait et iterait les 123 471 lignes en PHP
 * pour n'en retenir que 72 : mesure 93-109 ms (SCAN complet) contre <1 ms (SEARCH USING
 * INDEX sqlite_autoindex_list_counts_1) avec le WHERE prepare ci-dessous -- voir le rapport
 * AFTER de cette tache pour l'EXPLAIN QUERY PLAN et le detail du benchmark avant/apres.
 *
 * Budget runtime : 1 requete SQLite preparee, bornee par WHERE list_type IN (?,?,?) LIMIT,
 * aucun GROUP BY, aucun SCAN de `terms` -- tres en-dessous du plafond de moins de 10
 * (CLAUDE.md).
 */
final class ExploreHubBuilder
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function build(): ExploreHub
    {
        // CORRECTIF C1 : requete preparee, bornee a list_type IN ('length','start','end') --
        // les 3 seuls list_type consommes par le switch ci-dessous -- avec un LIMIT explicite
        // (100, marge au-dessus des 72 lignes utiles reelles : 14 longueurs + 29 lettres
        // 'start' + 29 lettres 'end', alphabet allemand A-Z + Ä/Ö/Ü) plutot qu'un SELECT * FROM
        // list_counts non prepare et non borne (123 471 lignes, 19 list_type -- voir le
        // docblock de classe pour la mesure avant/apres).
        $statement = $this->connection->pdo()->prepare(
            'SELECT list_type, list_key, count FROM list_counts WHERE list_type IN (?, ?, ?) LIMIT 100'
        );
        $statement->execute(['length', 'start', 'end']);

        $byLength = [];
        $byStart = [];
        $byEnd = [];

        foreach ($statement as $row) {
            $key = (string) $row['list_key'];
            $count = (int) $row['count'];

            // D-DE-010 : "-lettres"/"commencant"/"terminant" -> "-buchstaben"/"beginnend-mit"/
            // "endend-mit" (localisation d'URL, voir docs/DECISIONS.md) -- App\Search\
            // WordListFilters::KEYWORDS ne reconnait plus les anciens mots-cles francais,
            // fromPath() y renverrait silencieusement null (aucun lien genere) sinon.
            // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') ci-dessous --
            // $key peut contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
            switch ($row['list_type']) {
                case 'length':
                    $url = WordListFilters::fromPath($key . '-buchstaben')?->canonicalUrl();

                    if ($url !== null) {
                        $byLength[] = ['length' => (int) $key, 'url' => $url, 'count' => $count];
                    }
                    break;

                case 'start':
                    $url = WordListFilters::fromPath('beginnend-mit/' . mb_strtolower($key, 'UTF-8'))?->canonicalUrl();

                    if ($url !== null) {
                        $byStart[] = ['letter' => $key, 'url' => $url, 'count' => $count];
                    }
                    break;

                case 'end':
                    $url = WordListFilters::fromPath('endend-mit/' . mb_strtolower($key, 'UTF-8'))?->canonicalUrl();

                    if ($url !== null) {
                        $byEnd[] = ['letter' => $key, 'url' => $url, 'count' => $count];
                    }
                    break;
            }
        }

        usort($byLength, static fn (array $a, array $b): int => $a['length'] <=> $b['length']);
        usort($byStart, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);
        usort($byEnd, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);

        return new ExploreHub(byLength: $byLength, byStart: $byStart, byEnd: $byEnd, queryCount: 1);
    }
}
