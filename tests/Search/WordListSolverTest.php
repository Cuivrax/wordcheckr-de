<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\WordListFilters;
use App\Search\WordListSolver;
use Tests\Support\Assert;

/**
 * Exerce App\Search\WordListSolver sur la vraie base storage/dictionary_de.sqlite (lecture
 * seule) : correction croisee par force brute pour chaque contrainte et plusieurs
 * combinaisons, comportement de pagination, et le plafond de securite
 * (WordListSolver::ROW_EXAMINATION_CEILING) -- meme methodologie que RackSolverTest.php.
 *
 * ADAPTATION ALLEMANDE : la plupart des assertions ci-dessous recalculent leur valeur
 * attendue DIRECTEMENT depuis la base reelle (COUNT() SQL independant du solveur, pas une
 * valeur figee) -- elles restent donc valides sans changement pour n'importe quelle
 * langue. Ce qui a du changer : is_ods8/is_ods9 -> is_admitted (schema.sql, un seul
 * lexique), et les MOTS/LETTRES choisis comme cas particuliers illustratifs (frequences
 * differentes en allemand -- ex. le prefixe le plus frequent est A, pas R). "status/nicht-
 * gueltig" renvoie desormais toujours 0 resultat (aucune source "reel mais non admis"
 * allemande cette passe, voir data/raw/PROVENANCE.md) -- verifie explicitement comme un
 * comportement attendu, pas une regression.
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $connection = new Connection($dbPath);
    $solver = new WordListSolver($connection);
    $pdo = $connection->pdo();

    // --- Entree invalide ou hors perimetre : aucune liste, meme convention que
    // --- TermLookup::find() et RackSolver::solve(). ---
    Assert::null($solver->solve('inconnu/valeur'));
    Assert::null($solver->solve(''), '/woerter seul (aucune contrainte) refuse explicitement');

    // --- Longueur seule : EXACT, total = COUNT() direct sur idx_terms_length_normalized. ---
    $byLength = $solver->solve('7-buchstaben');
    Assert::notNull($byLength);
    Assert::true($byLength->exact);
    Assert::true(!$byLength->truncated);
    Assert::same(2, $byLength->queryCount);
    $expectedLengthCount = (int) $pdo->query('SELECT COUNT(*) c FROM terms WHERE length = 7')->fetch()['c'];
    Assert::same($expectedLengthCount, $byLength->total);
    Assert::same(WordListSolver::PAGE_SIZE, count($byLength->items));
    for ($i = 1; $i < count($byLength->items); $i++) {
        Assert::true($byLength->items[$i - 1]['normalized'] <= $byLength->items[$i]['normalized'], 'ordre alphabetique attendu');
    }
    foreach ($byLength->items as $item) {
        Assert::same(7, $item['length']);
        Assert::true(in_array($item['status'], ['admitted', 'french_not_admitted'], true), 'jamais STATUS_UNKNOWN sur une ligne de `terms`');
    }

    // --- Prefixe seul : EXACT, verifie par force brute (pas un echantillon). QI choisi ---
    // --- (7 correspondances en allemand : Qigong et ses formes flechies). ---
    $byPrefix = $solver->solve('beginnend-mit/qi');
    Assert::notNull($byPrefix);
    Assert::true($byPrefix->exact);
    $bruteForcePrefix = [];
    foreach ($pdo->query("SELECT normalized FROM terms WHERE normalized LIKE 'QI%'") as $row) {
        if (str_starts_with($row['normalized'], 'QI')) {
            $bruteForcePrefix[] = $row['normalized'];
        }
    }
    sort($bruteForcePrefix);
    Assert::same(count($bruteForcePrefix), $byPrefix->total);

    // --- Longueur + prefixe combines : intersection exacte. ---
    $comboPage = $solver->solve('7-buchstaben/beginnend-mit/ch');
    Assert::notNull($comboPage);
    Assert::true($comboPage->exact);
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM terms WHERE length = 7 AND normalized >= 'CH' AND normalized < 'CI'");
    $stmt->execute();
    $expectedCombo = (int) $stmt->fetch()['c'];
    Assert::same($expectedCombo, $comboPage->total);
    foreach ($comboPage->items as $item) {
        Assert::same(7, $item['length']);
        Assert::true(str_starts_with($item['normalized'], 'CH'));
    }

    // --- Terminant seul : verifie par force brute sur reversed. TION : suffixe latin
    // --- courant en allemand aussi (Nation, Aktion, Funktion...). ---
    $bySuffix = $solver->solve('endend-mit/tion');
    Assert::notNull($bySuffix);
    foreach ($bySuffix->items as $item) {
        Assert::true(str_ends_with($item['normalized'], 'TION'));
    }
    $stmtSuffix = $pdo->prepare("SELECT COUNT(*) c FROM terms WHERE normalized LIKE '%TION'");
    $stmtSuffix->execute();
    $expectedSuffixTotal = (int) $stmtSuffix->fetch()['c'];
    Assert::true(!$bySuffix->truncated, 'le panier de longueur "TION" ne doit pas depasser le plafond');
    Assert::same($expectedSuffixTotal, $bySuffix->total);

    // --- Regression index idx_terms_length_reversed (heritee du site francais) : "longueur +
    // --- endend-mit" combine, verifie par force brute que le resultat reste correct et que
    // --- le budget de requetes ne change pas (toujours 2, ancrage reversed non fusionne). ---
    $lengthSuffix = $solver->solve('8-buchstaben/endend-mit/e');
    Assert::notNull($lengthSuffix);
    Assert::same(2, $lengthSuffix->queryCount);
    foreach ($lengthSuffix->items as $item) {
        Assert::same(8, $item['length']);
        Assert::true(str_ends_with($item['normalized'], 'E'));
    }
    $expectedLengthSuffixTotal = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE length = 8 AND normalized LIKE '%E'")->fetch()['c'];
    Assert::true($expectedLengthSuffixTotal > WordListSolver::ROW_EXAMINATION_CEILING, 'sanity check : "8-buchstaben/endend-mit/e" doit reellement depasser le plafond, obtenu ' . $expectedLengthSuffixTotal);
    Assert::true($lengthSuffix->truncated, 'panier reellement au-dessus du plafond -> truncated attendu');

    // --- Contenant : verifie par force brute (instr() cote SQL, str_contains() cote PHP). ---
    $contains = $solver->solve('enthalten/che');
    Assert::notNull($contains);
    foreach ($contains->items as $item) {
        Assert::true(str_contains($item['normalized'], 'CHE'));
    }

    // --- Regression C1 (heritee du site francais) : "enthalten" SEUL, sans aucun ancrage
    // --- (longueur/prefixe/suffixe), doit trouver TOUTES les correspondances de toute la
    // --- base, pas seulement celles situees dans les ROW_EXAMINATION_CEILING premiers mots
    // --- de l'ordre alphabetique. XYL choisi : total reel mesure sous
    // --- ROW_EXAMINATION_CEILING (77 < 10 000) -- le total renvoye doit donc etre EXACT. ---
    $unanchoredContains = $solver->solve('enthalten/xyl');
    Assert::notNull($unanchoredContains);
    $bruteForceXyl = (int) $pdo->query('SELECT COUNT(*) c FROM terms WHERE instr(normalized, \'XYL\') > 0')->fetch()['c'];
    Assert::true($bruteForceXyl > 0, 'sanity check : XYL doit avoir des correspondances reelles dans la base');
    Assert::true(!$unanchoredContains->truncated, 'XYL (' . $bruteForceXyl . ' correspondances) est sous le plafond, ne doit pas etre tronque');
    Assert::same($bruteForceXyl, $unanchoredContains->total, 'C1 : "enthalten" sans ancrage doit trouver TOUTES les correspondances, pas seulement celles des 10 000 premiers mots alphabetiques');
    foreach ($unanchoredContains->items as $item) {
        Assert::true(str_contains($item['normalized'], 'XYL'));
    }

    // --- Regression C1, variante "mit-buchstaben" (minCount = 1, chemin optimise instr()) : meme
    // --- verification par force brute, plusieurs lettres combinees, sans aucun ancrage. ---
    $unanchoredWith = $solver->solve('mit-buchstaben/x/y/z');
    Assert::notNull($unanchoredWith);
    $bruteForceXyz = (int) $pdo->query('SELECT COUNT(*) c FROM terms WHERE instr(normalized, \'X\') > 0 AND instr(normalized, \'Y\') > 0 AND instr(normalized, \'Z\') > 0')->fetch()['c'];
    Assert::true(!$unanchoredWith->truncated, 'mit-buchstaben/x/y/z (' . $bruteForceXyz . ' correspondances) est sous le plafond, ne doit pas etre tronque');
    Assert::same($bruteForceXyz, $unanchoredWith->total, 'C1 : "mit-buchstaben" sans ancrage doit trouver TOUTES les correspondances');
    foreach ($unanchoredWith->items as $item) {
        Assert::true(str_contains($item['normalized'], 'X') && str_contains($item['normalized'], 'Y') && str_contains($item['normalized'], 'Z'));
    }

    // --- Avec, repetitions comptees : verifie par force brute (array_count_values). ---
    $withLetters = $solver->solve('mit-buchstaben/a/a/r');
    Assert::notNull($withLetters);
    foreach ($withLetters->items as $item) {
        // mb_str_split (pas str_split) : coherence generale, meme si A/R ne sont pas Ä/Ö/Ü.
        $counts = array_count_values(mb_str_split($item['normalized']));
        Assert::true(($counts['A'] ?? 0) >= 2, $item['normalized'] . ' doit contenir au moins 2 A');
        Assert::true(($counts['R'] ?? 0) >= 1, $item['normalized'] . ' doit contenir au moins 1 R');
    }
    Assert::true($withLetters->total > 0, 'sanity check : au moins un mot avec 2 A et 1 R doit exister');

    // --- Palier 2 de l'ouverture en entonnoir de "mit-buchstaben" (longueur explicite + EXACTEMENT
    // --- deux lettres "mit-buchstaben", chacune minCount=1) : ancrage sur `length = ?`, jamais un
    // --- ancrage "mit-buchstaben" -- verifie dans le code avant ce test, pas seulement suppose ici. ---
    $avecTwoLetters = $solver->solve('9-buchstaben/mit-buchstaben/q/x');
    Assert::notNull($avecTwoLetters);
    Assert::same(1, $avecTwoLetters->queryCount, 'ancrage normalized (length=?) : fusionne a 1 seule requete');
    $bruteForceQX9 = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE length = 9 AND instr(normalized, 'Q') > 0 AND instr(normalized, 'X') > 0")->fetch()['c'];
    Assert::true(!$avecTwoLetters->truncated, '9-buchstaben/mit-buchstaben/q/x (' . $bruteForceQX9 . ' correspondances) est sous le plafond, ne doit pas etre tronque');
    Assert::same($bruteForceQX9, $avecTwoLetters->total, 'correction verifiee par force brute');
    foreach ($avecTwoLetters->items as $item) {
        Assert::same(9, $item['length']);
        Assert::true(str_contains($item['normalized'], 'Q') && str_contains($item['normalized'], 'X'));
    }

    // --- L'ordre des segments dans le chemin BRUT ne doit rien changer : "mit-buchstaben/x/q" doit
    // --- produire exactement le meme total et le meme canonicalPath que "mit-buchstaben/q/x". ---
    $avecTwoLettersReversedInput = $solver->solve('9-buchstaben/mit-buchstaben/x/q');
    Assert::notNull($avecTwoLettersReversedInput);
    Assert::same($avecTwoLetters->total, $avecTwoLettersReversedInput->total, 'ordre de saisie des deux lettres "mit-buchstaben" sans effet sur le total');
    Assert::same($avecTwoLetters->canonicalPath, $avecTwoLettersReversedInput->canonicalPath, 'meme canonicalPath quel que soit l\'ordre de saisie');
    Assert::same('9-buchstaben/mit-buchstaben/q/x', $avecTwoLetters->canonicalPath, 'ordre alphabetique impose par canonicalPath()');

    // --- Cas pathologique plausible (deux lettres frequentes a la fois) : doit rester ancre
    // --- sur `length = ?` et ne jamais depasser le budget de requetes. ---
    $avecTwoLettersFrequent = $solver->solve('11-buchstaben/mit-buchstaben/e/s');
    Assert::notNull($avecTwoLettersFrequent);
    Assert::same(1, $avecTwoLettersFrequent->queryCount, 'toujours 1 seule requete, meme avec deux lettres tres frequentes');
    foreach ($avecTwoLettersFrequent->items as $item) {
        Assert::same(11, $item['length']);
        Assert::true(str_contains($item['normalized'], 'E') && str_contains($item['normalized'], 'S'));
    }

    // --- Palier 3 de l'ouverture en entonnoir de "mit-buchstaben" (longueur explicite + EXACTEMENT
    // --- trois lettres "mit-buchstaben"). Q/U/Z choisi (26 correspondances en allemand, non nul). ---
    $avecThreeLetters = $solver->solve('9-buchstaben/mit-buchstaben/q/u/z');
    Assert::notNull($avecThreeLetters);
    Assert::same(1, $avecThreeLetters->queryCount, 'ancrage normalized (length=?) : fusionne a 1 seule requete, comme les paliers 1 et 2');
    $bruteForceQUZ9 = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE length = 9 AND instr(normalized, 'Q') > 0 AND instr(normalized, 'U') > 0 AND instr(normalized, 'Z') > 0")->fetch()['c'];
    Assert::true($bruteForceQUZ9 > 0, 'sanity check : au moins un mot de 9 lettres avec Q, U et Z doit exister');
    Assert::true(!$avecThreeLetters->truncated, '9-buchstaben/mit-buchstaben/q/u/z (' . $bruteForceQUZ9 . ' correspondances) est sous le plafond, ne doit pas etre tronque');
    Assert::same($bruteForceQUZ9, $avecThreeLetters->total, 'correction verifiee par force brute');
    foreach ($avecThreeLetters->items as $item) {
        Assert::same(9, $item['length']);
        Assert::true(str_contains($item['normalized'], 'Q') && str_contains($item['normalized'], 'U') && str_contains($item['normalized'], 'Z'));
    }

    // --- L'ordre des segments dans le chemin BRUT ne doit rien changer : "mit-buchstaben/z/q/u" doit
    // --- produire exactement le meme total et le meme canonicalPath que "mit-buchstaben/q/u/z". ---
    $avecThreeLettersReversedInput = $solver->solve('9-buchstaben/mit-buchstaben/z/q/u');
    Assert::notNull($avecThreeLettersReversedInput);
    Assert::same($avecThreeLetters->total, $avecThreeLettersReversedInput->total, 'ordre de saisie des trois lettres "mit-buchstaben" sans effet sur le total');
    Assert::same($avecThreeLetters->canonicalPath, $avecThreeLettersReversedInput->canonicalPath, 'meme canonicalPath quel que soit l\'ordre de saisie');
    Assert::same('9-buchstaben/mit-buchstaben/q/u/z', $avecThreeLetters->canonicalPath, 'ordre alphabetique impose par canonicalPath()');

    // --- Cas pathologique plausible (trois lettres tres frequentes a la fois) : doit rester
    // --- ancre sur `length = ?` et ne jamais depasser le budget de requetes. ---
    $avecThreeLettersFrequent = $solver->solve('11-buchstaben/mit-buchstaben/e/s/t');
    Assert::notNull($avecThreeLettersFrequent);
    Assert::same(1, $avecThreeLettersFrequent->queryCount, 'toujours 1 seule requete, meme avec trois lettres tres frequentes');
    foreach ($avecThreeLettersFrequent->items as $item) {
        Assert::same(11, $item['length']);
        Assert::true(str_contains($item['normalized'], 'E') && str_contains($item['normalized'], 'S') && str_contains($item['normalized'], 'T'));
    }

    // --- Longueur trop courte pour trois lettres distinctes (2 lettres au total) : le
    // --- solveur doit repondre correctement (0 resultat), pas planter. ---
    $avecThreeLettersTooShort = $solver->solve('2-buchstaben/mit-buchstaben/a/e/i');
    Assert::notNull($avecThreeLettersTooShort);
    Assert::same(0, $avecThreeLettersTooShort->total, 'un mot de 2 lettres ne peut jamais contenir 3 lettres distinctes');
    Assert::same(1, $avecThreeLettersTooShort->queryCount);

    // --- Sans : aucune occurrence de la lettre exclue. ---
    $without = $solver->solve('ohne/z');
    Assert::notNull($without);
    foreach ($without->items as $item) {
        Assert::true(!str_contains($item['normalized'], 'Z'));
    }

    // --- Motif : cases connues respectees position par position. ---
    $motif = $solver->solve('5-buchstaben/muster/c--e-');
    Assert::notNull($motif);
    Assert::true($motif->total > 0);
    foreach ($motif->items as $item) {
        Assert::same(5, mb_strlen($item['normalized']));
        Assert::same('C', mb_substr($item['normalized'], 0, 1));
        Assert::same('E', mb_substr($item['normalized'], 3, 1));
    }

    // --- Combinaison prefixe + endend-mit : suffixe applique en predicat supplementaire.
    // --- CHLORPRODUKTION (commence par CH, termine par TION) doit apparaitre. ---
    $prefixSuffix = $solver->solve('beginnend-mit/ch/endend-mit/tion');
    Assert::notNull($prefixSuffix);
    foreach ($prefixSuffix->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'CH'));
        Assert::true(str_ends_with($item['normalized'], 'TION'));
    }
    Assert::true(in_array('CHLORPRODUKTION', array_column($prefixSuffix->items, 'normalized'), true), 'CHLORPRODUKTION doit apparaitre (commence par CH, termine par TION)');
    $bruteForceChTion = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'CH' AND normalized < 'CI' AND normalized LIKE '%TION'")->fetch()['c'];
    Assert::true(!$prefixSuffix->truncated, 'C1 : le panier combine (CH + TION, ' . $bruteForceChTion . ' correspondances reelles) est sous le plafond, ne doit pas etre tronque a tort du seul fait que le panier ANCRE seul (CH) le depasse');
    Assert::same($bruteForceChTion, $prefixSuffix->total, 'total exact attendu, panier combine sous le plafond');

    // --- Regression D-025bis (heritee du site francais) : prefixe ET suffixe D'UNE SEULE
    // --- LETTRE CHACUN doivent ancrer sur idx_terms_startletter_endletter_normalized
    // --- (egalite combinee), jamais sur une plage residuelle -- 1 seule requete quel que
    // --- soit le couple de lettres. ---
    $frequentPrefixRareSuffix = $solver->solve('beginnend-mit/r/endend-mit/h');
    Assert::notNull($frequentPrefixRareSuffix);
    $bruteForceRH = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'R' AND normalized < 'S' AND normalized LIKE '%H'")->fetch()['c'];
    Assert::same($bruteForceRH, $frequentPrefixRareSuffix->total, 'correction verifiee par force brute');
    foreach ($frequentPrefixRareSuffix->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'R') && str_ends_with($item['normalized'], 'H'));
    }
    Assert::same(1, $frequentPrefixRareSuffix->queryCount, 'prefixe+suffixe d\'une seule lettre chacun : egalite combinee, 1 seule requete fusionnee');

    // --- Meme index, sens inverse (prefixe rare, suffixe frequent) : doit rester a 1
    // --- requete aussi, symetrique par construction. ---
    $rarePrefixFrequentSuffix = $solver->solve('beginnend-mit/q/endend-mit/s');
    Assert::notNull($rarePrefixFrequentSuffix);
    $bruteForceQS = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'Q' AND normalized < 'R' AND normalized LIKE '%S'")->fetch()['c'];
    Assert::same($bruteForceQS, $rarePrefixFrequentSuffix->total, 'correction verifiee par force brute');
    foreach ($rarePrefixFrequentSuffix->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'Q') && str_ends_with($item['normalized'], 'S'));
    }
    Assert::same(1, $rarePrefixFrequentSuffix->queryCount, 'prefixe+suffixe d\'une seule lettre chacun : egalite combinee, 1 seule requete fusionnee');

    // --- Cas degenere pres de Z, prefixe ET suffixe simultanement d'une seule lettre :
    // --- ancre sur l'EGALITE combinee (idx_terms_startletter_endletter_normalized), jamais
    // --- sur une plage rangeBounds() -- quand les DEUX lettres sont frequentes, seul cet
    // --- index combine resout le cas efficacement. ADAPTATION ALLEMANDE : le brute force
    // --- DOIT utiliser substr(normalized,1,1) = 'Z' (egalite), PAS normalized >= 'Z'
    // --- (plage) -- en francais (domaine pur A-Z) les deux formulations coincidaient
    // --- puisque Z est la derniere lettre, mais en allemand Ä/Ö/Ü trient APRES Z (voir
    // --- Normalizer::signature()) : "normalized >= 'Z'" inclurait alors A TORT les mots
    // --- beginnend-mit par Ä/Ö/Ü, que ni le code ni ce test ne doivent compter ici. Erreur
    // --- reellement rencontree en ecrivant ce test (4322 avec la plage, contre 3226 -- le
    // --- bon compte -- avec l'egalite), pas une hypothese theorique. ---
    $bothFrequent = $solver->solve('beginnend-mit/z/endend-mit/s');
    Assert::notNull($bothFrequent);
    $bruteForceZS = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE substr(normalized, 1, 1) = 'Z' AND normalized LIKE '%S'")->fetch()['c'];
    Assert::same($bruteForceZS, $bothFrequent->total, 'correction verifiee par force brute (Z comme prefixe, egalite plutot que plage)');
    foreach ($bothFrequent->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'Z') && str_ends_with($item['normalized'], 'S'));
    }
    Assert::same(1, $bothFrequent->queryCount, 'meme les deux lettres frequentes a la fois restent a 1 seule requete avec l\'index combine');

    // --- Plafond de securite, toujours actif sur le panier COMBINE quand il depasse
    // --- reellement ROW_EXAMINATION_CEILING (pas seulement l'ancrage) : "S" + "sans Z"
    // --- laisse un panier reel bien au-dessus du plafond en allemand (S est le 2e prefixe
    // --- le plus frequent apres A). ---
    $anchoredTruncated = $solver->solve('beginnend-mit/s/ohne/z');
    Assert::notNull($anchoredTruncated);
    $bruteForceSSansZ = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'S' AND normalized < 'T' AND instr(normalized, 'Z') = 0")->fetch()['c'];
    Assert::true($bruteForceSSansZ > WordListSolver::ROW_EXAMINATION_CEILING, 'sanity check : le panier combine S + sans Z doit reellement depasser le plafond pour que ce test ait un sens, obtenu ' . $bruteForceSSansZ);
    Assert::true($anchoredTruncated->truncated, 'panier combine reellement au-dessus du plafond -> truncated attendu');
    Assert::true(!$anchoredTruncated->exact, 'total non garanti exhaustif quand truncated = true');
    foreach ($anchoredTruncated->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'S'));
        Assert::true(!str_contains($item['normalized'], 'Z'));
    }

    // --- Pagination : page 2 renvoie des elements differents, coherents avec page 1. ---
    $page1 = $solver->solve('7-buchstaben');
    $page2 = $solver->solve('7-buchstaben/page/2');
    Assert::notNull($page1);
    Assert::notNull($page2);
    Assert::same(1, $page1->page);
    Assert::same(2, $page2->page);
    Assert::true($page1->hasNextPage);
    Assert::true(!$page1->hasPreviousPage);
    Assert::true($page2->hasPreviousPage);
    Assert::same($page1->total, $page2->total, 'meme total sur les deux pages');
    $page1Words = array_column($page1->items, 'normalized');
    $page2Words = array_column($page2->items, 'normalized');
    Assert::same([], array_intersect($page1Words, $page2Words), 'aucun mot en commun entre page 1 et page 2');
    Assert::true(max($page1Words) < min($page2Words), 'page 2 suit alphabetiquement la page 1');

    // --- Budget de requetes : au plus 2, quelle que soit la combinaison de contraintes.
    // --- Regime EXACT (longueur/prefixe seuls ou combines) : toujours 2 (COUNT + LIMIT/OFFSET).
    // --- Regime BORNE : 1 requete quand l'ancrage est deja l'ordre d'affichage (normalized) --
    // --- 2 requetes seulement pour l'ancrage sur suffixe (reversed). ---
    foreach ([$byLength, $byPrefix, $comboPage] as $result) {
        Assert::same(2, $result->queryCount, 'regime EXACT : toujours 2 requetes');
    }
    Assert::same(2, $bySuffix->queryCount, 'suffixe seul : ancrage reversed, 2 requetes (non fusionne)');
    foreach ([$contains, $unanchoredContains, $unanchoredWith, $withLetters, $without, $motif, $anchoredTruncated, $avecTwoLetters, $avecTwoLettersReversedInput, $avecTwoLettersFrequent, $avecThreeLetters, $avecThreeLettersReversedInput, $avecThreeLettersFrequent, $avecThreeLettersTooShort] as $result) {
        Assert::same(1, $result->queryCount, 'regime BORNE, ancrage normalized (ou aucun ancrage) : fusionne a 1 requete');
    }
    // prefixe ET suffixe explicites tous deux presents : 1 requete de plus pour choisir la
    // lettre la moins frequente comme ancrage (voir anchorClause()).
    Assert::true(in_array($prefixSuffix->queryCount, [2, 3], true), 'prefixe+suffixe explicites : 2 requetes de base, +1 si choix de frequence necessaire');
    foreach ([$byLength, $byPrefix, $comboPage, $bySuffix, $contains, $unanchoredContains, $unanchoredWith, $withLetters, $without, $motif, $prefixSuffix, $anchoredTruncated, $avecTwoLetters, $avecTwoLettersReversedInput, $avecTwoLettersFrequent, $avecThreeLetters, $avecThreeLettersReversedInput, $avecThreeLettersFrequent, $avecThreeLettersTooShort] as $result) {
        Assert::true($result->queryCount <= 10, 'budget de requetes indexees depasse');
    }

    // --- Redirection canonique geree par WordListFilters, deja testee par
    // --- WordListFiltersTest.php -- pas reteste ici pour eviter la duplication. ---

    // --- Statut, regime EXACT (longueur seule) : is_admitted precalcule, verifie par force
    // --- brute. ADAPTATION ALLEMANDE : toute ligne est is_admitted = 1 cette passe (source
    // --- unique) -- "status/gueltig" doit donc egaler le total de la longueur, et
    // --- "status/nicht-gueltig" doit toujours renvoyer 0 (comportement attendu, pas une
    // --- regression -- voir data/raw/PROVENANCE.md). ---
    $admittedOnly = $solver->solve('9-buchstaben/status/gueltig');
    Assert::notNull($admittedOnly);
    Assert::true($admittedOnly->exact);
    Assert::same(2, $admittedOnly->queryCount, 'regime EXACT : is_admitted est un predicat de plus dans la meme clause WHERE, toujours 2 requetes');
    $expectedAdmitted9 = (int) $pdo->query('SELECT COUNT(*) c FROM terms WHERE length = 9 AND is_admitted = 1')->fetch()['c'];
    Assert::same($expectedAdmitted9, $admittedOnly->total);
    $expectedLength9Total = (int) $pdo->query('SELECT COUNT(*) c FROM terms WHERE length = 9')->fetch()['c'];
    Assert::same($expectedLength9Total, $expectedAdmitted9, 'ADAPTATION ALLEMANDE : source unique, statut/gueltig egale toujours le total de la longueur');
    foreach ($admittedOnly->items as $item) {
        Assert::same(9, $item['length']);
        Assert::same('admitted', $item['status']);
    }

    $notAdmittedOnly = $solver->solve('9-buchstaben/status/nicht-gueltig');
    Assert::notNull($notAdmittedOnly);
    Assert::same(0, $notAdmittedOnly->total, 'ADAPTATION ALLEMANDE : aucune forme "reelle mais non admise" cette passe, statut/nicht-gueltig renvoie toujours 0 (comportement attendu)');
    Assert::same([], $notAdmittedOnly->items);

    // --- Statut, regime BORNE (ancrage suffixe, verifie par force brute) : predicat
    // --- is_admitted ajoute au meme cout que les autres. ---
    $boundedStatus = $solver->solve('endend-mit/tion/status/gueltig');
    Assert::notNull($boundedStatus);
    $expectedBoundedStatus = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized LIKE '%TION' AND is_admitted = 1")->fetch()['c'];
    Assert::true(!$boundedStatus->truncated, 'sanity check : panier "TION" + admis reste sous le plafond');
    Assert::same($expectedBoundedStatus, $boundedStatus->total);
    foreach ($boundedStatus->items as $item) {
        Assert::true(str_ends_with($item['normalized'], 'TION'));
        Assert::same('admitted', $item['status']);
    }

    // --- Tri par points, regime EXACT : ordre croissant puis decroissant, verifie sur la
    // --- totalite de la longueur (pas seulement la page courante) via une requete separee. ---
    $sortedAsc = $solver->solve('9-buchstaben/sortierung/punkte');
    Assert::notNull($sortedAsc);
    Assert::true($sortedAsc->exact);
    for ($i = 1; $i < count($sortedAsc->items); $i++) {
        Assert::true($sortedAsc->items[$i - 1]['score'] <= $sortedAsc->items[$i]['score'], 'ordre croissant par points attendu');
    }
    $expectedFirstScore = (int) $pdo->query('SELECT MIN(score) c FROM terms WHERE length = 9')->fetch()['c'];
    Assert::same($expectedFirstScore, $sortedAsc->items[0]['score'], 'le premier mot de la page 1 doit porter le score minimal de la longueur');

    $sortedDesc = $solver->solve('9-buchstaben/sortierung/punkte-absteigend');
    Assert::notNull($sortedDesc);
    for ($i = 1; $i < count($sortedDesc->items); $i++) {
        Assert::true($sortedDesc->items[$i - 1]['score'] >= $sortedDesc->items[$i]['score'], 'ordre decroissant par points attendu');
    }
    $expectedMaxScore = (int) $pdo->query('SELECT MAX(score) c FROM terms WHERE length = 9')->fetch()['c'];
    Assert::same($expectedMaxScore, $sortedDesc->items[0]['score'], 'le premier mot de la page 1 doit porter le score maximal de la longueur');

    // --- Tri par points, regime BORNE (longueur + suffixe, ancrage reversed, tri PHP
    // --- applique sur le panier deja borne par ROW_EXAMINATION_CEILING) : meme verification. ---
    $boundedSorted = $solver->solve('9-buchstaben/endend-mit/s/sortierung/punkte-absteigend');
    Assert::notNull($boundedSorted);
    for ($i = 1; $i < count($boundedSorted->items); $i++) {
        Assert::true($boundedSorted->items[$i - 1]['score'] >= $boundedSorted->items[$i]['score'], 'ordre decroissant par points attendu meme en regime BORNE');
    }
    foreach ($boundedSorted->items as $item) {
        Assert::true(str_ends_with($item['normalized'], 'S'));
        Assert::same(9, $item['length']);
    }

    // --- Statut + tri combines : les deux raffinements s'appliquent ensemble sans interference. ---
    $statusAndSort = $solver->solve('9-buchstaben/status/gueltig/sortierung/punkte-absteigend');
    Assert::notNull($statusAndSort);
    Assert::same($expectedAdmitted9, $statusAndSort->total, 'meme total que le filtre statut seul (le tri ne change pas le panier)');
    foreach ($statusAndSort->items as $item) {
        Assert::same('admitted', $item['status']);
    }
    for ($i = 1; $i < count($statusAndSort->items); $i++) {
        Assert::true($statusAndSort->items[$i - 1]['score'] >= $statusAndSort->items[$i]['score']);
    }

    // --- Budget de requetes : les nouveaux cas restent dans les memes regimes deja verifies
    // --- ci-dessus (EXACT = 2, BORNE ancrage normalized = 1, BORNE ancrage reversed = 2). ---
    foreach ([$admittedOnly, $sortedAsc, $sortedDesc, $statusAndSort] as $result) {
        Assert::same(2, $result->queryCount, 'regime EXACT inchange par statut/sortierung');
    }
    Assert::same(2, $boundedStatus->queryCount, 'regime BORNE ancrage reversed (endend-mit seul, suffixe) inchange par statut');
    Assert::same(2, $boundedSorted->queryCount, 'regime BORNE ancrage reversed (suffixe) inchange par tri');

    // --- Position : une lettre connue a une position precise, verifiee par force brute
    // --- (substr() cote SQL, position PHP equivalente cote test). Toujours regime BORNE,
    // --- ancre sur la longueur seule -> 1 requete fusionnee. ---
    $byPosition = $solver->solve('9-buchstaben/position/3/a');
    Assert::notNull($byPosition);
    Assert::same(1, $byPosition->queryCount, 'regime BORNE, ancrage longueur seule -> fusionne a 1 requete');
    $expectedByPosition = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE length = 9 AND substr(normalized, 3, 1) = 'A'")->fetch()['c'];
    Assert::true(!$byPosition->truncated, 'sanity check : panier "9-buchstaben, A en 3e position" reste sous le plafond');
    Assert::same($expectedByPosition, $byPosition->total);
    foreach ($byPosition->items as $item) {
        Assert::same(9, $item['length']);
        Assert::same('A', mb_substr($item['normalized'], 2, 1), $item['normalized'] . ' doit avoir A en 3e position (index 2, 0-based)');
    }

    // --- Position combinee a un prefixe explicite : les deux predicats s'appliquent ensemble. ---
    $positionWithPrefix = $solver->solve('9-buchstaben/beginnend-mit/c/position/3/a');
    Assert::notNull($positionWithPrefix);
    $expectedPositionWithPrefix = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE length = 9 AND normalized >= 'C' AND normalized < 'D' AND substr(normalized, 3, 1) = 'A'")->fetch()['c'];
    Assert::same($expectedPositionWithPrefix, $positionWithPrefix->total);
    foreach ($positionWithPrefix->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'C'));
        Assert::same('A', mb_substr($item['normalized'], 2, 1));
    }

    // --- Collapse des positions degenerees : position 1 et position = longueur doivent
    // --- produire EXACTEMENT le meme resultat que beginnend-mit/endend-mit seuls. ---
    $collapsedFirst = $solver->solve('5-buchstaben/position/1/a');
    $equivalentPrefix = $solver->solve('5-buchstaben/beginnend-mit/a');
    Assert::notNull($collapsedFirst);
    Assert::notNull($equivalentPrefix);
    Assert::same($equivalentPrefix->total, $collapsedFirst->total, 'position/1/a doit collapser vers un resultat identique a beginnend-mit/a');
    Assert::same($equivalentPrefix->canonicalPath, $collapsedFirst->canonicalPath, 'meme chemin canonique -- une seule URL indexable pour cette liste');

    $collapsedLast = $solver->solve('5-buchstaben/position/5/a');
    $equivalentSuffix = $solver->solve('5-buchstaben/endend-mit/a');
    Assert::notNull($collapsedLast);
    Assert::notNull($equivalentSuffix);
    Assert::same($equivalentSuffix->total, $collapsedLast->total, 'position/5/a doit collapser vers un resultat identique a endend-mit/a');
    Assert::same($equivalentSuffix->canonicalPath, $collapsedLast->canonicalPath);

    // --- Commencant/endend-mit multi-buchstaben : quelques cas representatifs (2 lettres deja
    // --- couvertes ci-dessus, "beginnend-mit/qi"). ---
    $prefix3 = $solver->solve('beginnend-mit/ant');
    Assert::notNull($prefix3);
    Assert::true($prefix3->exact, 'beginnend-mit seul reste toujours en regime EXACT');
    Assert::same(2, $prefix3->queryCount);
    $expectedPrefix3 = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'ANT' AND normalized < 'ANU'")->fetch()['c'];
    Assert::same($expectedPrefix3, $prefix3->total);
    foreach ($prefix3->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'ANT'));
    }

    $prefix4 = $solver->solve('beginnend-mit/anti');
    Assert::notNull($prefix4);
    Assert::true($prefix4->exact);
    $expectedPrefix4 = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'ANTI' AND normalized < 'ANTJ'")->fetch()['c'];
    Assert::same($expectedPrefix4, $prefix4->total);

    $suffix3 = $solver->solve('endend-mit/ing');
    Assert::notNull($suffix3);
    Assert::same(2, $suffix3->queryCount, 'regime BORNE ancrage reversed (endend-mit seul), quel que soit le nombre de lettres');
    $expectedSuffix3 = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized LIKE '%ING'")->fetch()['c'];
    Assert::same($expectedSuffix3, $suffix3->total);
    foreach ($suffix3->items as $item) {
        Assert::true(str_ends_with($item['normalized'], 'ING'));
    }

    $suffix4 = $solver->solve('endend-mit/zing');
    Assert::notNull($suffix4);
    $expectedSuffix4 = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized LIKE '%ZING'")->fetch()['c'];
    Assert::same($expectedSuffix4, $suffix4->total);

    // --- Cas degenere proche de Z (rangeBounds() sans borne superieure quand le prefixe/
    // --- suffixe n'est fait que de 'Z') : "endend-mit/zz" est un cas reel, "beginnend-mit/zzzz"
    // --- n'existe pas -- les deux doivent rester rapides et corrects. ---
    $suffixZZ = $solver->solve('endend-mit/zz');
    Assert::notNull($suffixZZ);
    $expectedSuffixZZ = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized LIKE '%ZZ'")->fetch()['c'];
    Assert::same($expectedSuffixZZ, $suffixZZ->total);
    Assert::true($expectedSuffixZZ > 0, 'sanity check : au moins un mot allemand doit se terminer par ZZ');
    foreach ($suffixZZ->items as $item) {
        Assert::true(str_ends_with($item['normalized'], 'ZZ'));
    }

    $prefixZZZZ = $solver->solve('beginnend-mit/zzzz');
    Assert::notNull($prefixZZZZ);
    Assert::same(0, $prefixZZZZ->total, 'aucun mot allemand ne commence par ZZZZ -- rangeBounds() sans borne superieure doit rester correct (panier vide, pas une erreur)');

    // --- Collapse "mit-buchstaben/X" redondant avec un beginnend-mit/endend-mit d'une seule lettre X
    // --- (heritee du site francais), verifie ici via le VRAI solveur : force brute sur les
    // --- 26 lettres pour chaque famille. ---
    foreach (range('A', 'Z') as $x) {
        $degeneratePrefix = $solver->solve('beginnend-mit/' . strtolower($x) . '/mit-buchstaben/' . strtolower($x));
        $simplePrefix = $solver->solve('beginnend-mit/' . strtolower($x));
        Assert::notNull($degeneratePrefix);
        Assert::notNull($simplePrefix);
        Assert::same($simplePrefix->total, $degeneratePrefix->total, "beginnend-mit/$x/mit-buchstaben/$x doit avoir le meme total que beginnend-mit/$x seul");
        Assert::same($simplePrefix->truncated, $degeneratePrefix->truncated, "beginnend-mit/$x/mit-buchstaben/$x : meme statut truncated que beginnend-mit/$x seul");
        Assert::same($simplePrefix->exact, $degeneratePrefix->exact, "beginnend-mit/$x/mit-buchstaben/$x : meme regime exact que beginnend-mit/$x seul");
        Assert::same($simplePrefix->canonicalPath, $degeneratePrefix->canonicalPath, "beginnend-mit/$x/mit-buchstaben/$x doit collapser vers le meme canonicalPath que beginnend-mit/$x");
        Assert::same($simplePrefix->queryCount, $degeneratePrefix->queryCount, "beginnend-mit/$x/mit-buchstaben/$x : meme budget de requetes que beginnend-mit/$x seul");
        Assert::true(!$degeneratePrefix->truncated, "beginnend-mit/$x/mit-buchstaben/$x ne doit plus jamais etre tronque a tort ($x)");

        $degenerateSuffix = $solver->solve('endend-mit/' . strtolower($x) . '/mit-buchstaben/' . strtolower($x));
        $simpleSuffix = $solver->solve('endend-mit/' . strtolower($x));
        Assert::notNull($degenerateSuffix);
        Assert::notNull($simpleSuffix);
        Assert::same($simpleSuffix->total, $degenerateSuffix->total, "endend-mit/$x/mit-buchstaben/$x doit avoir le meme total que endend-mit/$x seul");
        Assert::same($simpleSuffix->truncated, $degenerateSuffix->truncated, "endend-mit/$x/mit-buchstaben/$x : meme statut truncated que endend-mit/$x seul");
        Assert::same($simpleSuffix->canonicalPath, $degenerateSuffix->canonicalPath, "endend-mit/$x/mit-buchstaben/$x doit collapser vers le meme canonicalPath que endend-mit/$x");
        Assert::same($simpleSuffix->queryCount, $degenerateSuffix->queryCount, "endend-mit/$x/mit-buchstaben/$x : meme budget de requetes que endend-mit/$x seul");
    }

    // --- Cas emblematique (pire divergence possible) : A, prefixe le plus frequent de la
    // --- base allemande (contrairement au francais ou R l'est). Avant le correctif D-032,
    // --- "beginnend-mit/a/mit-buchstaben/a" aurait plafonne a ROW_EXAMINATION_CEILING (regime BORNE)
    // --- alors que le vrai total (regime EXACT de "beginnend-mit/a" seul) est bien plus grand
    // --- -- verifie ici que le vrai total exact est desormais renvoye, sans aucun plafond. ---
    $worstCaseA = $solver->solve('beginnend-mit/a/mit-buchstaben/a');
    Assert::notNull($worstCaseA);
    $bruteForceA = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'A' AND normalized < 'B'")->fetch()['c'];
    Assert::true($bruteForceA > WordListSolver::ROW_EXAMINATION_CEILING, 'sanity check : A doit rester le prefixe le plus frequent, largement au-dessus du plafond');
    Assert::same($bruteForceA, $worstCaseA->total, 'beginnend-mit/a/mit-buchstaben/a doit desormais renvoyer le vrai total exact, jamais plafonne');
    Assert::true(!$worstCaseA->truncated);
    Assert::true($worstCaseA->exact, 'regime EXACT retrouve une fois le avec redondant retire');
    Assert::same('beginnend-mit/a', $worstCaseA->canonicalPath, 'WordListPage::$canonicalPath ne porte jamais le prefixe "/woerter"');

    // --- Non-regression : lettre "mit-buchstaben" DIFFERENTE du prefixe -- doit rester en regime
    // --- BORNE plafonne exactement comme avant (vrai predicat, jamais retire). ---
    $filtersNonRedundant = WordListFilters::fromPath('beginnend-mit/a/mit-buchstaben/y');
    Assert::notNull($filtersNonRedundant);
    Assert::same(['Y' => 1], $filtersNonRedundant->withLetters, 'mit-buchstaben/y non redondant avec beginnend-mit/a : jamais retire');

    $realConstraintPrefix = $solver->solve('beginnend-mit/a/mit-buchstaben/y');
    Assert::notNull($realConstraintPrefix);
    $bruteForceAY = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'A' AND normalized < 'B' AND instr(normalized, 'Y') > 0")->fetch()['c'];
    Assert::true(!$realConstraintPrefix->truncated, 'sanity check : beginnend-mit/a/mit-buchstaben/y (' . $bruteForceAY . ' correspondances) doit rester sous le plafond');
    Assert::same($bruteForceAY, $realConstraintPrefix->total, 'total correct pour un "mit-buchstaben" non redondant, jamais collapse');

    // --- Non-regression : minCount >= 2 pour la meme lettre que le prefixe ("mit-buchstaben/a/a", un
    // --- DEUXIEME A exige) -- jamais retire, reste un vrai predicat en regime BORNE. ---
    $minCountTwoPrefix = $solver->solve('beginnend-mit/a/mit-buchstaben/a/a');
    Assert::notNull($minCountTwoPrefix);
    $bruteForceAA = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE normalized >= 'A' AND normalized < 'B' AND (LENGTH(normalized) - LENGTH(REPLACE(normalized, 'A', ''))) >= 2")->fetch()['c'];
    Assert::true($bruteForceAA > WordListSolver::ROW_EXAMINATION_CEILING, 'sanity check : avec/a/a doit reellement depasser le plafond pour que ce test ait un sens, obtenu ' . $bruteForceAA);
    Assert::true($minCountTwoPrefix->truncated, 'mit-buchstaben/a/a (minCount=2) reste un vrai predicat non collapse : panier reellement au-dessus du plafond');
    Assert::same(WordListSolver::ROW_EXAMINATION_CEILING, $minCountTwoPrefix->total, 'total plafonne, jamais le vrai total exact -- preuve que ce cas n\'est PAS collapse comme avec/a (minCount=1) l\'est');
    $simpleAPrefix = $solver->solve('beginnend-mit/a');
    Assert::true($minCountTwoPrefix->total < $simpleAPrefix->total, 'exiger un deuxieme A doit reellement restreindre le panier par rapport a beginnend-mit/a seul');

    // =====================================================================
    // Regression specifique au site allemand (coeur de cette tache) : beginnend-mit/{Ä,Ö,Ü}
    // doit fonctionner exactement comme n'importe quelle autre lettre -- verifie par force
    // brute, plus le predicat "mit-buchstaben" combine a une lettre Ö.
    // =====================================================================

    $byPrefixUmlaut = $solver->solve('beginnend-mit/ö');
    Assert::notNull($byPrefixUmlaut, 'beginnend-mit/ö doit produire une liste, pas une entree invalide');
    Assert::true($byPrefixUmlaut->exact, 'prefixe seul, meme Ö, reste en regime EXACT');
    // Methode de comptage INDEPENDANTE de rangeBounds() : substr(normalized,1,1) = 'Ö'
    // (SQLite compte deja par CARACTERE, pas par octet) plutot qu'une plage >=/<, pour
    // eviter toute circularite avec le mecanisme teste.
    $expectedUmlautPrefix = (int) $pdo->query("SELECT COUNT(*) c FROM terms WHERE substr(normalized, 1, 1) = 'Ö'")->fetch()['c'];
    Assert::same($expectedUmlautPrefix, $byPrefixUmlaut->total, 'beginnend-mit/ö : total exact, verifie par une methode de comptage independante (substr)');
    Assert::true($byPrefixUmlaut->total > 0, 'sanity check : au moins un mot allemand doit commencer par Ö');
    foreach ($byPrefixUmlaut->items as $item) {
        Assert::true(str_starts_with($item['normalized'], 'Ö'), $item['normalized'] . ' devrait commencer par Ö');
    }
};
