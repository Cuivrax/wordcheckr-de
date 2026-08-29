<?php

declare(strict_types=1);

use App\Search\DuplicatePageResolver;
use App\Search\WordListFilters;
use Tests\Support\Assert;

/**
 * App\Search\DuplicatePageResolver (D-041, garde-fou structurel demandé par le constat C-4 du 4e
 * audit consolidé, docs/DECISIONS.md D-040) : règle de priorité GÉNÉRIQUE entre pages `/woerter/...`
 * au contenu strictement identique, appliquée aux 1 656 groupes trouvés par le balayage du
 * 2026-08-21 (scripts/check_combinatorial_duplicates.php).
 *
 * Ce fichier teste l'ALGORITHME lui-même (comptage de composants, signature de rôles, résolution
 * de groupe) sur des cas construits et sur les précédents déjà tranchés côté produit (D-025, D-038,
 * D-039) -- la vérification de la LISTE FIGÉE de chaque builder (que ce résolveur produit hors
 * ligne) vit dans le fichier de test de chaque builder concerné (ex.
 * StartEndWithLinksBuilderTest.php, AvecThreeLettersLinksBuilderTest.php...).
 */
return function (): void {
    // ============================================================================================
    // componentCount() -- barème exact (docblock de classe) : longueur/commençant/endend-mit/
    // contenant = 1 chacun, position = 2, chaque lettre "mit-buchstaben"/"ohne" = 1.
    // ============================================================================================
    $length1 = WordListFilters::fromPath('7-buchstaben');
    Assert::same(1, DuplicatePageResolver::componentCount($length1), 'longueur seule = 1 composant');

    $commencant1 = WordListFilters::fromPath('beginnend-mit/khr');
    Assert::same(1, DuplicatePageResolver::componentCount($commencant1), 'beginnend-mit seul = 1 composant, quelle que soit la longueur du prefixe');

    $terminant1 = WordListFilters::fromPath('endend-mit/xxes');
    Assert::same(1, DuplicatePageResolver::componentCount($terminant1), 'endend-mit seul = 1 composant, quelle que soit la longueur du suffixe');

    $combinedNoLength = WordListFilters::fromPath('beginnend-mit/x/endend-mit/m');
    Assert::same(2, DuplicatePageResolver::componentCount($combinedNoLength), 'beginnend-mit+endend-mit sans longueur = 2 composants');

    $combinedWithLength = WordListFilters::fromPath('5-buchstaben/beginnend-mit/x/endend-mit/m');
    Assert::same(3, DuplicatePageResolver::componentCount($combinedWithLength), 'beginnend-mit+endend-mit avec longueur = 3 composants');

    $avecSingle = WordListFilters::fromPath('2-buchstaben/mit-buchstaben/w');
    Assert::same(2, DuplicatePageResolver::componentCount($avecSingle), 'longueur + 1 lettre avec = 2 composants');

    $avecTwo = WordListFilters::fromPath('10-buchstaben/mit-buchstaben/j/w');
    Assert::same(3, DuplicatePageResolver::componentCount($avecTwo), 'longueur + 2 lettres avec = 3 composants');

    $avecThree = WordListFilters::fromPath('5-buchstaben/mit-buchstaben/b/q/r');
    Assert::same(4, DuplicatePageResolver::componentCount($avecThree), 'longueur + 3 lettres avec = 4 composants');

    $position = WordListFilters::fromPath('9-buchstaben/position/3/a');
    Assert::same(3, DuplicatePageResolver::componentCount($position), 'longueur + position (2 a elle seule) = 3 composants');

    $combinedWithLetter = WordListFilters::fromPath('beginnend-mit/f/endend-mit/q/mit-buchstaben/a');
    Assert::same(3, DuplicatePageResolver::componentCount($combinedWithLetter), 'beginnend-mit+endend-mit+avec (1 lettre) = 3 composants');

    $commencantWithLetter = WordListFilters::fromPath('beginnend-mit/w/mit-buchstaben/j');
    Assert::same(2, DuplicatePageResolver::componentCount($commencantWithLetter), 'beginnend-mit+avec (1 lettre) = 2 composants');

    // ============================================================================================
    // resolveDuplicateWinner() -- règle 1 : le plus petit nombre de composants gagne, sans
    // ambiguïté possible.
    // ============================================================================================
    Assert::same(
        '/woerter/beginnend-mit/x/endend-mit/m',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/beginnend-mit/x/endend-mit/m', '/woerter/5-buchstaben/mit-buchstaben/n/q/s']),
        '2 composants doit battre 4 composants'
    );

    Assert::same(
        '/woerter/endend-mit/faq',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/beginnend-mit/f/endend-mit/q', '/woerter/endend-mit/faq']),
        '1 composant doit battre 2 composants'
    );

    // ============================================================================================
    // resolveDuplicateWinner() -- règle 1 : à égalité de composants entre familles différentes,
    // l'ordre canonique des mots-clés départage (WordListFilters, docblock de classe : longueur ->
    // commençant -> contenant -> endend-mit -> position -> avec -> sans -> motif).
    // ============================================================================================

    // Cas réel principal du balayage du 2026-08-21 (408 groupes) : commençant vs endend-mit, 1
    // composant chacun -- commençant gagne (rôle 1 < rôle 3 dans l'ordre canonique).
    Assert::same(
        '/woerter/beginnend-mit/webj',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/beginnend-mit/webj', '/woerter/endend-mit/wu']),
        'a egalite de composants, beginnend-mit precede endend-mit dans l\'ordre canonique'
    );
    // Ordre de saisie inversé : même résultat, la fonction ne doit jamais dépendre de l'ordre du
    // tableau passé en entrée.
    Assert::same(
        '/woerter/beginnend-mit/webj',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/endend-mit/wu', '/woerter/beginnend-mit/webj']),
        'resultat independant de l\'ordre du tableau passe en entree'
    );

    // Précédent déjà tranché côté produit, D-039 (longueur vs "mit-buchstaben", deux familles à 3 composants
    // chacune) : "5-buchstaben/beginnend-mit/x/endend-mit/m" (signature [longueur, commençant, endend-mit])
    // doit battre "beginnend-mit/x/endend-mit/m/mit-buchstaben/a" (signature [commençant, endend-mit, avec]) --
    // "longueur" (0) précède "commençant" (1) dans l'ordre canonique. Cette règle n'est pas câblée
    // à la main : c'est une conséquence directe de la règle 2 générale, vérifiée ici comme un cas
    // concret plutôt que supposée.
    Assert::same(
        '/woerter/5-buchstaben/beginnend-mit/x/endend-mit/m',
        DuplicatePageResolver::resolveDuplicateWinner([
            '/woerter/5-buchstaben/beginnend-mit/x/endend-mit/m',
            '/woerter/beginnend-mit/x/endend-mit/m/mit-buchstaben/a',
        ]),
        'D-039 : la variante longueur gagne sur la variante avec, meme regle generale que D-025'
    );

    // Position (signature [longueur, position, position]) bat "mit-buchstaben" a 2 lettres (signature
    // [longueur, avec, avec]) -- "position" (4) precede "mit-buchstaben" (5) dans l'ordre canonique.
    Assert::same(
        '/woerter/5-buchstaben/position/4/q',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/5-buchstaben/position/4/q', '/woerter/5-buchstaben/mit-buchstaben/b/q/r']),
        'position bat avec a nombre de composants different (3 vs 4) -- cas simple, pas de tie-break necessaire ici'
    );

    // Cas ou position et "mit-buchstaben" ont le MEME nombre de composants (3) : position doit gagner
    // (role 4 < role 5).
    Assert::same(
        '/woerter/9-buchstaben/position/3/a',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/9-buchstaben/position/3/a', '/woerter/9-buchstaben/mit-buchstaben/a/b']),
        'a 3 composants egaux, position (role 4) precede avec (role 5) dans l\'ordre canonique'
    );

    // ============================================================================================
    // variableComponentDepth() -- longueur du composant à chaîne variable (commençant/enthalten/
    // endend-mit), 0 si absent. Fonction pure, vérifiée directement avant d'exercer
    // resolveDuplicateWinner() dessus.
    // ============================================================================================
    Assert::same(2, DuplicatePageResolver::variableComponentDepth(WordListFilters::fromPath('endend-mit/zt')), 'profondeur = longueur du suffixe (2)');
    Assert::same(3, DuplicatePageResolver::variableComponentDepth(WordListFilters::fromPath('endend-mit/azt')), 'profondeur = longueur du suffixe etendu (3)');
    Assert::same(0, DuplicatePageResolver::variableComponentDepth(WordListFilters::fromPath('7-buchstaben')), 'aucun composant a chaine variable -> profondeur 0');
    Assert::same(5, DuplicatePageResolver::variableComponentDepth(WordListFilters::fromPath('beginnend-mit/wu/endend-mit/abc')), 'beginnend-mit+endend-mit combines : somme des deux longueurs (2 + 3)');

    // ============================================================================================
    // resolveDuplicateWinner() -- règle 2 (constat I-1, 5e audit consolidé) : à égalité de
    // composants ET de signature de rôles au sein de la MÊME famille à chaîne variable
    // (commençant/enthalten/endend-mit), la forme dont le composant variable est le plus COURT
    // gagne -- jamais une comparaison alphabétique naïve du chemin complet.
    //
    // Cas couvert par le bug corrigé : App\Search\SuffixExtensionLinksBuilder ajoute la lettre
    // d'extension EN TÊTE du suffixe ('_' . $suffix dans le LIKE), donc "/woerter/endend-mit/azt"
    // (enfant, suffixe "azt") précède ALPHABÉTIQUEMENT "/woerter/endend-mit/zt" (parent, suffixe "zt")
    // -- une comparaison naïve du route_path complet désignerait à tort l'enfant gagnant. Le
    // parent doit rester gagnant : il est plus général (D-025/D-039) et c'est le seul mot-clé qui
    // reçoit un lien entrant depuis le maillage (SuffixExtensionLinksBuilder ne lie que du parent
    // vers l'enfant, jamais l'inverse) -- retirer le parent supprimerait le seul lien entrant du
    // survivant.
    // ============================================================================================
    Assert::same(
        '/woerter/endend-mit/zt',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/endend-mit/zt', '/woerter/endend-mit/azt']),
        'I-1 : le parent "endend-mit/zt" (2 lettres) doit battre son enfant "endend-mit/azt" (3 lettres), '
        . 'bien que "azt" precede "zt" alphabetiquement'
    );
    // Ordre de saisie inversé : même résultat, la fonction ne doit jamais dépendre de l'ordre du
    // tableau passé en entrée (même garde-fou que le cas commençant/endend-mit croisé plus haut).
    Assert::same(
        '/woerter/endend-mit/zt',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/endend-mit/azt', '/woerter/endend-mit/zt']),
        'I-1 : resultat independant de l\'ordre du tableau passe en entree'
    );

    // Cas symétrique côté "commençant" (App\Search\PrefixExtensionLinksBuilder ajoute la lettre
    // d'extension EN QUEUE du préfixe) : le parent gagnait déjà avant ce correctif -- reste vrai
    // après, désormais via la profondeur plutôt que par coïncidence alphabétique.
    Assert::same(
        '/woerter/beginnend-mit/wu',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/beginnend-mit/wu', '/woerter/beginnend-mit/wub']),
        'le parent "beginnend-mit/wu" (2 lettres) doit battre son enfant "beginnend-mit/wub" (3 lettres)'
    );

    // Profondeur également égale (vraies pages sœurs, même longueur de suffixe, lettres
    // différentes) : la règle 3 (alphabétique) reste le départage final, inchangée par ce
    // correctif.
    Assert::same(
        '/woerter/endend-mit/at',
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/endend-mit/zt', '/woerter/endend-mit/at']),
        'a profondeur egale (2 lettres chacun), "at" precede "zt" alphabetiquement -- vraies soeurs'
    );

    // ============================================================================================
    // resolveDuplicateWinner() -- règle 3 : à égalité de composants ET de signature de rôles
    // (même famille, cas "sœurs"), la forme alphabétiquement la plus petite gagne (D-038).
    // canonicalPath() sérialise toujours les lettres "mit-buchstaben" en ordre alphabétique croissant
    // (ksort(), D-022), donc comparer route_path complet revient au même résultat que "la lettre
    // la plus petite gagne".
    // ============================================================================================
    Assert::same(
        '/woerter/beginnend-mit/x/endend-mit/m/mit-buchstaben/a',
        DuplicatePageResolver::resolveDuplicateWinner([
            '/woerter/beginnend-mit/x/endend-mit/m/mit-buchstaben/l',
            '/woerter/beginnend-mit/x/endend-mit/m/mit-buchstaben/a',
        ]),
        'D-038 : entre deux pages soeurs de la meme famille, la lettre alphabetiquement la plus petite (A < L) gagne'
    );

    Assert::same(
        '/woerter/10-buchstaben/mit-buchstaben/a/w/x',
        DuplicatePageResolver::resolveDuplicateWinner([
            '/woerter/10-buchstaben/mit-buchstaben/e/w/x',
            '/woerter/10-buchstaben/mit-buchstaben/a/w/x',
            '/woerter/10-buchstaben/mit-buchstaben/n/w/x',
        ]),
        'meme regle sur un groupe de 3 pages soeurs (palier 3) : la plus petite (A) gagne parmi A/E/N'
    );

    // ============================================================================================
    // Groupe a N pages (> 2) melangeant plusieurs familles -- verifie que le gagnant est bien
    // l'unique minimum global, pas seulement le meilleur d'une comparaison par paires.
    // ============================================================================================
    Assert::same(
        '/woerter/beginnend-mit/webj',
        DuplicatePageResolver::resolveDuplicateWinner([
            '/woerter/10-buchstaben/mit-buchstaben/j/w',
            '/woerter/beginnend-mit/w/mit-buchstaben/j',
            '/woerter/beginnend-mit/w/endend-mit/l',
            '/woerter/beginnend-mit/webj',
        ]),
        'beginnend-mit/webj (1 composant) doit gagner sur les trois autres (2 ou 3 composants)'
    );

    // ============================================================================================
    // Garde-fous : groupe trop petit, route_path invalide.
    // ============================================================================================
    $threw = false;
    try {
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/beginnend-mit/a']);
    } catch (\InvalidArgumentException) {
        $threw = true;
    }
    Assert::true($threw, 'un groupe de moins de 2 pages doit lever une exception, jamais un resultat silencieux');

    $threw = false;
    try {
        DuplicatePageResolver::resolveDuplicateWinner(['/woerter/beginnend-mit/a', '/wortsuche/abc']);
    } catch (\InvalidArgumentException) {
        $threw = true;
    }
    Assert::true($threw, 'une route hors /woerter doit lever une exception, jamais un resultat silencieux');

    // ============================================================================================
    // Cohérence : componentCount()/roleSignature() sont des fonctions PURES de $filters -- deux
    // appels sur le même route_path doivent toujours produire le même résultat (déterminisme).
    // ============================================================================================
    $filtersA = WordListFilters::fromPath('9-buchstaben/mit-buchstaben/a/b/c');
    $filtersB = WordListFilters::fromPath('9-buchstaben/mit-buchstaben/a/b/c');
    Assert::same(DuplicatePageResolver::componentCount($filtersA), DuplicatePageResolver::componentCount($filtersB), 'componentCount() deterministe');
    Assert::same(DuplicatePageResolver::roleSignature($filtersA), DuplicatePageResolver::roleSignature($filtersB), 'roleSignature() deterministe');

    // compareRoleSignatures() : anti-symetrie et reflexivite de base.
    Assert::same(0, DuplicatePageResolver::compareRoleSignatures([0, 1, 3], [0, 1, 3]), 'signatures identiques -> 0');
    Assert::true(DuplicatePageResolver::compareRoleSignatures([0, 1, 3], [0, 4, 4]) < 0, '[0,1,3] precede [0,4,4] (1 < 4 au 2e jeton)');
    Assert::true(DuplicatePageResolver::compareRoleSignatures([0, 4, 4], [0, 1, 3]) > 0, 'comparaison inverse, signe oppose');
};
