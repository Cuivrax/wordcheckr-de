<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\ExploreHubBuilder;
use Tests\Support\Assert;

/**
 * App\Search\ExploreHubBuilder (correctif C1, audit NO GO 2026-08-31) : la page hub /woerter lit
 * list_counts par une requete PREPAREE, bornee a `WHERE list_type IN (?, ?, ?) LIMIT 100` --
 * jamais un `SELECT * FROM list_counts` non prepare et non borne (la SEULE occurrence de
 * `->query()` de tout `app/` avant ce correctif, contre 123 471 lignes reelles, 19 list_type,
 * alors que 72 seulement sont utiles au hub). Voir le rapport AFTER de cette tache pour
 * l'EXPLAIN QUERY PLAN et le benchmark avant/apres (93-109 ms -> < 1 ms).
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $connection = new Connection($dbPath);
    $pdo = $connection->pdo();

    // --- Garde de regression sur le CODE lui-meme : la requete list_counts doit rester une ---
    // --- instruction preparee et bornee, jamais un ->query() nu comme avant ce correctif. ---
    $source = file_get_contents(__DIR__ . '/../../app/Search/ExploreHubBuilder.php');
    Assert::true(is_string($source), 'lecture du source ExploreHubBuilder.php');
    Assert::true(
        !str_contains($source, '->pdo()->query('),
        'ExploreHubBuilder ne doit plus utiliser PDO::query() (non prepare) pour lire list_counts',
    );
    Assert::true(
        str_contains($source, '->pdo()->prepare('),
        'ExploreHubBuilder doit lire list_counts via une instruction preparee',
    );
    Assert::true(
        str_contains($source, 'LIMIT'),
        'ExploreHubBuilder doit borner sa requete list_counts par un LIMIT explicite',
    );

    // --- Comportement fonctionnel : les comptes recalcules directement depuis list_counts ---
    // --- (independamment du builder) doivent correspondre exactement aux listes rendues. ---
    $expectedLength = (int) $pdo->query("SELECT COUNT(*) c FROM list_counts WHERE list_type = 'length'")->fetch()['c'];
    $expectedStart = (int) $pdo->query("SELECT COUNT(*) c FROM list_counts WHERE list_type = 'start'")->fetch()['c'];
    $expectedEnd = (int) $pdo->query("SELECT COUNT(*) c FROM list_counts WHERE list_type = 'end'")->fetch()['c'];

    // Alphabet allemand : A-Z (26) + Ä/Ö/Ü (3) = 29 lettres de debut ET de fin -- rappel du
    // rapport AFTER, verifie ici comme une propriete stable plutot que suppose.
    Assert::same(29, $expectedStart, "29 lettres 'start' attendues (A-Z + Ä/Ö/Ü)");
    Assert::same(29, $expectedEnd, "29 lettres 'end' attendues (A-Z + Ä/Ö/Ü)");
    Assert::same(14, $expectedLength, '14 longueurs attendues (2 a 15 lettres)');

    $hub = (new ExploreHubBuilder($connection))->build();

    Assert::same(1, $hub->queryCount, 'ExploreHubBuilder::build() doit rester a 1 seule requete SQLite');
    Assert::same($expectedLength, count($hub->byLength), 'byLength doit refleter exactement list_counts');
    Assert::same($expectedStart, count($hub->byStart), 'byStart doit refleter exactement list_counts');
    Assert::same($expectedEnd, count($hub->byEnd), 'byEnd doit refleter exactement list_counts');

    // Chaque entree porte une URL canonique non vide et un compte strictement positif (aucune
    // ligne degeneree/vide ne devrait jamais atteindre le hub).
    foreach ([$hub->byLength, $hub->byStart, $hub->byEnd] as $section) {
        foreach ($section as $entry) {
            Assert::true($entry['count'] > 0, 'chaque entree du hub doit avoir un compte positif');
            Assert::true(str_starts_with($entry['url'], '/woerter/'), 'URL canonique attendue sous /woerter/');
        }
    }

    // byLength trie par longueur croissante (2, 3, 4...) -- comportement deja garanti par
    // build(), verifie explicitement.
    for ($i = 1; $i < count($hub->byLength); $i++) {
        Assert::true($hub->byLength[$i - 1]['length'] < $hub->byLength[$i]['length'], 'byLength doit rester trie par longueur croissante');
    }
};
