<?php

declare(strict_types=1);

use Tests\Support\Assert;

/**
 * Garde-fou (herite du site francais, D-025bis/D-021) : plusieurs correctifs de
 * performance dependent d'un index precis existant reellement dans
 * storage/dictionary_de.sqlite, avec ses statistiques ANALYZE a jour -- sans quoi une
 * regression peut revenir en silence (base copiee d'avant le correctif, reconstruction
 * partielle) sans qu'aucun test applicatif ne le detecte : WordListSolverTest.php verifie
 * le resultat et $queryCount (independants du plan choisi par SQLite), jamais le plan
 * lui-meme.
 *
 * Verifie directement sqlite_master (l'index existe) ET sqlite_stat1 (ANALYZE a tourne
 * dessus) pour chaque index du schema allemand simplifie (schema.sql) -- liste adaptee :
 * idx_terms_ods8/idx_terms_ods9 (double lexique francais) n'ont pas d'equivalent, fusionnes
 * en idx_terms_admitted_normalized/idx_terms_length_admitted_normalized.
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $requiredIndexes = [
        'idx_terms_length_normalized' => 'longueur+prefixe (/mots/{N}-lettres et ses paginations)',
        'idx_terms_signature' => 'anagrammes exactes et point de depart des anagrammes +-1 lettre',
        'idx_terms_reversed' => 'suffixe seul (/mots/terminant/...)',
        'idx_terms_length_reversed' => 'longueur+terminant combines, herite : jusqu\'a 1 779 ms mesure sans lui cote francais',
        'idx_terms_length_admitted_normalized' => 'filtre statut EXACT, herite : jusqu\'a 1 286 ms mesure sans lui cote francais',
        'idx_terms_admitted_normalized' => 'filtre statut BORNE sans ancrage de longueur',
        'idx_terms_length_score_normalized' => 'tri par points EXACT, herite : jusqu\'a 870 ms mesure sans lui cote francais',
        'idx_terms_startletter_endletter_normalized' => 'commencant+terminant mono-lettre, herite : jusqu\'a 6 675 ms mesure sans lui cote francais',
    ];

    foreach ($requiredIndexes as $indexName => $reason) {
        $existsRow = $pdo->query(
            "SELECT COUNT(*) c FROM sqlite_master WHERE type = 'index' AND name = '{$indexName}'"
        )->fetch();
        Assert::same(1, (int) $existsRow['c'], "index manquant : {$indexName} ({$reason})");

        $statRow = $pdo->query(
            "SELECT COUNT(*) c FROM sqlite_stat1 WHERE tbl = 'terms' AND idx = '{$indexName}'"
        )->fetch();
        Assert::same(1, (int) $statRow['c'], "ANALYZE jamais execute sur {$indexName} (sqlite_stat1 vide) -- risque : SQLite peut choisir un mauvais plan sans statistiques ({$reason})");
    }

    // Colonnes disparues du schema francais (fusionnees ou retirees, voir schema.sql) :
    // aucun index dessus ne doit exister -- garde-fou negatif contre une reconstruction
    // accidentelle depuis un schema.sql perime.
    foreach (['idx_terms_ods8', 'idx_terms_ods9'] as $obsoleteIndex) {
        $row = $pdo->query(
            "SELECT COUNT(*) c FROM sqlite_master WHERE type = 'index' AND name = '{$obsoleteIndex}'"
        )->fetch();
        Assert::same(0, (int) $row['c'], "index obsolete (double lexique francais) present a tort : {$obsoleteIndex}");
    }
};
