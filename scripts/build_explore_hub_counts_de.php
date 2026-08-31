<?php

declare(strict_types=1);

/**
 * Precalcule les 19 list_type de list_counts (storage/dictionary_de.sqlite) -- adaptation
 * allemande COMPLETE de scripts/build_explore_hub_counts.php (depot francais cousin, D-022 a
 * D-041 pour l'historique complet de chaque list_type). ETEND la premiere passe (D-DE-018, 5
 * des 19 list_type) aux 14 types restants -- demande produit explicite (2026-08-30, session
 * en cours) : "il reste plein de chose a faire", rattraper le fosse de couverture SEO signale
 * face au depot francais.
 *
 * GRANULARITE 'end'/'length_end' : 1 CARACTERE, INCHANGEE depuis D-DE-018 (question produit
 * posee et tranchee en cours de ce lot, pas supposee) -- App\Search\RelationsFinder emet
 * toujours un lien "se termine par" a 2 caracteres minimum (Normalizer::MIN_LENGTH=2, substr
 * ($mot, -min(2,$longueur))), c'est pourquoi D-DE-017 avait ouvert la famille INDEXEE
 * (word_list_terminant) a 2 lettres, jamais 1 -- MAIS ce lot-ci peuple list_counts, qui
 * alimente en plus le HUB /woerter (App\Search\ExploreHubBuilder), une SOURCE DE LIEN REEL
 * DISTINCTE du lien par mot. D-DE-017 avait mesure "0 lien reel pour endend-mit/{1 lettre}"
 * a un moment ou list_counts etait encore VIDE (chronologie : D-DE-017 precede D-DE-018) --
 * cette mesure est perimee des que ce script tourne. Decision produit explicite (2026-08-30,
 * en direct dans la conversation) : garder 'end'/'length_end' a 1 caractere pour que le hub
 * puisse lier reellement CHAQUE bucket "Endend Mit", et ouvrir en plus une nouvelle famille
 * SEO a 1 lettre sur cette base (voir le lot d'ouverture applique juste apres ce script,
 * verification de doublons/TTFB incluse) -- symetrique a "Beginnend Mit" (1 lettre, deja
 * indexe). La famille DEJA INDEXEE a 2 lettres (D-DE-017, 455 URL, construite par un script
 * one-off independant de list_counts) N'EST PAS remplacee, les deux tailles coexistent.
 *
 * ALPHABET NON BORNE A A-Z (D-DE-002, rappel) : Ä/Ö/Ü sont des lettres allemandes a part
 * entiere. Les 5 requetes deja existantes utilisaient deja substr() SQL (character-safe sur
 * TEXT, verifie). Les NOUVEAUX types ci-dessous qui exigent un parcours PHP lettre par lettre
 * (length_with, length_with_pair/triple, start_end_with, start_with) utilisent
 * mb_str_split()/un tableau associatif PHP -- JAMAIS str_split()/count_chars(), toutes deux
 * BYTE-orientees et qui couperaient Ä/Ö/Ü (2 octets UTF-8 chacune) en deux -- meme classe de
 * bug deja trouvee et corrigee plusieurs fois cette session (RelationsFinder, *LinksBuilder,
 * word.php highlight). Tri des lettres distinctes par sort() PHP (ordre octet, pas locale) --
 * coherent tant que l'INSERTION et la LECTURE (App\Search\*LinksBuilder, herites du francais)
 * utilisent la meme convention ; verifie que ces classes ne trient jamais elles-memes une paire
 * avant de construire la cle (elles la recoivent deja triee depuis list_counts).
 *
 * RISQUE DEJA SIGNALE (D-DE-018), TOUJOURS PAS LEVE ICI : App\Search\LengthLinksBuilder::
 * DUPLICATE_START_END_KEYS/EXTERNAL_DUPLICATE_WITH_KEYS, LetterCombinedLinksBuilder::
 * EXTERNAL_DUPLICATE_KEYS, PositionLinksBuilder::EXTERNAL_DUPLICATE_KEYS restent des listes
 * FIGEES calculees sur storage/dictionary_fr.sqlite, jamais recalculees pour l'allemand. Ce
 * lot PEUPLE 'length_with'/'start_end'/'length_start_end'/etc. (la DONNEE), mais n'ouvre AUCUNE
 * de ces familles a l'indexation et ne touche pas ces constantes -- un futur lot qui construirait
 * un batch SEO reel sur 'length_start_end' ou 'length_with' devra d'abord recalculer ces listes
 * pour l'allemand, sous peine de deduplication silencieusement fausse. Note laissee intacte
 * (toujours vraie) : verifie que 'length_start'/'length_end' (deja en production) ne lisent
 * aucune de ces constantes.
 *
 * Idempotent : DROP + CREATE + INSERT en une transaction, ANALYZE (D-021) a la fin.
 *
 * Usage : php scripts/build_explore_hub_counts_de.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "scripts/build_explore_hub_counts_de.php ne s'execute qu'en CLI, hors ligne.\n");
    exit(1);
}

$root = dirname(__DIR__);
$dbPath = getenv('SCRABBLE_DICTIONARY_DB_PATH') ?: $root . '/storage/dictionary_de.sqlite';

if (!is_file($dbPath)) {
    fwrite(STDERR, "dictionnaire introuvable : {$dbPath}\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec('DROP TABLE IF EXISTS list_counts');
$pdo->exec(
    'CREATE TABLE list_counts ('
    . "list_type TEXT NOT NULL CHECK (list_type IN ('length', 'start', 'end', 'length_start', 'length_end', 'length_with', 'start_end', 'length_with_position', 'length_avec_sans', 'length_start_end', 'length_with_pair', 'length_with_triple', 'start_end_with', 'start_with', 'prefix2', 'prefix3', 'prefix4', 'suffix2', 'suffix3', 'suffix4')), "
    . 'list_key TEXT NOT NULL, '
    . 'count INTEGER NOT NULL, '
    . 'PRIMARY KEY (list_type, list_key)'
    . ')'
);

$insert = $pdo->prepare('INSERT INTO list_counts (list_type, list_key, count) VALUES (?, ?, ?)');

$pdo->beginTransaction();

$total = 0;

// ---- Les 5 types deja construits (D-DE-018), inchanges ----

$lengthStatement = $pdo->query('SELECT length, COUNT(*) n FROM terms GROUP BY length ORDER BY length');
foreach ($lengthStatement as $row) {
    $insert->execute(['length', (string) $row['length'], (int) $row['n']]);
    $total++;
}

$startStatement = $pdo->query('SELECT substr(normalized, 1, 1) c, COUNT(*) n FROM terms GROUP BY c ORDER BY c');
foreach ($startStatement as $row) {
    $insert->execute(['start', $row['c'], (int) $row['n']]);
    $total++;
}

// D-DE-023 (REVISE en cours de tache -- decision produit directe, pas relayee) : 'end'/
// 'length_end' RESTENT a 1 CARACTERE (annule la tentative d'alignement 2-caracteres sur
// ES-017 faite plus tot dans ce meme lot). Raison du changement : le hub /woerter peut
// desormais lier reellement CHAQUE bucket "Endend Mit" a 1 lettre (list_counts vient d'etre
// peuple, contrairement a la mesure D-DE-017 qui l'avait trouve vide -- mesure perimee, pas
// une erreur a l'epoque). Decision explicite du proprietaire du produit : ouvrir aussi
// "termine par 1 lettre" a l'indexation sur les 3 sites, symetrique a "commence par 1
// lettre" deja indexe -- voir le lot SEO dedie apres ce script pour l'ouverture reelle avec
// verification de doublons/TTFB. La famille DEJA INDEXEE a 2 lettres (D-DE-017, 455 URL) ne
// depend PAS de list_counts (construite par une requete directe, un script one-off separe)
// -- inchangee par ce choix.
$mbReverse = static fn (string $s): string => implode('', array_reverse(mb_str_split($s, 1, 'UTF-8')));

$endStatement = $pdo->query('SELECT substr(reversed, 1, 1) c, COUNT(*) n FROM terms GROUP BY c ORDER BY c');
foreach ($endStatement as $row) {
    $insert->execute(['end', $row['c'], (int) $row['n']]);
    $total++;
}

$lengthStartStatement = $pdo->query(
    'SELECT length, substr(normalized, 1, 1) c, COUNT(*) n FROM terms GROUP BY length, c ORDER BY length, c'
);
foreach ($lengthStartStatement as $row) {
    $insert->execute(['length_start', $row['length'] . ':' . $row['c'], (int) $row['n']]);
    $total++;
}

$lengthEndStatement = $pdo->query(
    'SELECT length, substr(reversed, 1, 1) c, COUNT(*) n FROM terms GROUP BY length, c ORDER BY length, c'
);
foreach ($lengthEndStatement as $row) {
    $insert->execute(['length_end', $row['length'] . ':' . $row['c'], (int) $row['n']]);
    $total++;
}

// ---- 14 types nouveaux (ce lot) ----

// length_with : longueur + lettre presente n'importe ou (minCount=1). Parcours PHP unique
// (mb_str_split, pas count_chars -- Ä/Ö/Ü).
$lengthWithCounts = [];
foreach ($pdo->query('SELECT length, normalized FROM terms') as $row) {
    $length = (int) $row['length'];
    $seen = array_unique(mb_str_split((string) $row['normalized'], 1, 'UTF-8'));
    foreach ($seen as $letter) {
        $lengthWithCounts[$length][$letter] = ($lengthWithCounts[$length][$letter] ?? 0) + 1;
    }
}
ksort($lengthWithCounts);
foreach ($lengthWithCounts as $length => $byLetter) {
    ksort($byLetter);
    foreach ($byLetter as $letter => $n) {
        $insert->execute(['length_with', $length . ':' . $letter, $n]);
        $total++;
    }
}

// start_end : lettre de debut ET de fin (1 caractere chacune, coherent avec 'end' ci-dessus,
// revise).
$startEndStatement = $pdo->query(
    'SELECT substr(normalized, 1, 1) s, substr(reversed, 1, 1) e, COUNT(*) n FROM terms GROUP BY s, e ORDER BY s, e'
);
foreach ($startEndStatement as $row) {
    $insert->execute(['start_end', $row['s'] . ':' . $row['e'], (int) $row['n']]);
    $total++;
}

// length_with_position : longueur + lettre + position exacte (1-based).
$positionCounts = [];
foreach ($pdo->query('SELECT length, normalized FROM terms') as $row) {
    $length = (int) $row['length'];
    foreach (mb_str_split((string) $row['normalized'], 1, 'UTF-8') as $index => $letter) {
        $key = $length . ':' . $letter . ':' . ($index + 1);
        $positionCounts[$key] = ($positionCounts[$key] ?? 0) + 1;
    }
}
ksort($positionCounts);
foreach ($positionCounts as $key => $n) {
    $insert->execute(['length_with_position', $key, $n]);
    $total++;
}

// length_avec_sans : lettre EXIGEE + lettre EXCLUE + longueur. Alphabet DE = A-Z + Ä/Ö/Ü (29
// lettres, pas 26) -- construit dynamiquement depuis les lettres reellement distinctes vues
// dans la base plutot qu'une liste A-Z figee (verifie : eviter de silencieusement omettre
// Ä/Ö/Ü de la boucle "without").
$distinctAlphabetStatement = $pdo->query(
    "SELECT DISTINCT substr(normalized, 1, 1) c FROM terms UNION SELECT DISTINCT substr(reversed, 1, 1) FROM terms"
);
$alphabet = [];
foreach ($distinctAlphabetStatement as $row) {
    $alphabet[] = $row['c'];
}
sort($alphabet);

$avecSansCounts = [];
foreach ($pdo->query('SELECT length, normalized FROM terms') as $row) {
    $length = (int) $row['length'];
    $normalized = (string) $row['normalized'];
    $presentArr = array_unique(mb_str_split($normalized, 1, 'UTF-8'));
    $presentFlip = array_flip($presentArr);

    foreach ($presentArr as $with) {
        foreach ($alphabet as $without) {
            if (isset($presentFlip[$without])) {
                continue;
            }
            $key = $with . ':' . $without . ':' . $length;
            $avecSansCounts[$key] = ($avecSansCounts[$key] ?? 0) + 1;
        }
    }
}
ksort($avecSansCounts);
foreach ($avecSansCounts as $key => $n) {
    $insert->execute(['length_avec_sans', $key, $n]);
    $total++;
}

// length_start_end : longueur + lettre de debut ET de fin (1 caractere chacune, revise).
$lengthStartEndStatement = $pdo->query(
    'SELECT length, substr(normalized, 1, 1) s, substr(reversed, 1, 1) e, COUNT(*) n FROM terms'
    . ' GROUP BY length, s, e ORDER BY length, s, e'
);
foreach ($lengthStartEndStatement as $row) {
    $insert->execute(['length_start_end', $row['length'] . ':' . $row['s'] . ':' . $row['e'], (int) $row['n']]);
    $total++;
}

// length_with_pair : longueur + CHAQUE PAIRE de lettres distinctes presentes (lettre1 < lettre2,
// ordre octet PHP -- coherent avec la lecture faite par AvecTwoLettersLinksBuilder qui recoit
// deja la cle triee).
$pairCounts = [];
foreach ($pdo->query('SELECT length, normalized FROM terms') as $row) {
    $length = (int) $row['length'];
    $distinct = array_values(array_unique(mb_str_split((string) $row['normalized'], 1, 'UTF-8')));
    sort($distinct);
    $n = count($distinct);
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $key = $length . ':' . $distinct[$i] . ':' . $distinct[$j];
            $pairCounts[$key] = ($pairCounts[$key] ?? 0) + 1;
        }
    }
}
ksort($pairCounts);
foreach ($pairCounts as $key => $n) {
    $insert->execute(['length_with_pair', $key, $n]);
    $total++;
}

// length_with_triple : longueur + CHAQUE TRIPLET de lettres distinctes presentes.
$tripleCounts = [];
foreach ($pdo->query('SELECT length, normalized FROM terms') as $row) {
    $length = (int) $row['length'];
    $distinct = array_values(array_unique(mb_str_split((string) $row['normalized'], 1, 'UTF-8')));
    sort($distinct);
    $n = count($distinct);
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            for ($k = $j + 1; $k < $n; $k++) {
                $key = $length . ':' . $distinct[$i] . ':' . $distinct[$j] . ':' . $distinct[$k];
                $tripleCounts[$key] = ($tripleCounts[$key] ?? 0) + 1;
            }
        }
    }
}
ksort($tripleCounts);
foreach ($tripleCounts as $key => $n) {
    $insert->execute(['length_with_triple', $key, $n]);
    $total++;
}

// start_end_with : lettre de debut + lettre de fin (1 caractere chacune, revise) + lettre
// presente n'importe ou (minCount=1).
$startEndWithCounts = [];
foreach ($pdo->query('SELECT normalized, reversed FROM terms') as $row) {
    $normalized = (string) $row['normalized'];
    $chars = mb_str_split($normalized, 1, 'UTF-8');
    $start = $chars[0];
    $end = mb_substr((string) $row['reversed'], 0, 1, 'UTF-8');
    $distinct = array_unique($chars);

    foreach ($distinct as $letter) {
        $key = $start . ':' . $end . ':' . $letter;
        $startEndWithCounts[$key] = ($startEndWithCounts[$key] ?? 0) + 1;
    }
}
ksort($startEndWithCounts);
foreach ($startEndWithCounts as $key => $n) {
    $insert->execute(['start_end_with', $key, $n]);
    $total++;
}

// start_with : lettre de debut + lettre presente n'importe ou (minCount=1), SANS longueur ni
// fin -- exclusion des diagonales (lettre = debut) au precalcul (D-032 cote francais, meme
// raisonnement : WordListFilters::fromPath() collapse "avec/X" vers la page parente
// commencant/X des que la lettre "avec" egale le prefixe d'une seule lettre).
$startWithCounts = [];
foreach ($pdo->query('SELECT normalized FROM terms') as $row) {
    $chars = mb_str_split((string) $row['normalized'], 1, 'UTF-8');
    $start = $chars[0];
    $distinct = array_unique($chars);

    foreach ($distinct as $letter) {
        if ($letter === $start) {
            continue;
        }
        $key = $start . ':' . $letter;
        $startWithCounts[$key] = ($startWithCounts[$key] ?? 0) + 1;
    }
}
ksort($startWithCounts);
foreach ($startWithCounts as $key => $n) {
    $insert->execute(['start_with', $key, $n]);
    $total++;
}

// prefix2/3/4 : GROUP BY direct sur substr(normalized, 1, N) -- character-safe en SQL, pas de
// parcours PHP necessaire (meme principe que 'start' ci-dessus).
foreach ([2, 3, 4] as $prefixLength) {
    $prefixStatement = $pdo->query(
        "SELECT substr(normalized, 1, {$prefixLength}) c, COUNT(*) n FROM terms"
        . " WHERE length >= {$prefixLength} GROUP BY c ORDER BY c"
    );
    foreach ($prefixStatement as $row) {
        $insert->execute(['prefix' . $prefixLength, $row['c'], (int) $row['n']]);
        $total++;
    }
}

// suffix2/3/4 : meme principe via substr(reversed, 1, N), remis en ordre de lecture normal via
// mbReverse() (deja defini plus haut) -- PAS strrev() (byte-oriente, corromprait Ä/Ö/Ü).
foreach ([2, 3, 4] as $suffixLength) {
    $suffixStatement = $pdo->query(
        "SELECT substr(reversed, 1, {$suffixLength}) c, COUNT(*) n FROM terms"
        . " WHERE length >= {$suffixLength} GROUP BY c ORDER BY c"
    );
    foreach ($suffixStatement as $row) {
        $suffix = $mbReverse((string) $row['c']);
        $insert->execute(['suffix' . $suffixLength, $suffix, (int) $row['n']]);
        $total++;
    }
}

$pdo->commit();

// D-021 (herite) : toute modification de table/index doit etre suivie d'ANALYZE dans la MEME
// operation.
$pdo->exec('ANALYZE');

// CORRECTIF I6 (audit NO GO 2026-08-31) : le DROP TABLE + CREATE TABLE + INSERT ci-dessus
// laisse des pages libres (freelist) dans le fichier -- ce script tourne apres le VACUUM final
// de scripts/import_de.py (import_de.py:208-210), qui ne voit donc jamais l'espace liberé par
// une reconstruction ulterieure de list_counts. Mesure avant ce correctif : 1 898 pages libres
// (freelist_count), 4,6% des 40 863 pages totales (page_size=4096), ~7,4 Mo recuperables sur
// 159,6 Mo -- voir le rapport AFTER de cette tache pour le avant/apres exact. VACUUM ne peut pas
// s'executer dans une transaction explicite (deja fermee par commit() ci-dessus) ; execute apres
// ANALYZE plutot qu'avant : VACUUM recopie les stats sqlite_stat1 telles quelles, aucune
// modification de table/index n'a lieu entre les deux (D-021 reste respecte -- ANALYZE suit
// toujours la modification reelle de list_counts, VACUUM est un simple compactage physique du
// fichier, jamais un changement de contenu).
$pdo->exec('VACUUM');

printf("list_counts : %d lignes inserees (19/19 list_type)\n", $total);
