<?php

declare(strict_types=1);

/**
 * Applique la famille word_admitted dans storage/seo_de.sqlite -- OUTIL PRÊT, PAS ENCORE
 * EXÉCUTÉ CONTRE LE REGISTRE RÉEL À CE STADE (docs/DECISIONS.md D-DE-013).
 *
 * CORRECTION (2026-08-29, revue independante sur le lot equivalent du depot espagnol cousin,
 * NO GO) : la version initiale de ce script inserait `robots = 'index,follow'` en dur dans le
 * SQL prepare(), SANS AUCUNE des regles R1/R3/R4/R5/R7 de scripts/apply_seo_batch.php appliquees
 * mecaniquement -- seulement affirmees en prose ("respectees par construction"). Corrige : les
 * memes controles sont maintenant des ASSERTIONS EN CODE (assertRow() ci-dessous), executees
 * pour CHAQUE ligne avant ecriture -- une ligne qui les violerait interrompt le script (exit 1)
 * plutot que d'etre silencieusement acceptee. N'UTILISE TOUJOURS PAS
 * scripts/apply_seo_batch.php tel quel (var_export d'un tableau PHP a 590 856 lignes epuise la
 * memoire CLI par defaut, meme constat que le depot espagnol cousin) -- mais les GARANTIES sont
 * desormais les memes, verifiees mecaniquement, pas seulement documentees.
 *
 * DEUXIEME CORRECTION, plus importante (meme revue) : ce script REFUSE PAR DEFAUT d'ecrire plus
 * de SAFE_DEFAULT_CEILING lignes -- contrainte de role dure ("never propose indexing an entire
 * word family at once without discussing batch size first"). Le depot francais d'origine a
 * ouvert sa famille equivalente (838 180 lignes) en un seul lot, mais SEULEMENT apres une
 * DECISION EXPLICITE DU PROPRIETAIRE DU PRODUIT documentee (D-017, "contre l'avis initial de
 * l'agent seo-registry") -- pas une decision unilaterale de l'agent. Aucune decision equivalente
 * n'a ete prise ici : --confirm-full-rollout est desormais OBLIGATOIRE pour depasser le plafond
 * de securite, et son absence n'est pas un defaut a corriger silencieusement mais l'etat ATTENDU
 * de ce lot tant que le proprietaire du produit n'a pas tranche le dimensionnement (voir le
 * rapport de tache de l'agent seo-registry pour les options chiffrees proposees).
 *
 * TROISIEME PROBLEME, INDEPENDANT DE CE SCRIPT, BLOQUANT A LUI SEUL (meme revue) : le gabarit
 * <title> de app/View/word.php ('Ja, %s Ist Ein Gültiges Scrabble-Wort (%d Punkte) | WORD
 * CHECKR') depasse 60 caracteres pour LA TOTALITE des 590 856 mots admis (100%, mesure directe,
 * pas un echantillon), et 70 caracteres pour 379 079 d'entre eux (64%) -- un defaut de gabarit,
 * hors perimetre de l'agent seo-registry (app/View/, CLAUDE.md : "ne pas modifier les gabarits,
 * signaler le besoin"). Indexer 590 856 pages avec un <title> systematiquement tronque dans les
 * resultats de recherche serait un defaut de QUALITE a l'echelle du site, pas seulement un
 * detail cosmetique -- ce script reste donc INAPPLIQUE tant que ce correctif n'a pas ete route
 * vers l'agent frontend et livre, MEME SI le proprietaire du produit tranche un plafond de lot.
 *
 * Insertion directe en flux (curseur PDO, jamais de fetchAll) -- indispensable a cette echelle,
 * memoire CLI par defaut (128 Mo) sinon epuisee des le chargement d'un tableau de 590 856 lignes.
 *
 * Usage :
 *     php scripts/apply_word_admitted_rollout.php --dry-run
 *         Simule le lot complet (memes assertions R1/R3/R4/R5/R7, ORDER BY, decoupage en
 *         fragments) SANS ecrire une seule ligne dans storage/seo_de.sqlite -- imprime les
 *         comptes attendus. Sans danger, ne touche jamais le registre.
 *
 *     php scripts/apply_word_admitted_rollout.php --limit=N
 *         Applique REELLEMENT les N premiers mots (ordre alphabetique) -- pour un premier
 *         tranche explicitement dimensionnee une fois que le proprietaire du produit a choisi
 *         N. N doit rester <= SAFE_DEFAULT_CEILING, sinon --confirm-full-rollout est requis en
 *         plus (voir plus bas).
 *
 *     php scripts/apply_word_admitted_rollout.php --confirm-full-rollout
 *         Applique la famille au complet (590 856 lignes) -- REFUSE sans ce drapeau explicite.
 *         Le nom du drapeau n'est pas cosmetique : il documente qu'une decision de dimensionnement
 *         a ete prise en amont, pas prise par ce script lui-meme.
 *
 * Sans aucun argument : affiche l'usage et quitte en erreur (aucune valeur par defaut qui
 * ecrirait quoi que ce soit) -- un lancement sans reflexion ne doit jamais silencieusement
 * appliquer le lot complet.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "scripts/apply_word_admitted_rollout.php ne s'execute qu'en CLI, hors ligne.\n");
    exit(1);
}

// Plafond de securite (role seo-registry, contrainte dure de dimensionnement de lot) : au-dela,
// --confirm-full-rollout est obligatoire. Valeur choisie pour couvrir une "premiere tranche
// pilote" raisonnable (ex. les mots de 2 a 6 lettres = 70 967 mots, voir le rapport de tache)
// sans jamais laisser passer la famille complete par accident.
const SAFE_DEFAULT_CEILING = 100_000;

require_once dirname(__DIR__) . '/app/Seo/Family.php';

use App\Seo\Family;

$args = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $args, true);
$confirmFull = in_array('--confirm-full-rollout', $args, true);
$limit = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, strlen('--limit='));
    }
}

if (!$dryRun && $limit === null && !$confirmFull) {
    fwrite(STDERR, <<<TXT
        usage :
          php scripts/apply_word_admitted_rollout.php --dry-run
          php scripts/apply_word_admitted_rollout.php --limit=N              (N <= 100000)
          php scripts/apply_word_admitted_rollout.php --confirm-full-rollout (590 856 lignes)

        Aucun argument fourni -- refuse d'appliquer quoi que ce soit par defaut (jamais
        d'indexation de masse par omission). Voir le docblock de ce fichier et le rapport de
        tache de l'agent seo-registry pour les options chiffrees.

        TANT QUE app/View/word.php n'a pas ete corrige (titre > 60 caracteres pour 100% des mots
        admis, voir le rapport de tache), aucune valeur de --limit/--confirm-full-rollout ne
        devrait etre utilisee en dehors de --dry-run -- ce script le permet techniquement (les
        deux defauts sont independants et corriges separement) mais le proprietaire du produit
        doit trancher les deux, pas seulement l'un des deux.

        TXT);
    exit(1);
}

if ($limit !== null && $limit > SAFE_DEFAULT_CEILING && !$confirmFull) {
    fwrite(STDERR, sprintf(
        "--limit=%d depasse le plafond de securite (%d) -- ajouter --confirm-full-rollout pour confirmer explicitement ce dimensionnement\n",
        $limit,
        SAFE_DEFAULT_CEILING,
    ));
    exit(1);
}

$root = dirname(__DIR__);
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

$seo = $dryRun ? null : new PDO('sqlite:' . $seoPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

const FRAGMENT_SIZE = 40_000;
const FAMILY = 'word_admitted';
const NOTES = 'Admittiertes Wort (enz/german-wordlist und/oder hippler/german-wordlist, CC0-1.0, '
    . 'D-DE-006). Erreicht ueber /woerter/{N}-buchstaben (bereits indexiert, gleiches Los), die '
    . 'alphabetische Wort-Navigation (App\\Search\\TermLookup::neighbours(), vollstaendige Kette '
    . 'ueber die gesamte terms-Tabelle) und bis zu 10 Kategorien interner Relationen zu anderen '
    . '/wort/...-Seiten (App\\Search\\RelationsFinder).';

/**
 * Meme role que les regles R1/R3/R4/R5/R7 de scripts/apply_seo_batch.php, appliquees ici
 * MECANIQUEMENT ligne par ligne plutot que documentees en prose (correction du 2026-08-29,
 * voir le docblock de fichier). Interrompt immediatement (exit 1) si une ligne generee violerait
 * l'une d'entre elles -- ne devrait jamais se produire vu comment $routePath/$family/$notes sont
 * construits ci-dessous, mais l'assertion reste active en permanence, pas seulement en
 * developpement (defense en profondeur, pas un `assert()` desactivable).
 */
function assertRow(string $routePath, string $family, string $canonicalPath, ?int $resultCount, string $notes): void
{
    // R1
    if (!str_starts_with($routePath, '/')) {
        fwrite(STDERR, "R1 viole : route_path '{$routePath}' ne commence pas par '/'\n");
        exit(1);
    }

    if (!Family::isValid($family)) {
        fwrite(STDERR, "R1 viole : famille '{$family}' inconnue\n");
        exit(1);
    }

    // R3 -- cette famille n'ecrit jamais d'alias, route_path === canonical_path toujours.
    if ($canonicalPath !== $routePath) {
        fwrite(STDERR, "R3 viole : canonical_path '{$canonicalPath}' != route_path '{$routePath}'\n");
        exit(1);
    }

    // R4 -- word_admitted n'est jamais dans Family::NEVER_SITEMAP, verifie quand meme.
    if (Family::forbidsSitemap($family)) {
        fwrite(STDERR, "R4 viole : famille '{$family}' interdite de sitemap/index\n");
        exit(1);
    }

    // R5 -- cette famille n'a pas de notion de result_count (NULL partout, comme /wort/{mot}).
    if ($resultCount === 0) {
        fwrite(STDERR, "R5 viole : result_count = 0 avec 'index,follow'\n");
        exit(1);
    }

    // R7
    if (trim($notes) === '') {
        fwrite(STDERR, "R7 viole : note de maillage vide\n");
        exit(1);
    }
}

$addedAt = gmdate('Y-m-d');
$batchId = 'word_admitted-' . ($dryRun ? 'dry-run' : ($limit !== null ? "tranche{$limit}" : 'full')) . '-' . $addedAt;

$sql = 'SELECT normalized FROM terms WHERE is_admitted = 1 ORDER BY normalized';

if ($limit !== null) {
    $sql .= ' LIMIT ' . $limit;
}

$statement = $dict->query($sql);

$insert = $seo?->prepare(
    'INSERT OR REPLACE INTO registry '
    . '(route_path, family, robots, canonical_path, sitemap_fragment, batch_id, result_count, notes, added_at) '
    . 'VALUES (?, ?, "index,follow", ?, ?, ?, NULL, ?, ?)'
);

if (!$dryRun) {
    $seo->beginTransaction();
}

$count = 0;
$fragmentIndex = 1;
$countInFragment = 0;

foreach ($statement as $row) {
    if ($countInFragment >= FRAGMENT_SIZE) {
        $fragmentIndex++;
        $countInFragment = 0;
    }
    $countInFragment++;
    $count++;

    $slug = mb_strtolower($row['normalized'], 'UTF-8');
    $routePath = "/wort/{$slug}";
    $fragment = sprintf('words-%04d', $fragmentIndex);

    assertRow($routePath, FAMILY, $routePath, null, NOTES);

    if (!$dryRun) {
        $insert->execute([$routePath, FAMILY, $routePath, $fragment, $batchId, NOTES, $addedAt]);
    }
}

if (!$dryRun) {
    $seo->commit();
}

printf(
    "%s : %d ligne(s) %s (lot '%s')\n",
    $dryRun ? 'simulation (--dry-run, rien ecrit)' : 'appliquee(s)',
    $count,
    $dryRun ? '' : 'ecrites',
    $batchId,
);

if (!$dryRun) {
    $totalCount = $seo->query('SELECT COUNT(*) c FROM registry')->fetch()['c'];
    $indexCount = $seo->query("SELECT COUNT(*) c FROM registry WHERE robots = 'index,follow'")->fetch()['c'];

    echo "registre apres application : {$totalCount} lignes au total, {$indexCount} en 'index,follow'\n";
}
