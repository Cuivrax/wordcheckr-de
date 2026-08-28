<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\RelationsFinder;
use Tests\Support\Assert;

/**
 * Exerce App\Search\RelationsFinder sur la vraie base storage/dictionary_de.sqlite
 * (lecture seule) : correction croisee par force brute pour STEIN (mot pivot choisi pour
 * sa richesse dans toutes les categories, equivalent allemand du role joue par POSER cote
 * francais), et deux cas limites -- un mot tres court (AS, 2 lettres, egalement un mot
 * allemand valide) et un mot au plafond de longueur (ABANDONNIERTEST, 15 lettres, D-010).
 *
 * Chaque brute force ci-dessous reimplemente la definition en langage naturel de la
 * categorie de facon INDEPENDANTE du mecanisme de RelationsFinder (candidats explicites,
 * signatures) -- pas un appel a ses methodes privees : l'objectif est de detecter une
 * erreur de logique dans RelationsFinder, pas de la confirmer en circularite.
 *
 * mb_str_split (pas str_split) partout ci-dessous : STEIN/AS/ABANDONNIERTEST ne
 * contiennent pas Ä/Ö/Ü, mais la coherence generale (une seule methode multioctet, jamais
 * un melange) reste la meme discipline que RelationsFinder lui-meme.
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $connection = new Connection($dbPath);
    $pdo = $connection->pdo();
    $finder = new RelationsFinder($connection);

    /** @return list<string> admis, longueur exacte, trie */
    $admittedOfLength = static function (int $length) use ($pdo): array {
        $statement = $pdo->prepare('SELECT normalized FROM terms WHERE length = ? AND is_admitted = 1');
        $statement->execute([$length]);
        $words = array_column($statement->fetchAll(), 'normalized');
        sort($words, SORT_STRING);

        return $words;
    };

    // Deliberement PAS de "SELECT ... WHERE length > ? " fetchAll() ici : ce panier peut
    // depasser 500 000 lignes pour un seuil bas -- le charger entierement en memoire PHP
    // pour le filtrer ensuite est le genre de motif que ce projet interdit au runtime
    // (CLAUDE.md) et qui, meme en test, epuise memory_limit=128M par defaut. Les predicats
    // prefixe/suffixe/contenant sont donc exprimes directement en SQL (substr()/instr(),
    // independants de RelationsFinder -- SQLite calcule, pas PHP, et compte deja par
    // CARACTERE pas par octet) : verite terrain streamee, jamais materialisee en un seul
    // grand tableau.

    /** Nombre de lignes admises, longueur > $length, dont le prefixe des $length premiers
     * caracteres egale $word (rallonges a droite). */
    $countRightExtensions = static function (string $word, int $length) use ($pdo): int {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) c FROM terms WHERE length > ? AND is_admitted = 1 '
            . 'AND substr(normalized, 1, ?) = ?'
        );
        $statement->execute([$length, $length, $word]);

        return (int) $statement->fetch()['c'];
    };

    /** @return list<string> trie, mots admis dont le prefixe egale $word (pas de LIMIT --
     * reserve aux cas ou le compte est deja connu comme raisonnable, ex. STEIN). */
    $fetchRightExtensions = static function (string $word, int $length) use ($pdo): array {
        $statement = $pdo->prepare(
            'SELECT normalized FROM terms WHERE length > ? AND is_admitted = 1 '
            . 'AND substr(normalized, 1, ?) = ? ORDER BY normalized'
        );
        $statement->execute([$length, $length, $word]);

        return array_column($statement->fetchAll(), 'normalized');
    };

    $countLeftExtensions = static function (string $word, int $length) use ($pdo): int {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) c FROM terms WHERE length > ? AND is_admitted = 1 '
            . 'AND substr(normalized, -?) = ?'
        );
        $statement->execute([$length, $length, $word]);

        return (int) $statement->fetch()['c'];
    };

    /** @return list<string> trie */
    $fetchLeftExtensions = static function (string $word, int $length) use ($pdo): array {
        $statement = $pdo->prepare(
            'SELECT normalized FROM terms WHERE length > ? AND is_admitted = 1 '
            . 'AND substr(normalized, -?) = ? ORDER BY normalized'
        );
        $statement->execute([$length, $length, $word]);

        return array_column($statement->fetchAll(), 'normalized');
    };

    /** @return list<string> trie, contient $word mais ni prefixe ni suffixe */
    $fetchContainingWords = static function (string $word, int $length) use ($pdo): array {
        $statement = $pdo->prepare(
            'SELECT normalized FROM terms WHERE length > ? AND is_admitted = 1 '
            . 'AND instr(normalized, ?) > 0 AND substr(normalized, 1, ?) != ? AND substr(normalized, -?) != ? '
            . 'ORDER BY normalized'
        );
        $statement->execute([$length, $word, $length, $word, $length, $word]);

        return array_column($statement->fetchAll(), 'normalized');
    };

    $signatureOf = static function (string $w): string {
        $chars = mb_str_split($w);
        sort($chars, SORT_STRING);

        return implode('', $chars);
    };

    /**
     * Assertion generique : $actual (la liste renvoyee par RelationsFinder, deja plafonnee
     * a DISPLAY_LIMIT_PER_CATEGORY) doit etre un sous-ensemble trie de $expectedFull, et si
     * $expectedFull tient dans le plafond, l'egalite doit etre exacte.
     *
     * @param list<string> $actual
     * @param list<string> $expectedFull deja trie
     */
    $assertCategory = static function (array $actual, array $expectedFull, string $label): void {
        sort($expectedFull, SORT_STRING);
        $limit = RelationsFinder::DISPLAY_LIMIT_PER_CATEGORY;

        if (count($expectedFull) <= $limit) {
            Assert::same($expectedFull, $actual, $label . ' : correspondance exacte attendue (sous le plafond d\'affichage)');

            return;
        }

        Assert::same($limit, count($actual), $label . ' : plafond d\'affichage attendu');
        $expectedDisplayed = array_slice($expectedFull, 0, $limit);
        Assert::same($expectedDisplayed, $actual, $label . ' : premiers elements tries attendus');
    };

    // =====================================================================
    // STEIN -- mot pivot allemand, chiffres verifies par force brute (pas un echantillon).
    // =====================================================================

    $word = 'STEIN';
    $length = mb_strlen($word);
    $relations = $finder->find($word);

    Assert::same(5, $relations->queryCount, 'budget : 5 requetes pour un mot admis (RelationsFinder seul, hors TermLookup)');

    // --- 1. Anagrammes exactes. ---
    $sameLength = $admittedOfLength($length);
    $sig = $signatureOf($word);
    $bruteAnagrams = array_values(array_filter($sameLength, static fn (string $w) => $w !== $word && $signatureOf($w) === $sig));
    $assertCategory(array_column($relations->anagrams, 'normalized'), $bruteAnagrams, 'anagrams');
    Assert::same(['EINST', 'ESTIN', 'INSTE', 'NIEST', 'NIETS', 'NISTE', 'TEINS'], array_column($relations->anagrams, 'normalized'), 'anagrams STEIN : liste exacte connue');

    // --- 2. Changer une lettre : distance de Hamming exactement 1, meme longueur. ---
    $hamming1 = static function (string $a, string $b): bool {
        $la = mb_str_split($a);
        $lb = mb_str_split($b);

        if (count($la) !== count($lb)) {
            return false;
        }
        $diff = 0;
        foreach ($la as $i => $ch) {
            if ($ch !== $lb[$i]) {
                $diff++;
            }
        }

        return $diff === 1;
    };
    $bruteChange = array_values(array_filter($sameLength, static fn (string $w) => $hamming1($w, $word)));
    $assertCategory(array_column($relations->changeOneLetter, 'normalized'), $bruteChange, 'changeOneLetter');
    Assert::same(['SPEIN', 'STEHN', 'STEIF', 'STEIG', 'STEIL', 'STERN'], array_column($relations->changeOneLetter, 'normalized'), 'changeOneLetter STEIN : liste exacte connue');

    // --- 3. Retirer une lettre : sous-sequence obtenue en supprimant exactement 1 caractere. ---
    $shorterByOne = $admittedOfLength($length - 1);
    $isDeletionOf = static function (string $candidate, string $w): bool {
        $letters = mb_str_split($w);

        foreach (array_keys($letters) as $i) {
            $rest = $letters;
            array_splice($rest, $i, 1);

            if (implode('', $rest) === $candidate) {
                return true;
            }
        }

        return false;
    };
    $bruteRemove = array_values(array_filter($shorterByOne, static fn (string $w) => $isDeletionOf($w, $word)));
    $assertCategory(array_column($relations->removeOneLetter, 'normalized'), $bruteRemove, 'removeOneLetter');
    Assert::same(['SEIN', 'TEIN'], array_column($relations->removeOneLetter, 'normalized'), 'removeOneLetter STEIN : liste exacte connue');

    // --- 4. Inserer une lettre : le mot = candidat avec une lettre supprimee. ---
    $longerByOne = $admittedOfLength($length + 1);
    $bruteInsert = array_values(array_filter($longerByOne, static fn (string $w) => $isDeletionOf($word, $w)));
    $assertCategory(array_column($relations->insertOneLetter, 'normalized'), $bruteInsert, 'insertOneLetter');
    Assert::same(['STEINE', 'STEINS', 'STEINT', 'STERIN'], array_column($relations->insertOneLetter, 'normalized'), 'insertOneLetter STEIN : liste exacte connue');

    // --- 5. Sous-mots : sous-chaine CONTIGUE, longueur 2 a N-1. ---
    $bruteSubstrings = [];
    for ($l = 2; $l <= $length - 1; $l++) {
        foreach ($admittedOfLength($l) as $candidate) {
            if (str_contains($word, $candidate)) {
                $bruteSubstrings[] = $candidate;
            }
        }
    }
    $assertCategory(array_column($relations->substrings, 'normalized'), $bruteSubstrings, 'substrings');
    Assert::same(['EI', 'EIN', 'IN', 'ST', 'TEIN'], array_column($relations->substrings, 'normalized'), 'substrings STEIN : liste exacte connue');

    // --- 6/7/8. Rallonges a droite/gauche, mot contenu. ---
    $bruteRight = $fetchRightExtensions($word, $length);
    $bruteLeft = $fetchLeftExtensions($word, $length);
    $bruteContaining = $fetchContainingWords($word, $length);

    Assert::same(count($bruteRight), $relations->rightExtensionsTotal, 'rightExtensions : total exact (sous le plafond)');
    Assert::true(!$relations->rightExtensionsTruncated, 'rightExtensions : pas de troncature attendue pour STEIN');
    $assertCategory(array_column($relations->rightExtensions, 'normalized'), $bruteRight, 'rightExtensions');
    Assert::same(343, count($bruteRight), 'rightExtensions STEIN : 343 mots (verifie a la main sur la base reelle)');

    Assert::same(count($bruteLeft), $relations->leftExtensionsTotal, 'leftExtensions : total exact (sous le plafond)');
    Assert::true(!$relations->leftExtensionsTruncated, 'leftExtensions : pas de troncature attendue pour STEIN');
    $assertCategory(array_column($relations->leftExtensions, 'normalized'), $bruteLeft, 'leftExtensions');
    Assert::same(142, count($bruteLeft), 'leftExtensions STEIN : 142 mots (verifie a la main sur la base reelle)');

    Assert::same(count($bruteContaining), $relations->containingWordsTotal, 'containingWords : total exact (sous le plafond)');
    Assert::true(!$relations->containingWordsTruncated, 'containingWords : pas de troncature attendue pour STEIN (564 < 1000)');
    $assertCategory(array_column($relations->containingWords, 'normalized'), $bruteContaining, 'containingWords');
    Assert::same(564, count($bruteContaining), 'containingWords STEIN : 564 mots (verifie a la main sur la base reelle)');

    // --- 9/10. Anagrammes +1/-1 lettre. ---
    $multisetDiffersByOneAdded = static function (string $candidate, string $w, string $sigW): bool {
        if (mb_strlen($candidate) !== mb_strlen($w) + 1) {
            return false;
        }
        // candidat = w + exactement une lettre : sa signature contient la signature de w
        // comme sous-multiensemble, avec un seul caractere en plus.
        $sigCandidate = mb_str_split($candidate);
        sort($sigCandidate, SORT_STRING);
        $remaining = mb_str_split($sigW);
        $extra = 0;
        foreach ($sigCandidate as $ch) {
            $pos = array_search($ch, $remaining, true);
            if ($pos === false) {
                $extra++;
                continue;
            }
            unset($remaining[$pos]);
            $remaining = array_values($remaining);
        }

        return $extra === 1 && $remaining === [];
    };
    $bruteMinusOne = array_values(array_filter($shorterByOne, static fn (string $w) => $multisetDiffersByOneAdded($word, $w, $signatureOf($w))));
    $assertCategory(array_column($relations->anagramsMinusOne, 'normalized'), $bruteMinusOne, 'anagramsMinusOne');
    Assert::same(['EINS', 'EINT', 'EIST', 'NEST', 'NETS', 'NIES', 'NIET', 'NIST', 'SEIN', 'SEIT', 'SITE', 'TEIN'], array_column($relations->anagramsMinusOne, 'normalized'), 'anagramsMinusOne STEIN : liste exacte connue (12 mots)');

    $bruteFullPlusOne = array_values(array_filter($longerByOne, static fn (string $w) => $multisetDiffersByOneAdded($w, $word, $sig)));
    Assert::same(75, count($bruteFullPlusOne), 'anagramsPlusOne STEIN : 75 mots au total (verifie a la main sur la base reelle)');
    $assertCategory(array_column($relations->anagramsPlusOne, 'normalized'), $bruteFullPlusOne, 'anagramsPlusOne');

    // --- Recherches liees : au plus 12, toutes des URL /woerter/..., /wortsuche/... ou le hub /woerter
    // bien formees. ---
    Assert::true(count($relations->relatedSearches) <= RelationsFinder::MAX_RELATED_SEARCHES, 'relatedSearches : plafond de 12');
    foreach ($relations->relatedSearches as $link) {
        Assert::true(
            $link['url'] === '/woerter' || str_starts_with($link['url'], '/woerter/') || str_starts_with($link['url'], '/wortsuche/'),
            'relatedSearches : URL bien formee -- ' . $link['url'],
        );
    }
    Assert::same(['/woerter/5-buchstaben', '/woerter/beginnend-mit/s', '/woerter/beginnend-mit/ste', '/woerter/endend-mit/in', '/woerter/5-buchstaben/avec/e/i/n', '/wortsuche/einst', '/woerter'], array_column($relations->relatedSearches, 'url'), 'relatedSearches STEIN : selection exacte connue');
    foreach ($relations->relatedSearches as $link) {
        Assert::true(!str_starts_with($link['url'], '/woerter/contenant/'), 'relatedSearches ne doit jamais emettre de lien "contenant" sans ancrage : ' . $link['url']);
    }

    // --- Plafond global "environ 160 liens de mots" (docs/01) : verification que la fiche
    // STEIN, mot volontairement choisi pour generer beaucoup de candidats dans plusieurs
    // categories a la fois, reste dans une enveloppe raisonnable. ---
    $totalLinks = count($relations->anagrams) + count($relations->changeOneLetter) + count($relations->removeOneLetter)
        + count($relations->insertOneLetter) + count($relations->substrings) + count($relations->rightExtensions)
        + count($relations->leftExtensions) + count($relations->containingWords) + count($relations->anagramsPlusOne)
        + count($relations->anagramsMinusOne);
    Assert::true($totalLinks <= 10 * RelationsFinder::DISPLAY_LIMIT_PER_CATEGORY, 'plafond de liens de mots : au plus 10 x DISPLAY_LIMIT_PER_CATEGORY');

    // =====================================================================
    // AS -- mot allemand valide (l'as d'un jeu de cartes), le plus court possible
    // (2 lettres, Normalizer::MIN_LENGTH). Categories structurellement vides : retirer une
    // lettre (1 lettre restante, jamais en base), sous-mots (aucune longueur possible entre
    // 2 et N-1=1).
    // =====================================================================

    $shortRelations = $finder->find('AS');
    Assert::same(5, $shortRelations->queryCount, 'AS : meme budget de 5 requetes qu\'un mot plus long');
    Assert::same([], $shortRelations->removeOneLetter, 'AS : retirer une lettre structurellement vide (1 lettre non stockee)');
    Assert::same([], $shortRelations->substrings, 'AS : sous-mots structurellement vide (aucune longueur 2..N-1 possible)');
    Assert::same([], $shortRelations->anagramsMinusOne, 'AS : anagrammes -1 lettre structurellement vide, meme raison');

    // --- Categories 6/7/8 pour un prefixe/suffixe de 2 lettres tres frequent : verite
    // terrain mesuree directement par COUNT() SQL (pas un chargement massif en memoire
    // PHP), plafond attendu declenche des deux cotes. ---
    $bruteRightAsCount = $countRightExtensions('AS', 2);
    $bruteLeftAsCount = $countLeftExtensions('AS', 2);
    Assert::true($bruteRightAsCount > RelationsFinder::EXTENSION_ROW_CEILING, 'AS : verite terrain, prefixe frequent, doit depasser le plafond (mesure : ' . $bruteRightAsCount . ')');
    Assert::true($bruteLeftAsCount > RelationsFinder::EXTENSION_ROW_CEILING, 'AS : verite terrain, suffixe frequent, doit depasser le plafond (mesure : ' . $bruteLeftAsCount . ')');
    Assert::true($shortRelations->rightExtensionsTruncated, 'AS : rightExtensions doit etre marque tronque');
    Assert::true($shortRelations->leftExtensionsTruncated, 'AS : leftExtensions doit etre marque tronque');
    Assert::same(RelationsFinder::EXTENSION_ROW_CEILING, $shortRelations->rightExtensionsTotal, 'AS : total plafonne, jamais presente comme exact au-dela du plafond');
    Assert::same(RelationsFinder::DISPLAY_LIMIT_PER_CATEGORY, count($shortRelations->rightExtensions), 'AS : liste affichee plafonnee malgre la troncature');

    // =====================================================================
    // ABANDONNIERTEST -- 15 lettres, plafond D-010 (Normalizer::MAX_LENGTH). Categories
    // structurellement vides : inserer une lettre et anagrammes +1 (aucun mot de 16 lettres
    // ne peut jamais exister en base), rallonges a droite/gauche et mot contenu (aucun mot
    // plus long que 15 lettres ne peut jamais exister en base).
    // =====================================================================

    $longWord = 'ABANDONNIERTEST';
    Assert::same(15, mb_strlen($longWord), 'mot de test au plafond exact de longueur');
    $longRelations = $finder->find($longWord);
    Assert::same(5, $longRelations->queryCount, 'ABANDONNIERTEST : meme budget de 5 requetes');
    Assert::same([], $longRelations->insertOneLetter, 'ABANDONNIERTEST : inserer une lettre structurellement vide (D-010, aucun mot de 16 lettres en base)');
    Assert::same([], $longRelations->anagramsPlusOne, 'ABANDONNIERTEST : anagrammes +1 lettre structurellement vide, meme raison');
    Assert::same([], $longRelations->rightExtensions, 'ABANDONNIERTEST : rallonges a droite structurellement vide, meme raison');
    Assert::same(0, $longRelations->rightExtensionsTotal);
    Assert::true(!$longRelations->rightExtensionsTruncated, 'ABANDONNIERTEST : total 0 n\'est pas une troncature');
    Assert::same([], $longRelations->leftExtensions, 'ABANDONNIERTEST : rallonges a gauche structurellement vide, meme raison');
    Assert::same([], $longRelations->containingWords, 'ABANDONNIERTEST : mot contenu structurellement vide, meme raison');

    // Recherches liees toujours bien formees, meme au plafond de longueur.
    Assert::true(count($longRelations->relatedSearches) >= 1 && count($longRelations->relatedSearches) <= RelationsFinder::MAX_RELATED_SEARCHES, 'ABANDONNIERTEST : recherches liees dans les bornes');

    // =====================================================================
    // Regression specifique au site allemand (coeur de cette tache) : SCHÖN, verifie que
    // "changer une lettre" produit bien des candidats contenant Ö (RelationsFinder::
    // ALPHABET etendu a 29 lettres) -- sans ce correctif, aucun candidat Ä/Ö/Ü n'aurait pu
    // etre genere par changeOneLetterCandidates(), et cette categorie manquerait
    // silencieusement toute variante en Ö de SCHÖN.
    // =====================================================================

    $schoenRelations = $finder->find('SCHÖN');
    Assert::same(5, $schoenRelations->queryCount, 'SCHÖN : meme budget de 5 requetes');
    $schoenChangeWords = array_column($schoenRelations->changeOneLetter, 'normalized');
    sort($schoenChangeWords, SORT_STRING);
    Assert::same(['SCHON', 'SCHÖB', 'SCHÖR'], $schoenChangeWords, 'changeOneLetter SCHÖN : liste exacte connue, dont deux candidats en Ö (verifie a la main)');
    Assert::true(
        in_array('SCHÖB', $schoenChangeWords, true) && in_array('SCHÖR', $schoenChangeWords, true),
        'changeOneLetter doit pouvoir produire des candidats contenant Ö, pas seulement A-Z'
    );
};
