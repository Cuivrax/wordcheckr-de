<?php

declare(strict_types=1);

use App\Seo\Registry;
use Tests\Support\Assert;

/**
 * App\Seo\Registry : la route de test ne touche jamais storage/seo_de.sqlite reel -- chaque
 * cas construit sa propre base temporaire depuis app/Seo/schema.sql, effacee a la fin (meme
 * discipline que tests/Database/ConnectionTest.php pour ne jamais laisser de trace).
 *
 * Contrat central verifie ici (meme contrat que D-005 sur le depot francais d'origine) : une
 * route absente du registre reste noindex,follow, que ce soit parce que le FICHIER est absent
 * ou parce que la LIGNE est absente -- les deux cas doivent produire exactement le meme
 * SeoMeta.
 */
return function (): void {
    $schemaPath = __DIR__ . '/../../app/Seo/schema.sql';
    Assert::true(is_file($schemaPath), 'schema introuvable : ' . $schemaPath);

    $baseUrl = 'https://exemple-test.invalid';

    // --- Cas 1 : fichier de base absent -- comportement par defaut, jamais une erreur. ---
    $missingPath = sys_get_temp_dir() . '/scrabble_seo_de_test_missing_' . bin2hex(random_bytes(4)) . '.sqlite';
    Assert::true(!is_file($missingPath));

    $registryMissing = new Registry($missingPath, $baseUrl);
    $metaMissing = $registryMissing->resolve('/wort/schreiben');

    Assert::same('noindex,follow', $metaMissing->robots);
    Assert::same($baseUrl . '/wort/schreiben', $metaMissing->canonicalUrl);
    Assert::true(!$metaMissing->inSitemap);
    Assert::true(!is_file($missingPath), 'resolve() ne doit jamais creer de fichier (lecture seule)');

    // --- Cas 2 : base presente, mais aucune ligne pour ce chemin -- meme comportement. ---
    $dbPath = sys_get_temp_dir() . '/scrabble_seo_de_test_' . bin2hex(random_bytes(4)) . '.sqlite';

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $schema = file_get_contents($schemaPath);
    Assert::true($schema !== false);
    $pdo->exec($schema);

    try {
        $registryEmpty = new Registry($dbPath, $baseUrl);
        $metaAbsent = $registryEmpty->resolve('/wort/unbekannt');

        Assert::same('noindex,follow', $metaAbsent->robots);
        Assert::same($baseUrl . '/wort/unbekannt', $metaAbsent->canonicalUrl);
        Assert::true(!$metaAbsent->inSitemap);

        // --- Cas 3 : ligne explicite index,follow, self-canonique, dans un sitemap. ---
        $pdo->prepare(
            'INSERT INTO registry (route_path, family, robots, canonical_path, sitemap_fragment, batch_id, result_count, notes, added_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute(['/wort/schreiben', 'word_admitted', 'index,follow', '/wort/schreiben', 'words-0001', 'test-batch', null, 'test', '2026-08-29']);

        $registry = new Registry($dbPath, $baseUrl);
        $meta = $registry->resolve('/wort/schreiben');

        Assert::same('index,follow', $meta->robots);
        Assert::same($baseUrl . '/wort/schreiben', $meta->canonicalUrl);
        Assert::true($meta->inSitemap);

        // --- Cas 4 : ligne noindex avec canonical pointant ailleurs (alias documente). ---
        $pdo->prepare(
            'INSERT INTO registry (route_path, family, robots, canonical_path, sitemap_fragment, batch_id, result_count, notes, added_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute(['/woerter/7-buchstaben/page/1', 'word_list_length', 'noindex,follow', '/woerter/7-buchstaben', null, 'test-batch', null, 'alias page 1', '2026-08-29']);

        $aliasMeta = $registry->resolve('/woerter/7-buchstaben/page/1');
        Assert::same('noindex,follow', $aliasMeta->robots);
        Assert::same($baseUrl . '/woerter/7-buchstaben', $aliasMeta->canonicalUrl);

        // --- resolveMany() : plusieurs chemins en une seule requete, chemins absents omis. ---
        $many = $registry->resolveMany(['/wort/schreiben', '/wort/absent']);
        Assert::same(1, count($many));
        Assert::true(isset($many['/wort/schreiben']));
        Assert::same('index,follow', $many['/wort/schreiben']->robots);

        Assert::same([], $registry->resolveMany([]));
    } finally {
        // Registry ouvre sa propre connexion PDO vers $dbPath (cachee en interne, distincte de
        // $pdo ci-dessus) : tant qu'un objet Registry reste en portee, Windows garde le
        // descripteur de fichier ouvert et unlink() echoue avec "Resource temporarily
        // unavailable" -- meme constat que les depots francais/espagnol cousins, corrige en
        // liberant explicitement toutes les references avant de supprimer le fichier temporaire.
        unset($pdo, $registryMissing, $registryEmpty, $registry);

        if (is_file($dbPath)) {
            unlink($dbPath);
        }
    }
};
