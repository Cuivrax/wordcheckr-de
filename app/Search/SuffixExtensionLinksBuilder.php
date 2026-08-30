<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\SuffixExtensionLinks depuis list_counts (`list_type` 'suffix2'/'suffix3'/
 * 'suffix4'), même principe que App\Search\PrefixExtensionLinksBuilder — une seule requête
 * triviale, aucun GROUP BY sur `terms` au runtime.
 *
 * `list_key` de 'suffixN' est TOUJOURS le suffixe RÉEL, en ordre de lecture normal (déjà "dé-
 * inversé" par `strrev()` à l'écriture, `scripts/build_explore_hub_counts.php` — jamais la
 * sous-chaîne brute de `reversed`), donc aucune conversion nécessaire ici non plus. L'extension
 * ajoute une lettre AU DÉBUT du suffixe (ex. depuis "NG", "ING"/"ANG"/"ONG"... se terminent tous
 * par "NG") : le joker `_` du LIKE se place donc en TÊTE du motif (`'_' . $suffix`), pas en
 * queue comme pour PrefixExtensionLinksBuilder — seul point de différence structurelle entre les
 * deux builders, documenté explicitement pour ne pas les confondre par simple copier-coller.
 * `list_counts` reste petit (91 681 lignes au total au 2026-08-18) — un LIKE à joker en tête
 * reste trivial ici, même raisonnement déjà accepté pour
 * App\Search\LetterCombinedLinksBuilder::buildForEnd() (D-024).
 */
final class SuffixExtensionLinksBuilder
{
    private const MIN_INPUT_LENGTH = 1;
    private const MAX_INPUT_LENGTH = 3;

    /**
     * NEUTRALISEE POUR L'ALLEMAND (D-DE-024, 2026-08-30) : cette liste etait calculee sur
     * storage/dictionary_fr.sqlite (838 180 termes francais, D-041/D-040 cote francais) et
     * copiee telle quelle lors du portage du depot (git archive) -- jamais revalidee pour
     * l'allemand. Trouvee en verifiant SuffixExtensionLinksBuilder avant d'ouvrir le palier
     * suffix3/suffix4 (D-DE-024) : meme classe de landmine deja signalee a plusieurs reprises
     * cette session pour d'autres constantes figees (DUPLICATE_START_END_KEYS,
     * EXTERNAL_DUPLICATE_WITH_KEYS, EXTERNAL_DUPLICATE_KEYS de LetterCombinedLinksBuilder/
     * PositionLinksBuilder) -- celle-ci n'avait pas encore ete inventoriee explicitement.
     * Videe plutot que conservee : une liste de chaines francaises filtrerait des suffixes
     * allemands par pure coincidence de caracteres, sans aucun rapport avec un vrai doublon
     * allemand -- pire que ne rien filtrer du tout. Le calcul REEL d'un equivalent allemand
     * (doublons croises terminant/commencant sur storage/dictionary_de.sqlite) reste a faire
     * dans une passe separee si besoin -- ce champ ne bloque QUE l'affichage du lien "explorer
     * plus loin" sur une page deja indexee, jamais une decision d'indexation (calculee
     * independamment par les lots scripts/seo-batches/*.php).
     *
     * @var list<string>
     */
    private const EXTERNAL_DUPLICATE_SUFFIXES = [
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * $suffix : suffixe normalisé (A-Z) de la page source, 1 à 3 lettres. Renvoie une liste vide
     * (queryCount = 0) pour toute longueur hors de cette plage.
     */
    public function build(string $suffix): SuffixExtensionLinks
    {
        $length = strlen($suffix);

        if ($length < self::MIN_INPUT_LENGTH || $length > self::MAX_INPUT_LENGTH) {
            return new SuffixExtensionLinks(links: [], queryCount: 0);
        }

        $listType = 'suffix' . ($length + 1);

        $statement = $this->connection->pdo()->prepare(
            "SELECT list_key, count FROM list_counts WHERE list_type = ? AND list_key LIKE ?"
        );
        $statement->execute([$listType, '_' . $suffix]);

        $links = [];

        foreach ($statement as $row) {
            $extendedSuffix = (string) $row['list_key'];

            if (in_array($extendedSuffix, self::EXTERNAL_DUPLICATE_SUFFIXES, true)) {
                continue;
            }

            // D-DE-010 : "terminant" -> "endend-mit" (localisation d'URL, voir docs/DECISIONS.md).
            // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- $extendedSuffix
            // peut contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
            $url = WordListFilters::fromPath('endend-mit/' . mb_strtolower($extendedSuffix, 'UTF-8'))?->canonicalUrl();

            if ($url !== null) {
                $links[] = ['suffix' => $extendedSuffix, 'url' => $url, 'count' => (int) $row['count']];
            }
        }

        usort($links, static fn (array $a, array $b): int => $a['suffix'] <=> $b['suffix']);

        return new SuffixExtensionLinks(links: $links, queryCount: 1);
    }
}
