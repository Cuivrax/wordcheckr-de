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
    // home (racine '/' + hub de navigation '/woerter') : 2 pages au total, D-DE-013.
    'home' => 'core',
    // word_admitted (/wort/{mot}, mots admis enz/german-wordlist + hippler/german-wordlist) :
    // D-DE-013.
    'word_admitted' => 'words',
    // word_list_length (/woerter/{N}-buchstaben) : SEULEMENT 7 et 9 buchstaben, D-DE-013 --
    // les 12 autres longueurs restent noindex,follow (aucun lien entrant reel demontre tant
    // que list_counts reste vide).
    'word_list_length' => 'letters',
    // rack, contenant/avec/sans/motif, et toute famille beginnend-mit/endend-mit/position/
    // combined future : absents volontairement -- soit App\Seo\Family::NEVER_SITEMAP (jamais
    // de prefixe), soit non encore ouverts (D-DE-013).
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
