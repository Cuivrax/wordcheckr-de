<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\LengthLinks depuis la table list_counts (D-022), meme principe et meme
 * source que App\Search\ExploreHubBuilder -- une seule requete triviale, aucun GROUP BY sur
 * `terms` au runtime (voir scripts/build_explore_hub_counts.php pour la mesure qui impose ce
 * detour).
 *
 * list_key est toujours "{longueur}:{lettre}" pour 'length_start'/'length_end'/'length_with', et
 * "{longueur}:{lettre}:{position}" pour 'length_with_position' (D-023bis, ajoute au correctif
 * C1 de l'audit D-028, 2026-08-11), et "{longueur}:{debut}:{fin}" pour 'length_start_end' (D-027,
 * ajoute au correctif C1 applique cette fois a la variante commencant+terminant, 2026-08-18) --
 * le filtre `list_key LIKE '{longueur}:%'` reste sans ambiguite pour les cinq list_type a la fois
 * (le premier ':' delimite toujours la longueur).
 *
 * Budget runtime : 1 requete SQLite -- appelee uniquement pour une page "longueur seule"
 * (aucune autre contrainte, voir public/index.php), en plus des requetes deja comptees par
 * WordListSolver pour cette meme page (2 au plus), reste tres en-dessous du plafond de moins
 * de 10 (CLAUDE.md). L'ajout de 'length_with_position' puis de 'length_start_end' au IN(...)
 * n'ajoute AUCUNE requete supplementaire (meme requete elargie, meme LIKE '{longueur}:%').
 */
final class LengthLinksBuilder
{
    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, audit NO GO 2026-08-31 -- meme discipline que
     * D-DE-024/SuffixExtensionLinksBuilder::EXTERNAL_DUPLICATE_SUFFIXES, decision a journaliser
     * par la session principale dans docs/DECISIONS.md) : cette liste des 52 paires
     * (longueur, debut, fin) etait calculee sur storage/dictionary_fr.sqlite (838 180 termes
     * francais, D-025/I-1 cote francais, voir historique ci-dessous) et copiee telle quelle lors
     * du portage du depot (git archive) -- jamais revalidee pour l'allemand. Trouvee lue au
     * runtime par l'audit independant : filtrait des cles allemandes par pure coincidence de
     * format ("{N}:{X}:{Y}"), sans aucun rapport avec un vrai doublon allemand -- pire que ne
     * rien filtrer du tout (2 141 cles francaises figees correspondaient a de vraies cles
     * list_counts allemandes sur l'ensemble des builders touches par ce correctif, dont 179
     * suppressions injustifiees sur la seule famille tranchee exactement ici). Videe plutot que
     * conservee. Le calcul REEL d'un equivalent allemand (doublons longueur/commencant+terminant
     * sur storage/dictionary_de.sqlite) reste a faire dans une passe separee si besoin -- ce
     * champ ne bloque QUE l'affichage du lien "explorer plus loin" sur une page deja indexee,
     * jamais une decision d'indexation (calculee independamment par storage/seo_de.sqlite).
     *
     * Historique francais (pour memoire, ne s'applique plus a cette base) : les 52 paires a
     * contenu strictement duplique identifiees par D-025 (I-1) -- pour chacune, TOUS les mots
     * commencant par {debut} et terminant par {fin} (toutes longueurs confondues) partageaient
     * exactement la meme longueur {longueur} cote francais.
     *
     * @var list<string>
     */
    private const DUPLICATE_START_END_KEYS = [];

    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, audit NO GO 2026-08-31 -- meme discipline que
     * DUPLICATE_START_END_KEYS ci-dessus) : la seule entree ('2:W') etait un doublon structurel
     * francais precis (WU/wu) sans aucune raison de correspondre a un mot allemand de 2 lettres.
     * Videe plutot que conservee, meme garantie de securite (n'affecte que le maillage d'une
     * page deja indexee, jamais storage/seo_de.sqlite).
     *
     * @var list<string>
     */
    private const EXTERNAL_DUPLICATE_WITH_KEYS = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function build(int $length): LengthLinks
    {
        $statement = $this->connection->pdo()->prepare(
            "SELECT list_type, list_key, count FROM list_counts"
            . " WHERE list_type IN ('length_start', 'length_end', 'length_with', 'length_with_position', 'length_start_end') AND list_key LIKE ?"
        );
        $statement->execute([$length . ':%']);

        $byStart = [];
        $byEnd = [];
        $byWith = [];
        $byPositionGrouped = [];
        $byStartEndGrouped = [];

        foreach ($statement as $row) {
            $key = (string) $row['list_key'];
            $count = (int) $row['count'];

            if ($row['list_type'] === 'length_with_position') {
                // key = "{longueur}:{lettre}:{position}" (D-023bis) -- structure a 3 segments,
                // distincte des trois autres list_type (2 segments). Positions degenerees (1re
                // et derniere lettre) exclues ici : deja couvertes par byStart/byEnd ci-dessous,
                // D-023 les collapse toujours vers commencant/terminant, jamais une URL
                // "position/1/..." ni "position/{longueur}/...".
                [, $letter, $positionRaw] = explode(':', $key, 3);
                $position = (int) $positionRaw;

                if ($position <= 1 || $position >= $length) {
                    continue;
                }

                // Doublon de contenu CROISE avec une famille EXTERIEURE (D-041) : voir
                // PositionLinksBuilder::EXTERNAL_DUPLICATE_KEYS -- meme famille cible
                // (Family::WORD_LIST_POSITION), source de verite unique referencee ici plutot que
                // dupliquee.
                if (in_array($key, PositionLinksBuilder::EXTERNAL_DUPLICATE_KEYS, true)) {
                    continue;
                }

                // D-DE-010 : "-lettres" -> "-buchstaben" (localisation d'URL, voir docs/DECISIONS.md).
                // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- $letter peut
                // contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
                // D-DE-015 : "position" reste "position" (cognate allemand, voir
                // WordListFilters), mais passe par la constante KEYWORD_POSITION comme tous
                // les autres segments -- plus de chaine ecrite a la main.
                $url = WordListFilters::fromPath($length . '-buchstaben/' . WordListFilters::KEYWORD_POSITION . '/' . $position . '/' . mb_strtolower($letter, 'UTF-8'))?->canonicalUrl();

                if ($url !== null) {
                    $byPositionGrouped[$position][] = ['letter' => $letter, 'url' => $url, 'count' => $count];
                }

                continue;
            }

            if ($row['list_type'] === 'length_start_end') {
                // key = "{longueur}:{debut}:{fin}" (D-027) -- structure a 3 segments elle aussi,
                // mais debut/fin plutot que lettre/position. Les 52 paires a contenu duplique
                // (D-025, I-1) sont exclues explicitement : ces pages resteront noindex,follow en
                // permanence (R3), inutile et trompeur de leur creer un lien depuis une page deja
                // indexee.
                if (in_array($key, self::DUPLICATE_START_END_KEYS, true)) {
                    continue;
                }

                // Doublon de contenu CROISE avec une famille EXTERIEURE (D-041) : voir
                // LengthCombinedLinksBuilder::EXTERNAL_DUPLICATE_KEYS -- meme famille cible
                // (Family::WORD_LIST_COMBINED avec longueur), source de verite unique referencee
                // ici plutot que dupliquee.
                if (in_array($key, LengthCombinedLinksBuilder::EXTERNAL_DUPLICATE_KEYS, true)) {
                    continue;
                }

                [, $start, $end] = explode(':', $key, 3);

                // D-DE-010 : "-lettres"/"commencant"/"terminant" -> "-buchstaben"/"beginnend-mit"/
                // "endend-mit" (localisation d'URL, voir docs/DECISIONS.md).
                // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- $start/$end
                // peuvent contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
                $url = WordListFilters::fromPath(
                    $length . '-buchstaben/beginnend-mit/' . mb_strtolower($start, 'UTF-8') . '/endend-mit/' . mb_strtolower($end, 'UTF-8')
                )?->canonicalUrl();

                if ($url !== null) {
                    $byStartEndGrouped[$start][] = ['letter' => $end, 'url' => $url, 'count' => $count];
                }

                continue;
            }

            $letter = substr($key, strpos($key, ':') + 1);

            switch ($row['list_type']) {
                case 'length_start':
                    // D-DE-010 : "-lettres"/"commencant" -> "-buchstaben"/"beginnend-mit".
                    // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8').
                    $url = WordListFilters::fromPath($length . '-buchstaben/beginnend-mit/' . mb_strtolower($letter, 'UTF-8'))?->canonicalUrl();

                    if ($url !== null) {
                        $byStart[] = ['letter' => $letter, 'url' => $url, 'count' => $count];
                    }
                    break;

                case 'length_end':
                    // D-DE-010 : "-lettres"/"terminant" -> "-buchstaben"/"endend-mit".
                    // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8').
                    $url = WordListFilters::fromPath($length . '-buchstaben/endend-mit/' . mb_strtolower($letter, 'UTF-8'))?->canonicalUrl();

                    if ($url !== null) {
                        $byEnd[] = ['letter' => $letter, 'url' => $url, 'count' => $count];
                    }
                    break;

                case 'length_with':
                    // Doublon de contenu CROISE avec une famille EXTERIEURE (D-041) : voir
                    // EXTERNAL_DUPLICATE_WITH_KEYS.
                    if (in_array($key, self::EXTERNAL_DUPLICATE_WITH_KEYS, true)) {
                        break;
                    }

                    // D-DE-010 : "-lettres" -> "-buchstaben" (localisation d'URL, voir docs/DECISIONS.md).
                    // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8').
                    // D-DE-015 : "avec" -> WordListFilters::KEYWORD_WITH ("mit-buchstaben").
                    $url = WordListFilters::fromPath($length . '-buchstaben/' . WordListFilters::KEYWORD_WITH . '/' . mb_strtolower($letter, 'UTF-8'))?->canonicalUrl();

                    if ($url !== null) {
                        $byWith[] = ['letter' => $letter, 'url' => $url, 'count' => $count];
                    }
                    break;
            }
        }

        usort($byStart, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);
        usort($byEnd, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);
        usort($byWith, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);

        ksort($byPositionGrouped);
        $byPosition = [];

        foreach ($byPositionGrouped as $position => $letters) {
            usort($letters, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);
            $byPosition[] = ['position' => $position, 'letters' => $letters];
        }

        ksort($byStartEndGrouped);
        $byStartEnd = [];

        foreach ($byStartEndGrouped as $start => $letters) {
            usort($letters, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);
            $byStartEnd[] = ['start' => $start, 'letters' => $letters];
        }

        return new LengthLinks(
            byStart: $byStart,
            byEnd: $byEnd,
            byWith: $byWith,
            byPosition: $byPosition,
            byStartEnd: $byStartEnd,
            queryCount: 1,
        );
    }
}
