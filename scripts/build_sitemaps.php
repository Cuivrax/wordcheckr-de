<?php

declare(strict_types=1);

/**
 * Génère les fragments de sitemap et sitemap-index.xml depuis storage/seo_de.sqlite.
 * Hors ligne uniquement (CLI) -- jamais appelé au runtime, même principe que
 * scripts/build_seo_registry.php et scripts/apply_seo_batch.php. Adapté des dépôts français
 * et espagnol cousins (scripts/build_sitemaps.php, FR/ES) -- URLs wordcheckr.de, JAMAIS
 * wordcheckr.fr ni l'ancien schéma d'URL français (/mots/...) -- erreur déjà trouvée et
 * corrigée une fois sur CE dépôt (public/robots.txt et public/sitemaps/ hérités du scaffold
 * FR->DE pointaient vers le mauvais domaine ET l'ancien schéma, D-DE-012) ; ne pas la
 * réintroduire.
 *
 * Usage :
 *     php scripts/build_sitemaps.php --base-url=https://www.wordcheckr.de
 *
 * --base-url est OBLIGATOIRE : aucun domaine par défaut n'est supposé. Un domaine faux publié
 * dans un sitemap serait pire qu'aucun sitemap.
 *
 * Ne lit QUE storage/seo_de.sqlite : les colonnes route_path/canonical_path/sitemap_fragment
 * suffisent, aucun accès à storage/dictionary_de.sqlite n'est nécessaire ici (les deux bases
 * restent indépendantes même à la génération des sitemaps).
 *
 * Règles dures appliquées (docs/05_URL_SEO_INDEXATION.md, section Sitemaps, hérité -- toujours
 * valable) :
 *   - seules les lignes robots = 'index,follow' ET sitemap_fragment NOT NULL sont émises ;
 *     une ligne 'noindex,follow' n'apparaît JAMAIS dans un sitemap, même si sitemap_fragment
 *     était renseigné par erreur (défendu aussi en amont par scripts/apply_seo_batch.php, R4) ;
 *   - 40 000 URL au plus par fragment -- vérifié ici en sortie, pas seulement supposé respecté
 *     par la donnée en entrée (défense en profondeur : si un fragment dépasse la limite, le
 *     script s'arrête en erreur plutôt que d'écrire un fragment non conforme) ;
 *   - la famille détermine le PRÉFIXE attendu du fragment -- un fragment dont le préfixe ne
 *     correspond pas à la famille de toutes ses lignes est un signal d'incohérence de
 *     nommage, rejeté ici plutôt que publié silencieusement.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "scripts/build_sitemaps.php ne s'execute qu'en CLI, hors ligne.\n");
    exit(1);
}

const MAX_URLS_PER_FRAGMENT = 40_000;

/**
 * Encode chaque SEGMENT de route_path par rawurlencode() (RFC 3986), sans toucher aux '/'
 * separateurs -- necessaire pour toute route contenant Ä/Ö/Ü (D-DE-002 : lettres allemandes
 * distinctes, jamais repliees sur A/O/U, donc presentes telles quelles dans normalized/slug).
 * 115 536 mots admis (19,6% de storage/dictionary_de.sqlite) contiennent au moins une de ces
 * trois lettres -- un <loc> XML valide en UTF-8 n'est PAS pour autant un URI valide au sens du
 * protocole sitemap (sitemaps.org/RFC 3986 : un <loc> doit rester une sequence ASCII, tout
 * caractere non-ASCII doit etre pourcent-encode) ; XMLWriter::writeElement() echappe les
 * caracteres XML reserves (&, <, >...) mais n'encode PAS l'UTF-8 non-ASCII en pourcent-encodage
 * -- laisser passer un octet UTF-8 brut dans <loc> est rejete ou mal interprete par certains
 * lecteurs de sitemap. Trouve et corrige avant toute application reelle de la famille
 * word_admitted (aucune ligne appliquee sur des mots Ä/Ö/Ü a ce stade, D-DE-013, mais le script
 * doit rester correct par construction, pas seulement pour le lot actuellement applique).
 */
function encodeRoutePath(string $routePath): string
{
    $segments = explode('/', $routePath);

    return implode('/', array_map(
        static fn (string $segment): string => rawurlencode($segment),
        $segments,
    ));
}

/**
 * Familles REELLEMENT peuplees sur ce depot a ce stade (docs/DECISIONS.md D-DE-013). Toute
 * famille combinatoire future (beginnend-mit, endend-mit, avec, position, combined...) devra
 * ajouter sa propre entree ici au moment ou elle sera reellement ouverte -- jamais avant, meme
 * discipline que les depots francais/espagnol cousins (voir leur FAMILY_FRAGMENT_PREFIXES).
 *
 * @var array<string, string>
 */
const FAMILY_FRAGMENT_PREFIXES = [
    // home (racine '/' + hub de navigation '/woerter') : 1 page a ce stade ('/' seul, le hub
    // reste noindex,follow -- D-DE-013 point 5, list_counts vide), D-DE-013.
    'home' => 'core',
    // word_admitted (/wort/{mot}, mots admis enz/german-wordlist + hippler/german-wordlist) :
    // 590 856 URL, D-DE-016.
    'word_admitted' => 'words',
    // word_list_length (/woerter/{N}-buchstaben) : les 14 longueurs (2 a 15), D-DE-013
    // (correction du plan initial qui n'en anticipait que 2 -- CHAQUE fiche de mot admis lie
    // inconditionnellement sa propre page de longueur, App\Search\RelationsFinder::
    // relatedSearches(), verifie en direct).
    'word_list_length' => 'letters',
    // word_list_commencant : PLUSIEURS paliers, meme famille, fragments distincts --
    // starts-0001.xml (/woerter/beginnend-mit/{lettre}, 1 lettre unique, 29 lettres, D-DE-017),
    // starts-0002.xml (/woerter/{N}-buchstaben/beginnend-mit/{lettre}, longueur+1 lettre, 401
    // combinaisons reelles, D-DE-019/D-DE-018/list_counts), starts-0003.xml
    // (/woerter/beginnend-mit/{3 lettres}, 3703 combinaisons a lien entrant reel, D-DE-019).
    'word_list_commencant' => 'starts',
    // word_list_terminant : PLUSIEURS paliers, meme famille -- ends-0001.xml
    // (/woerter/endend-mit/{2 lettres}, palier 2 lettres, PAS 1 lettre -- voir docs/DECISIONS.md
    // D-DE-017 pour la raison mesuree : App\Search\RelationsFinder::relatedSearches() n'emet
    // jamais de suffixe a 1 seule lettre, seulement 2, la longueur MIN_LENGTH=2 rendant
    // mb_substr($word, -min(2,$length)) toujours egal a 2), ends-0002.xml
    // (/woerter/{N}-buchstaben/endend-mit/{lettre}, longueur+1 lettre, 343 pages sur 353
    // combinaisons reelles -- 10 exclues explicitement, D-DE-019 : 1 doublon de contenu +
    // 9 <title> >= 60 caracteres, voir scripts/seo-batches/length-start-end-2026-08-30.php).
    'word_list_terminant' => 'ends',
    // word_list_avec_single_letter (/woerter/{N}-buchstaben/mit-buchstaben/{lettre}, palier 1
    // lettre) : D-DE-026, distinct de word_list_avec (generique, NEVER_SITEMAP).
    'word_list_avec_single_letter' => 'avec-single',
    // word_list_avec_two_letters (/woerter/{N}-buchstaben/mit-buchstaben/{X}/{Y}, palier 2
    // lettres) : D-DE-027.
    'word_list_avec_two_letters' => 'avec-two',
    // rack, enthalten/ohne/muster, position, "avec" 3 lettres, et beginnend-mit+endend-mit
    // combine (690 combinaisons, 1 seule liee) : absents volontairement -- soit
    // App\Seo\Family::NEVER_SITEMAP (jamais de prefixe), soit non encore ouverts (D-DE-017/
    // D-DE-019/D-DE-026/D-DE-027).
];

$baseUrl = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base-url=')) {
        $baseUrl = substr($arg, strlen('--base-url='));
    }
}

if ($baseUrl === null || $baseUrl === '') {
    fwrite(STDERR, "--base-url=https://... est obligatoire\n");
    exit(1);
}

$baseUrl = rtrim($baseUrl, '/');

$root = dirname(__DIR__);
// SCRABBLE_SEO_DB_PATH / SCRABBLE_PUBLIC_DIR : reserves aux tests (tests/Seo/), jamais
// definis en usage normal -- meme raison que scripts/apply_seo_batch.php : permet de verifier
// ce script sans jamais ecrire dans le vrai public/ pendant la suite de tests.
$dbPath = getenv('SCRABBLE_SEO_DB_PATH') ?: $root . '/storage/seo_de.sqlite';
$publicDir = getenv('SCRABBLE_PUBLIC_DIR') ?: $root . '/public';

if (!is_file($dbPath)) {
    fwrite(STDERR, "registre introuvable : {$dbPath}\nlancer d'abord scripts/build_seo_registry.php puis scripts/apply_seo_batch.php\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Iteration en flux (PDOStatement parcouru directement, jamais fetchAll) : un registre a
// l'echelle du dictionnaire (plusieurs centaines de milliers de lignes en famille
// word_admitted, D-DE-013) epuise la memoire CLI par defaut (128 Mo) si tout est charge en
// tableau avant traitement -- meme constat que les depots francais/espagnol cousins. La
// requete trie deja par sitemap_fragment : un seul fragment (au plus MAX_URLS_PER_FRAGMENT
// lignes) est jamais retenu en memoire a la fois, jamais le registre entier.
$statement = $pdo->query(
    "SELECT route_path, family, canonical_path, sitemap_fragment FROM registry "
    . "WHERE robots = 'index,follow' AND sitemap_fragment IS NOT NULL "
    . 'ORDER BY sitemap_fragment, route_path'
);

$sitemapsDir = $publicDir . '/sitemaps';

if (!is_dir($sitemapsDir)) {
    mkdir($sitemapsDir, 0777, true);
}

$fragmentFiles = [];
$totalUrls = 0;

$currentFragment = null;
/** @var list<array<string, string>> */
$currentRows = [];

$flushFragment = static function (?string $fragment, array $rows) use ($sitemapsDir, $baseUrl, &$fragmentFiles): void {
    if ($fragment === null || $rows === []) {
        return;
    }

    if (count($rows) > MAX_URLS_PER_FRAGMENT) {
        fwrite(STDERR, sprintf(
            "fragment '%s' depasse %d URL (%d) -- rescinder le lot en amont, jamais publier tel quel\n",
            $fragment,
            MAX_URLS_PER_FRAGMENT,
            count($rows),
        ));
        exit(1);
    }

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('urlset');
    $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    foreach ($rows as $row) {
        $xml->startElement('url');
        $xml->writeElement('loc', $baseUrl . encodeRoutePath($row['route_path']));
        $xml->endElement();
    }

    $xml->endElement();
    $xml->endDocument();

    $fileName = $fragment . '.xml';
    $filePath = $sitemapsDir . '/' . $fileName;
    file_put_contents($filePath, $xml->outputMemory());
    $fragmentFiles[] = $fileName;

    printf("%s : %d URL\n", $fileName, count($rows));
};

foreach ($statement as $row) {
    // Une URL non canonique (canonical_path != route_path) ne doit jamais apparaitre dans un
    // sitemap : chaque entree de sitemap doit "repondre 200, index, canonical autonome" (docs/05)
    // -- une ligne qui pointe son canonical ailleurs qu'elle-meme n'est PAS le gagnant.
    if ($row['canonical_path'] !== $row['route_path']) {
        fwrite(STDERR, sprintf(
            "ignore (canonical non autonome) : %s -> %s\n",
            $row['route_path'],
            $row['canonical_path'],
        ));

        continue;
    }

    $expectedPrefix = FAMILY_FRAGMENT_PREFIXES[$row['family']] ?? null;

    if ($expectedPrefix === null) {
        fwrite(STDERR, sprintf(
            "famille '%s' sans prefixe de sitemap autorise (route %s) -- ligne ignoree, verifier apply_seo_batch.php (R4)\n",
            $row['family'],
            $row['route_path'],
        ));

        continue;
    }

    if (!str_starts_with($row['sitemap_fragment'], $expectedPrefix . '-')) {
        fwrite(STDERR, sprintf(
            "fragment '%s' ne correspond pas au prefixe attendu '%s-' pour la famille '%s' (route %s)\n",
            $row['sitemap_fragment'],
            $expectedPrefix,
            $row['family'],
            $row['route_path'],
        ));
        exit(1);
    }

    if ($row['sitemap_fragment'] !== $currentFragment) {
        $flushFragment($currentFragment, $currentRows);
        $totalUrls += count($currentRows);
        $currentRows = [];
        $currentFragment = $row['sitemap_fragment'];
    }

    $currentRows[] = $row;
}

$flushFragment($currentFragment, $currentRows);
$totalUrls += count($currentRows);

sort($fragmentFiles);

$indexXml = new XMLWriter();
$indexXml->openMemory();
$indexXml->setIndent(true);
$indexXml->startDocument('1.0', 'UTF-8');
$indexXml->startElement('sitemapindex');
$indexXml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

foreach ($fragmentFiles as $fileName) {
    $indexXml->startElement('sitemap');
    $indexXml->writeElement('loc', $baseUrl . '/sitemaps/' . $fileName);
    $indexXml->endElement();
}

$indexXml->endElement();
$indexXml->endDocument();

file_put_contents($publicDir . '/sitemap-index.xml', $indexXml->outputMemory());

printf("sitemap-index.xml : %d fragment(s), %d URL au total\n", count($fragmentFiles), $totalUrls);
