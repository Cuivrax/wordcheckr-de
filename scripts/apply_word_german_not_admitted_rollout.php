<?php

declare(strict_types=1);

/**
 * Applique la famille word_german_not_admitted (236 909 mots, storage/dictionary_de.sqlite,
 * is_german=1 AND is_admitted=0) dans storage/seo_de.sqlite, EN UN SEUL LOT.
 *
 * D-DE-029 (docs/DECISIONS.md) : decision explicite du proprietaire du produit d'ouvrir cette
 * famille au complet, meme raisonnement que D-017 (FR) / ES-024 (ES) -- le site repond a deux
 * questions symetriques ("dieses Wort ist gueltig ?"/"dieses Wort ist ein echtes deutsches
 * Wort, aber nicht gueltig ?"), un visiteur ne sait jamais laquelle s'applique avant de
 * chercher sur Google.
 *
 * MEME DISCIPLINE que scripts/apply_word_admitted_rollout.php : chaque ligne validee par des
 * assertions mecaniques (assertRow() ci-dessous, meme role que seoValidateBatchRow() sur le
 * depot espagnol cousin -- ce depot n'a pas de scripts/seo_batch_rules.php partage, la
 * discipline reste identique, juste dupliquee ligne a ligne comme apply_word_admitted_rollout.php
 * le fait deja pour word_admitted), en flux (curseur PDO, jamais un tableau de 236 909 lignes
 * en memoire), plafond Family::MAX_BATCH_SIZE_GERMAN_NOT_ADMITTED verifie explicitement.
 *
 * Maillage interne (meme mecanisme que D-017/ES-024, verifie ici aussi) :
 * App\Search\TermLookup::neighbours() (navigation mot precedent/suivant) parcourt DEJA la
 * chaine alphabetique complete de la table terms, admis ET non admis confondus -- chaque mot
 * non admis recoit donc au moins 2 liens internes reels des sa mise en ligne.
 *
 * result_count reste NULL (R5 ne s'applique pas : /wort/{mot} n'a pas de notion de "nombre de
 * resultats", meme raisonnement que word_admitted).
 *
 * Usage :
 *     php scripts/apply_word_german_not_admitted_rollout.php --dry-run
 *     php scripts/apply_word_german_not_admitted_rollout.php
 *     php scripts/apply_word_german_not_admitted_rollout.php --reset-family
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "scripts/apply_word_german_not_admitted_rollout.php ne s'execute qu'en CLI, hors ligne.\n");
    exit(1);
}

ini_set('memory_limit', '512M');

$root = dirname(__DIR__);
require_once $root . '/app/Seo/Family.php';

use App\Seo\Family;

$args = array_slice($argv, 1);
$resetFamily = in_array('--reset-family', $args, true);
$dryRun = in_array('--dry-run', $args, true);

if ($dryRun && $resetFamily) {
    fwrite(STDERR, "--dry-run et --reset-family sont incompatibles.\n");
    exit(1);
}

$dictPath = getenv('SCRABBLE_DICTIONARY_DB_PATH') ?: $root . '/storage/dictionary_de.sqlite';
$seoPath = getenv('SCRABBLE_SEO_DB_PATH') ?: $root . '/storage/seo_de.sqlite';

if (!is_file($dictPath)) {
    fwrite(STDERR, "dictionnaire introuvable : {$dictPath}\n");
    exit(1);
}

if (!is_file($seoPath)) {
    fwrite(STDERR, "registre introuvable, lancer d'abord : php scripts/build_seo_registry.php\n");
    exit(1);
}

$dict = new PDO('sqlite:' . $dictPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$dict->exec('PRAGMA query_only = ON');

$seo = new PDO('sqlite:' . $seoPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

const FRAGMENT_SIZE = 40_000;
const FAMILY = 'word_german_not_admitted';
const NOTES = 'Echtes deutsches Wort, ermittelt ueber kaikki.org/dewiktionary (is_german=1, '
    . 'D-DE-029), nicht in enz/german-wordlist oder hippler/german-wordlist enthalten. Nuetzliche '
    . 'Seite zur Beantwortung der Frage "ist dieses Wort gueltig ?" von der negativen Seite aus '
    . '(D-017 des franzoesischen Standorts, gleiche Ueberlegung : ein Besucher weiss nicht im '
    . 'Voraus, welcher der beiden Faelle zutrifft). Erreicht ueber die alphabetische '
    . 'Wort-Navigation (App\\Search\\TermLookup::neighbours(), vollstaendige Kette ueber die '
    . 'gesamte terms-Tabelle, admittiert und nicht admittiert).';

/**
 * Meme role que assertRow() de scripts/apply_word_admitted_rollout.php, adapte a cette
 * famille (R6 en plus : plafond global verifie par l'appelant, comptage passe par reference).
 */
function assertRow(string $routePath, string $family, string $canonicalPath, ?int $resultCount, string $notes): void
{
    if (!str_starts_with($routePath, '/')) {
        fwrite(STDERR, "R1 viole : route_path '{$routePath}' ne commence pas par '/'\n");
        exit(1);
    }
    if (!Family::isValid($family)) {
        fwrite(STDERR, "R1 viole : famille '{$family}' inconnue\n");
        exit(1);
    }
    if ($canonicalPath !== $routePath) {
        fwrite(STDERR, "R3 viole : canonical_path '{$canonicalPath}' != route_path '{$routePath}'\n");
        exit(1);
    }
    if (Family::forbidsSitemap($family)) {
        fwrite(STDERR, "R4 viole : famille '{$family}' interdite de sitemap/index\n");
        exit(1);
    }
    if ($resultCount === 0) {
        fwrite(STDERR, "R5 viole : result_count = 0 avec 'index,follow'\n");
        exit(1);
    }
    if (trim($notes) === '') {
        fwrite(STDERR, "R6/R7 viole : note de maillage/attestation vide\n");
        exit(1);
    }
}

if ($resetFamily) {
    $deleted = $seo->prepare('DELETE FROM registry WHERE family = ?');
    $deleted->execute([FAMILY]);
    echo "--reset-family : {$deleted->rowCount()} ligne(s) existante(s) de word_german_not_admitted supprimee(s)\n";
}

$addedAt = gmdate('Y-m-d');
$batchId = 'word_german_not_admitted-full-' . $addedAt;

$select = $dict->query('SELECT normalized FROM terms WHERE is_german = 1 AND is_admitted = 0 ORDER BY normalized');

$insert = $dryRun ? null : $seo->prepare(
    'INSERT OR REPLACE INTO registry '
    . '(route_path, family, robots, canonical_path, sitemap_fragment, batch_id, result_count, notes, added_at) '
    . 'VALUES (?, ?, "index,follow", ?, ?, ?, NULL, ?, ?)'
);

$count = 0;
$fragmentIndex = 1;
$countInFragment = 0;
$maxBatchSize = Family::MAX_BATCH_SIZE_GERMAN_NOT_ADMITTED;

if ($dryRun) {
    echo "--dry-run : aucune ecriture, storage/seo_de.sqlite lu uniquement (transaction jamais ouverte).\n";
} else {
    $seo->beginTransaction();
}

foreach ($select as $row) {
    $slug = mb_strtolower($row['normalized'], 'UTF-8');
    $routePath = "/wort/{$slug}";

    assertRow($routePath, FAMILY, $routePath, null, NOTES);

    $count++;

    if ($count > $maxBatchSize) {
        if (!$dryRun) {
            $seo->rollBack();
        }
        fwrite(STDERR, "lot refuse : plafond Family::MAX_BATCH_SIZE_GERMAN_NOT_ADMITTED depasse ({$count} > {$maxBatchSize})\n");
        exit(1);
    }

    if ($countInFragment >= FRAGMENT_SIZE) {
        $fragmentIndex++;
        $countInFragment = 0;
    }
    $countInFragment++;
    $fragment = sprintf('invalid-%04d', $fragmentIndex);

    if (!$dryRun) {
        $insert->execute([$routePath, FAMILY, $routePath, $fragment, $batchId, NOTES, $addedAt]);
    }
}

if (!$dryRun) {
    $seo->commit();
}

echo ($dryRun ? "[DRY-RUN] lot '{$batchId}' validee" : "lot '{$batchId}' applique") . " : {$count} ligne(s)\n";

$totalCount = (int) $seo->query('SELECT COUNT(*) c FROM registry')->fetch()['c'];
$indexCount = (int) $seo->query("SELECT COUNT(*) c FROM registry WHERE robots = 'index,follow'")->fetch()['c'];
$familyCount = (int) $seo->query("SELECT COUNT(*) c FROM registry WHERE family = 'word_german_not_admitted'")->fetch()['c'];

echo "registre " . ($dryRun ? 'ACTUEL (inchange par ce dry-run)' : 'apres application') . " : {$totalCount} lignes au total, "
    . "{$indexCount} en 'index,follow', {$familyCount} en word_german_not_admitted\n";

if ($dryRun) {
    echo 'registre PROJETE si ce lot etait applique (non ecrit) : ' . ($totalCount + $count)
        . ' lignes au total, ' . ($indexCount + $count) . " en 'index,follow'\n";
}
