<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\SuffixExtensionLinksBuilder;
use Tests\Support\Assert;

/**
 * App\Search\SuffixExtensionLinksBuilder (correctif I2, audit NO GO 2026-08-31) : meme bug et
 * meme correctif que App\Search\PrefixExtensionLinksBuilder (I1) -- `strlen($suffix)` compte des
 * OCTETS, pas des caracteres. Pour un suffixe Ü (2 octets en UTF-8), `strlen('Ü') === 2` au lieu
 * de 1, faisant calculer un `$listType` ('suffixN') decale d'un palier (`'suffix3'` au lieu de
 * `'suffix2'`) : la section "Suffix Ü Fortsetzen" restait vide alors que list_counts contient
 * reellement les donnees pour ce suffixe. `mb_strlen($suffix, 'UTF-8')` corrige ce comptage.
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $connection = new Connection($dbPath);
    $pdo = $connection->pdo();
    $builder = new SuffixExtensionLinksBuilder($connection);

    // --- Cas de regression direct : un suffixe Ü (1 caractere, 2 octets UTF-8). ---
    $expectedCount = (int) $pdo->query("SELECT COUNT(*) c FROM list_counts WHERE list_type = 'suffix2' AND list_key LIKE '_Ü'")->fetch()['c'];
    Assert::true($expectedCount > 0, "list_counts doit contenir des extensions suffix2 pour 'Ü' (donnee prealable au test)");

    $result = $builder->build('Ü');
    Assert::same(1, $result->queryCount, 'build() doit rester a 1 seule requete SQLite');
    Assert::same(
        $expectedCount,
        count($result->links),
        "SuffixExtensionLinksBuilder::build('Ü') doit renvoyer autant de liens que list_counts (bug strlen() -> mb_strlen())",
    );
    Assert::true(count($result->links) > 0, "la section \"Suffix Ü Fortsetzen\" ne doit plus etre vide");

    foreach ($result->links as $link) {
        Assert::true(str_ends_with($link['suffix'], 'Ü'), 'chaque extension doit se terminer par le suffixe Ü demande');
        Assert::same(2, mb_strlen($link['suffix'], 'UTF-8'), 'extension attendue a 2 caracteres (suffixe 1 lettre + 1)');
    }

    // --- Cas temoin ASCII : un suffixe ordinaire d'une lettre continue de fonctionner ---
    // --- identiquement apres le correctif (pas de regression sur le cas commun). ---
    $expectedAscii = (int) $pdo->query("SELECT COUNT(*) c FROM list_counts WHERE list_type = 'suffix2' AND list_key LIKE '_A'")->fetch()['c'];
    $resultAscii = $builder->build('A');
    Assert::same($expectedAscii, count($resultAscii->links), "le suffixe ASCII 'A' doit rester inchange par le correctif");
};
