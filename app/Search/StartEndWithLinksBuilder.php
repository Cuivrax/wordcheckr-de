<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\StartEndWithLinks depuis list_counts (list_type 'start_end_with'), meme
 * principe que App\Search\PositionLinksBuilder / App\Search\AvecSansLengthLinksBuilder -- une
 * seule requete triviale, aucun calcul sur `terms` au runtime (voir
 * scripts/build_explore_hub_counts.php pour la mesure qui impose ce detour, et
 * scripts/bench_start_end_with_build.php pour la comparaison chiffree avec l'alternative SQL).
 *
 * list_key est toujours "{debut}:{fin}:{lettre}" pour 'start_end_with' -- une seule direction de
 * lecture necessaire (contrairement a App\Search\LetterCombinedLinksBuilder/
 * App\Search\LengthCombinedLinksBuilder, qui doivent lire "start_end"/"length_start_end" dans les
 * DEUX sens depuis deux pages source distinctes) : la page source de ce maillage est toujours
 * /mots/commencant/{X}/terminant/{Y}, debut ET fin sont donc TOUJOURS connus simultanement --
 * `list_key LIKE '{debut}:{fin}:%'` reste un prefixe exact, servi par l'index de cle primaire.
 *
 * Budget runtime : 1 requete SQLite par page.
 *
 * Quatre filtres anti-doublon, appliques dans build() ci-dessous (voir chaque constante pour le
 * detail) : DUPLICATE_CONTENT_KEYS, SIBLING_DUPLICATE_KEYS, CROSS_DUPLICATE_LENGTH_KEYS,
 * EXTERNAL_DUPLICATE_KEYS. TOUS LES QUATRE NEUTRALISES POUR L'ALLEMAND (correctif C2, audit NO
 * GO 2026-08-31, decision a journaliser par la session principale dans docs/DECISIONS.md) :
 * calcules sur storage/dictionary_fr.sqlite (838 180 termes francais, D-037 a D-041 cote
 * francais) et copies tels quels lors du portage du depot (git archive) -- jamais revalides pour
 * l'allemand, videes plutot que conservees (une liste de cles francaises filtrerait des paires
 * allemandes par pure coincidence de format). Cible App\Seo\Family::WORD_LIST_COMBINED (le depot
 * allemand n'a pas de famille distincte pour "commencant+terminant+avec" -- voir
 * app/Seo/Family.php), qui n'a AUCUNE ligne dans storage/seo_de.sqlite a ce jour (2026-08-31,
 * famille pas encore deployee) : ni la page SOURCE de ce builder (commencant+terminant) ni ses
 * pages cibles ne sont indexees actuellement, le risque decrit par l'audit est donc nul pour
 * cette famille tant qu'elle n'est pas ouverte. Le calcul REEL d'un equivalent allemand pour
 * chacun des quatre axes reste a faire dans une passe separee au moment de l'ouverture de ce
 * palier.
 */
final class StartEndWithLinksBuilder
{
    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, voir le docblock de classe ci-dessus pour le
     * detail commun aux quatre constantes de ce fichier) : contenait 227 triples
     * "{debut}:{fin}:{lettre}" calcules sur storage/dictionary_fr.sqlite (D-037 cote francais,
     * historique conserve ci-dessous pour memoire, ne s'applique plus a cette base).
     *
     * Historique francais : triples a contenu strictement DUPLIQUE avec leur page parente
     * /mots/commencant/{debut}/terminant/{fin} (sans "avec") -- une ligne 'start_end_with' etait
     * un doublon SI ET SEULEMENT SI son `count` etait EXACTEMENT EGAL au `count` de l'entree
     * parente 'start_end' correspondante.
     *
     * @var list<string>
     */
    private const DUPLICATE_CONTENT_KEYS = [];

    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, voir le docblock de classe ci-dessus) :
     * contenait 428 triples "{debut}:{fin}:{lettre}" calcules sur storage/dictionary_fr.sqlite
     * (D-038 cote francais, historique conserve ci-dessous pour memoire, ne s'applique plus a
     * cette base).
     *
     * Historique francais : doublons de contenu entre pages SOEURS "avec" -- deux lettres "avec"
     * DIFFERENTES du MEME panier commencant+terminant isolant neanmoins exactement le meme
     * sous-ensemble de mots.
     *
     * @var list<string>
     */
    private const SIBLING_DUPLICATE_KEYS = [];

    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, voir le docblock de classe ci-dessus) :
     * contenait 333 triples "{debut}:{fin}:{lettre}" calcules sur storage/dictionary_fr.sqlite
     * (3e audit consolide cote francais, historique conserve ci-dessous pour memoire, ne
     * s'applique plus a cette base).
     *
     * Historique francais : doublons de contenu CROISES entre DEUX FAMILLES DIFFERENTES
     * partageant le meme panier de base commencant+terminant {debut}:{fin} -- une tranche
     * LONGUEUR (App\Search\LengthLinksBuilder::byStartEnd) et une tranche LETTRE "avec" (ce
     * builder) isolant exactement le meme ensemble de mots.
     *
     * @var list<string>
     */
    private const CROSS_DUPLICATE_LENGTH_KEYS = [];

    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, voir le docblock de classe ci-dessus) :
     * contenait 314 triples "{debut}:{fin}:{lettre}" calcules sur storage/dictionary_fr.sqlite
     * (D-041 cote francais, historique conserve ci-dessous pour memoire, ne s'applique plus a
     * cette base).
     *
     * Historique francais : doublons de contenu CROISÉS avec une famille EXTÉRIEURE à l'axe
     * commençant+terminant+avec -- une page "commençant/{X}/terminant/{Y}/avec/{Z}" partageant un
     * contenu strictement identique avec une page d'une famille sans rapport (terminant ou
     * commençant multi-lettres avec un préfixe/suffixe totalement différent...).
     *
     * @var list<string>
     */
    private const EXTERNAL_DUPLICATE_KEYS = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function build(string $startLetter, string $endLetter): StartEndWithLinks
    {
        $statement = $this->connection->pdo()->prepare(
            "SELECT list_key, count FROM list_counts WHERE list_type = 'start_end_with' AND list_key LIKE ?"
        );
        $statement->execute([$startLetter . ':' . $endLetter . ':%']);

        // URL de la page parente (commencant+terminant, sans "avec") : sert a detecter les
        // lettres "avec" degenerees (D-032, WordListFilters::fromPath() collapse silencieusement
        // "avec/X" quand X est deja garanti par un commencant/terminant d'une seule lettre --
        // meme mecanisme que le collapse "position" deja etabli, D-023). Sans cette detection,
        // ces lettres degenerees (toujours PRESENTES dans list_counts : count_chars() du script
        // de precalcul liste la lettre de debut et la lettre de fin comme des lettres
        // "distinctes" du mot au meme titre que les autres, aucune exclusion cote precalcul)
        // produiraient un lien dont l'URL est IDENTIQUE a celle de la page source elle-meme --
        // un doublon trompeur (deux lettres "avec" differentes menant chacune vers la MEME URL
        // que la page qui les propose), pas seulement une page en moins.
        // D-DE-010 : "commencant"/"terminant" -> "beginnend-mit"/"endend-mit" (localisation
        // d'URL, voir docs/DECISIONS.md).
        // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- $startLetter/
        // $endLetter/$letter peuvent contenir Ä/Ö/Ü (list_counts), signale par l'audit
        // independant.
        // D-DE-015 : segments tires des constantes WordListFilters::KEYWORD_*, plus jamais
        // ecrits a la main ("avec" -> "mit-buchstaben").
        $pairPath = WordListFilters::KEYWORD_PREFIX . '/' . mb_strtolower($startLetter, 'UTF-8')
            . '/' . WordListFilters::KEYWORD_SUFFIX . '/' . mb_strtolower($endLetter, 'UTF-8');

        $parentUrl = WordListFilters::fromPath($pairPath)?->canonicalUrl();

        $links = [];

        foreach ($statement as $row) {
            $parts = explode(':', (string) $row['list_key'], 3);
            $letter = $parts[2];
            $count = (int) $row['count'];

            $path = $pairPath . '/' . WordListFilters::KEYWORD_WITH . '/' . mb_strtolower($letter, 'UTF-8');
            $url = WordListFilters::fromPath($path)?->canonicalUrl();

            if ($url === null || $url === $parentUrl) {
                continue;
            }

            // Doublon de CONTENU (audit consolide, NO GO) : URL distincte de la page parente,
            // mais tous les mots de la paire contiennent deja cette lettre -- voir
            // DUPLICATE_CONTENT_KEYS ci-dessus, jamais un lien vers une page dont le contenu est
            // identique a une page deja indexee.
            $key = strtoupper($startLetter) . ':' . strtoupper($endLetter) . ':' . strtoupper($letter);

            if (in_array($key, self::DUPLICATE_CONTENT_KEYS, true)) {
                continue;
            }

            // Doublon de CONTENU entre pages SOEURS (I-A, 2e audit consolide) : une AUTRE lettre
            // "avec" du MEME panier produit exactement le meme sous-ensemble de mots -- voir
            // SIBLING_DUPLICATE_KEYS ci-dessus. La lettre alphabetiquement la plus petite du
            // groupe reste candidate (jamais exclue par ce filtre) ; les autres sont retirees ici.
            if (in_array($key, self::SIBLING_DUPLICATE_KEYS, true)) {
                continue;
            }

            // Doublon de contenu CROISE avec l'AUTRE famille (3e audit consolide) : la tranche
            // LONGUEUR de ce meme panier {debut}:{fin} (App\Search\LengthLinksBuilder::byStartEnd)
            // contient EXACTEMENT le meme ensemble de mots -- voir CROSS_DUPLICATE_LENGTH_KEYS
            // ci-dessus. La variante LONGUEUR reste candidate, celle-ci (avec) est retiree.
            if (in_array($key, self::CROSS_DUPLICATE_LENGTH_KEYS, true)) {
                continue;
            }

            // Doublon de contenu CROISE avec une famille EXTERIEURE au panier commencant+
            // terminant d'origine (D-041) : voir EXTERNAL_DUPLICATE_KEYS ci-dessus.
            if (in_array($key, self::EXTERNAL_DUPLICATE_KEYS, true)) {
                continue;
            }

            $links[] = ['letter' => $letter, 'url' => $url, 'count' => $count];
        }

        usort($links, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);

        return new StartEndWithLinks(links: $links, queryCount: 1);
    }
}
