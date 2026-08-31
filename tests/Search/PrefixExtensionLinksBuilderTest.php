<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\PrefixExtensionLinksBuilder;
use Tests\Support\Assert;

/**
 * App\Search\PrefixExtensionLinksBuilder (correctif I1, audit NO GO 2026-08-31) :
 * `strlen($prefix)` compte des OCTETS, pas des caracteres -- pour un prefixe Ä/Ö/Ü (2 octets en
 * UTF-8), `strlen('Ä') === 2` au lieu de 1, faisant calculer un `$listType` ('prefixN') decale
 * d'un palier (`'prefix3'` au lieu de `'prefix2'` pour un prefixe Ä d'une seule lettre) : la
 * section "Präfix Ä Fortsetzen" restait vide alors que list_counts contient reellement les
 * donnees pour ce prefixe. `mb_strlen($prefix, 'UTF-8')` corrige ce comptage.
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $connection = new Connection($dbPath);
    $pdo = $connection->pdo();
    $builder = new PrefixExtensionLinksBuilder($connection);

    // --- Cas de regression direct : un prefixe Ä (1 caractere, 2 octets UTF-8). ---
    $expectedCount = (int) $pdo->query("SELECT COUNT(*) c FROM list_counts WHERE list_type = 'prefix2' AND list_key LIKE 'Ä_'")->fetch()['c'];
    Assert::true($expectedCount > 0, "list_counts doit contenir des extensions prefix2 pour 'Ä' (donnee prealable au test)");

    $result = $builder->build('Ä');
    Assert::same(1, $result->queryCount, 'build() doit rester a 1 seule requete SQLite');
    Assert::same(
        $expectedCount,
        count($result->links),
        "PrefixExtensionLinksBuilder::build('Ä') doit renvoyer autant de liens que list_counts (bug strlen() -> mb_strlen())",
    );
    Assert::true(count($result->links) > 0, "la section \"Präfix Ä Fortsetzen\" ne doit plus etre vide");

    foreach ($result->links as $link) {
        Assert::true(str_starts_with($link['prefix'], 'Ä'), 'chaque extension doit commencer par le prefixe Ä demande');
        Assert::same(2, mb_strlen($link['prefix'], 'UTF-8'), 'extension attendue a 2 caracteres (prefixe 1 lettre + 1)');
    }

    // --- Cas temoin ASCII (Ö/Ü non concernes ici) : un prefixe ordinaire d'une lettre continue ---
    // --- de fonctionner identiquement apres le correctif (pas de regression sur le cas commun). ---
    $expectedAscii = (int) $pdo->query("SELECT COUNT(*) c FROM list_counts WHERE list_type = 'prefix2' AND list_key LIKE 'A_'")->fetch()['c'];
    $resultAscii = $builder->build('A');
    Assert::same($expectedAscii, count($resultAscii->links), "le prefixe ASCII 'A' doit rester inchange par le correctif");
};
