<?php

declare(strict_types=1);

use App\Search\WordListFilters;
use Tests\Support\Assert;

/**
 * App\Search\WordListFilters : analyse et canonicalisation des contraintes de /woerter/...
 * (Phase 3), independamment de toute base de donnees -- meme esprit que RackTest.php pour
 * App\Search\Rack.
 */
return function (): void {
    // --- Longueur seule. ---
    $length = WordListFilters::fromPath('7-buchstaben');
    Assert::notNull($length);
    Assert::same(7, $length->length);
    Assert::true($length->isEmpty() === false);
    Assert::same('/woerter/7-buchstaben', $length->canonicalUrl());

    // --- Prefixe seul, insensible a la casse et aux accents (Normalizer::normalize, D-009). ---
    $prefix = WordListFilters::fromPath('beginnend-mit/CH');
    Assert::notNull($prefix);
    Assert::same('CH', $prefix->prefix);
    Assert::same('/woerter/beginnend-mit/ch', $prefix->canonicalUrl());

    $accentedPrefix = WordListFilters::fromPath('beginnend-mit/éÉ');
    Assert::notNull($accentedPrefix);
    Assert::same('EE', $accentedPrefix->prefix, 'accents retires par Normalizer::normalize()');

    // --- Longueur + prefixe combines, dans l'ordre canonique recu (deja correct ici). ---
    $combo = WordListFilters::fromPath('7-buchstaben/beginnend-mit/ch');
    Assert::notNull($combo);
    Assert::same(7, $combo->length);
    Assert::same('CH', $combo->prefix);
    Assert::same('/woerter/7-buchstaben/beginnend-mit/ch', $combo->canonicalUrl());

    // --- Terminant. ---
    $suffix = WordListFilters::fromPath('endend-mit/tion');
    Assert::notNull($suffix);
    Assert::same('TION', $suffix->suffix);

    // --- Prefixe/endend-mit multi-buchstaben (tache de dimensionnement "beginnend-mit/endend-mit
    // --- multi-buchstaben", 2026-08-18) : readSingleLetterRun() accepte deja 1 a 15 lettres --
    // --- verifie explicitement ici la longueur 4 (borne haute retenue par cette tache, 2 a 4
    // --- lettres), jamais mesuree avant ce jour. ---
    $prefix4 = WordListFilters::fromPath('beginnend-mit/anti');
    Assert::notNull($prefix4);
    Assert::same('ANTI', $prefix4->prefix);
    Assert::same('/woerter/beginnend-mit/anti', $prefix4->canonicalUrl());

    $suffix4 = WordListFilters::fromPath('endend-mit/zing');
    Assert::notNull($suffix4);
    Assert::same('ZING', $suffix4->suffix);
    Assert::same('/woerter/endend-mit/zing', $suffix4->canonicalUrl());

    // --- Contenant. ---
    $contains = WordListFilters::fromPath('enthalten/che');
    Assert::notNull($contains);
    Assert::same('CHE', $contains->contains);

    // --- Avec : repetitions comptees, triees par lettre. ---
    $with = WordListFilters::fromPath('mit-buchstaben/a/a/r');
    Assert::notNull($with);
    Assert::same(['A' => 2, 'R' => 1], $with->withLetters);
    Assert::same('/woerter/mit-buchstaben/a/a/r', $with->canonicalUrl(), 'ordre canonique alphabetique, repetitions regroupees');

    // Ordre de saisie sans effet sur le resultat (meme principe que Rack : multiensemble).
    $withReordered = WordListFilters::fromPath('mit-buchstaben/r/a/a');
    Assert::notNull($withReordered);
    Assert::same($with->withLetters, $withReordered->withLetters);
    Assert::same($with->canonicalUrl(), $withReordered->canonicalUrl());

    // "mit-buchstaben" sans aucune lettre : entree malformee, pas un resultat vide.
    Assert::null(WordListFilters::fromPath('mit-buchstaben'));

    // --- Sans : lettres distinctes, sans notion de repetition, deduplique et triees. ---
    $without = WordListFilters::fromPath('ohne/z/x/z');
    Assert::notNull($without);
    Assert::same(['X', 'Z'], $without->withoutLetters);

    // --- Motif : longueur derivee, prefixe initial detecte, cases connues preservees. ---
    $pattern = WordListFilters::fromPath('5-buchstaben/muster/c--e-');
    Assert::notNull($pattern);
    Assert::same('C--E-', $pattern->pattern);
    Assert::same(5, $pattern->length, 'la longueur du motif prevaut');
    Assert::true($pattern->needsUnindexedPredicates(), 'ce motif a une case connue (E) au-dela du prefixe initial (C) -> predicat non indexe necessaire');

    // Motif entierement fait de '-' : refuse, n'apporte rien qu'une longueur ne dise deja.
    Assert::null(WordListFilters::fromPath('5-buchstaben/muster/-----'));

    // Motif dont la longueur explicite ne correspond pas au segment "{N}-buchstaben" fourni :
    // pas une 404, la longueur du motif prevaut -- l'URL canonique se corrige elle-meme,
    // et le routeur redirige en 301 (meme esprit que toute autre permutation, docs/05).
    $mismatched = WordListFilters::fromPath('6-buchstaben/muster/c--e-');
    Assert::notNull($mismatched);
    Assert::same(5, $mismatched->length);
    Assert::same('/woerter/5-buchstaben/muster/c--e-', $mismatched->canonicalUrl());

    // --- Canonicalisation : ordre impose quel que soit l'ordre recu (docs/05). ---
    $permuted = WordListFilters::fromPath('endend-mit/tion/beginnend-mit/ch');
    Assert::notNull($permuted);
    Assert::same('/woerter/beginnend-mit/ch/endend-mit/tion', $permuted->canonicalUrl());

    $fullOrder = WordListFilters::fromPath('ohne/z/7-buchstaben/muster/-------/mit-buchstaben/a/beginnend-mit/a');
    // motif tout-tirets refuse plus haut dans la chaine -> attendu null ici aussi (verifie
    // que le refus d'un segment ne laisse pas les autres segments partiellement acceptes).
    Assert::null($fullOrder);

    // --- ORDRE CANONIQUE COMPLET (D-DE-015) : la localisation des mots-cles ne doit RIEN
    // --- changer a l'ordre impose, seulement au vocabulaire. Aucun test ne verrouillait
    // --- jusqu'ici l'ordre relatif de plus de deux mots-cles a la fois (le seul candidat,
    // --- $fullOrder ci-dessus, attend null a cause du motif tout-tirets) : ajoute ici pour
    // --- qu'un reordonnancement accidentel de WordListFilters::KEYWORDS soit detecte.
    // --- Ordre attendu, inchange depuis D-DE-009 : longueur -> beginnend-mit -> enthalten ->
    // --- endend-mit -> position -> mit-buchstaben -> ohne -> muster -> status -> sortierung.
    // --- La longueur doit ouvrir le chemin (token positionnel, verifie plus bas) ; tous les
    // --- autres segments sont volontairement fournis a l'envers de l'ordre canonique.
    $scrambled = WordListFilters::fromPath(
        '7-buchstaben/sortierung/points/status/admis/ohne/z/mit-buchstaben/b/endend-mit/en/enthalten/rr/beginnend-mit/a'
    );
    Assert::notNull($scrambled, 'combinaison complete valide, quel que soit l\'ordre de saisie');
    Assert::same(
        '/woerter/7-buchstaben/beginnend-mit/a/enthalten/rr/endend-mit/en/mit-buchstaben/b/ohne/z/status/admis/sortierung/points',
        $scrambled->canonicalUrl(),
        'ordre canonique reserialise a l\'identique apres localisation des mots-cles'
    );

    // Meme verification avec "position" (incompatible avec "muster", jamais combinables) :
    // "position" reste entre "endend-mit" et "mit-buchstaben" dans l'ordre canonique.
    $scrambledPosition = WordListFilters::fromPath('9-buchstaben/sortierung/points-desc/mit-buchstaben/b/position/3/x/beginnend-mit/a');
    Assert::notNull($scrambledPosition);
    Assert::same(
        '/woerter/9-buchstaben/beginnend-mit/a/position/3/x/mit-buchstaben/b/sortierung/points-desc',
        $scrambledPosition->canonicalUrl(),
        '"position" conserve sa place entre endend-mit et mit-buchstaben'
    );

    // Et avec "muster", qui occupe la place juste avant "status".
    $scrambledPattern = WordListFilters::fromPath('5-buchstaben/status/admis/muster/c--e-');
    Assert::notNull($scrambledPattern);
    Assert::same('/woerter/5-buchstaben/muster/c--e-/status/admis', $scrambledPattern->canonicalUrl(), '"muster" precede toujours "status"');

    // --- Les ANCIENS mots-cles francais (avant D-DE-015) doivent desormais etre des 404 secs,
    // --- jamais silencieusement acceptes : un mot-cle inconnu ne redirige pas, il n'existe pas
    // --- (docs/05, "mot-cle inconnu -> 404, jamais 301"). Aucun d'eux ne peut non plus etre
    // --- pris pour une lettre de "mit-buchstaben"/"ohne" (tous font plus d'un caractere).
    foreach ([
        'contenant/che',
        'avec/a',
        'sans/z',
        '5-buchstaben/motif/c--e-',
        '13-buchstaben/statut/admis',
        '13-buchstaben/tri/points',
    ] as $legacyPath) {
        Assert::null(WordListFilters::fromPath($legacyPath), "ancien mot-cle francais refuse apres D-DE-015 : {$legacyPath}");
    }

    // Les constantes publiques sont la source unique consommee par app/Search/*LinksBuilder,
    // app/View et public/index.php -- verrouillees ici pour qu'un renommage silencieux casse
    // le test plutot que les URL en production.
    Assert::same('beginnend-mit', WordListFilters::KEYWORD_PREFIX);
    Assert::same('enthalten', WordListFilters::KEYWORD_CONTAINS);
    Assert::same('endend-mit', WordListFilters::KEYWORD_SUFFIX);
    Assert::same('position', WordListFilters::KEYWORD_POSITION);
    Assert::same('mit-buchstaben', WordListFilters::KEYWORD_WITH);
    Assert::same('ohne', WordListFilters::KEYWORD_WITHOUT);
    Assert::same('muster', WordListFilters::KEYWORD_PATTERN);
    Assert::same('status', WordListFilters::KEYWORD_STATUS);
    Assert::same('sortierung', WordListFilters::KEYWORD_SORT);

    // --- Pagination : page 1 jamais dans l'URL, page 1 explicite redirige (pas 404). ---
    $noPage = WordListFilters::fromPath('7-buchstaben');
    Assert::notNull($noPage);
    Assert::same(1, $noPage->page);
    Assert::same('/woerter/7-buchstaben', $noPage->canonicalUrl(), 'page 1 jamais refletee dans l\'URL');

    $explicitPageOne = WordListFilters::fromPath('7-buchstaben/page/1');
    Assert::notNull($explicitPageOne, 'page/1 est syntaxiquement valide, pas une entree malformee');
    Assert::same(1, $explicitPageOne->page);
    Assert::same('/woerter/7-buchstaben', $explicitPageOne->canonicalUrl(), 'redirige vers la forme sans /page/1, jamais 404');

    $pageTwo = WordListFilters::fromPath('7-buchstaben/page/2');
    Assert::notNull($pageTwo);
    Assert::same(2, $pageTwo->page);
    Assert::same('/woerter/7-buchstaben/page/2', $pageTwo->canonicalUrl());

    Assert::null(WordListFilters::fromPath('7-buchstaben/page/0'), 'page 0 invalide');
    Assert::null(WordListFilters::fromPath('7-buchstaben/page/-1'), 'page negative invalide');
    Assert::null(WordListFilters::fromPath('7-buchstaben/page/deux'), 'page non numerique invalide');

    // --- Position (D-023) : une lettre connue a une position precise, exige une longueur. ---
    $position = WordListFilters::fromPath('9-buchstaben/position/3/a');
    Assert::notNull($position);
    Assert::same(3, $position->position);
    Assert::same('A', $position->positionLetter);
    Assert::same('/woerter/9-buchstaben/position/3/a', $position->canonicalUrl());
    Assert::true(!$position->isEmpty());
    Assert::true($position->needsUnindexedPredicates(), 'substr() residuel, jamais indexe');

    Assert::null(WordListFilters::fromPath('position/3/a'), 'position sans longueur refusee');
    Assert::null(WordListFilters::fromPath('9-buchstaben/position/10/a'), 'position au-dela de la longueur refusee');
    Assert::null(WordListFilters::fromPath('9-buchstaben/position/0/a'), 'position 0 refusee');
    Assert::null(WordListFilters::fromPath('9-buchstaben/position/3/ab'), 'position avec plus d\'une lettre refusee');
    Assert::null(WordListFilters::fromPath('9-buchstaben/position/3'), 'position sans lettre refusee');
    Assert::null(WordListFilters::fromPath('9-buchstaben/position/3/a/position/4/b'), 'mot-cle position duplique refuse');
    Assert::null(WordListFilters::fromPath('9-buchstaben/muster/--a------/position/3/a'), 'position et motif incompatibles (meme concept, deux vocabulaires) refuses ensemble');

    // Collapse silencieux des positions degenerees (premiere/derniere lettre) vers
    // prefix/suffix -- evite le contenu duplique constate sur motif (voir docblock de classe
    // et reports/query-plans/position-family.md). canonicalPath() n'emet jamais
    // "position/1/..." ni "position/{longueur}/...".
    $firstLetter = WordListFilters::fromPath('5-buchstaben/position/1/a');
    Assert::notNull($firstLetter);
    Assert::null($firstLetter->position, 'collapse vers prefix, position redevient null');
    Assert::same('A', $firstLetter->prefix);
    Assert::same('/woerter/5-buchstaben/beginnend-mit/a', $firstLetter->canonicalUrl());

    $lastLetter = WordListFilters::fromPath('5-buchstaben/position/5/a');
    Assert::notNull($lastLetter);
    Assert::null($lastLetter->position, 'collapse vers suffix, position redevient null');
    Assert::same('A', $lastLetter->suffix);
    Assert::same('/woerter/5-buchstaben/endend-mit/a', $lastLetter->canonicalUrl());

    // Conflits : une position degeneree qui contredit un beginnend-mit/endend-mit explicite deja
    // present (lettre differente) est une contrainte contradictoire -> 404, jamais un choix
    // arbitraire entre les deux. Meme lettre : redondant mais coherent, accepte.
    Assert::null(WordListFilters::fromPath('5-buchstaben/beginnend-mit/b/position/1/a'), 'conflit beginnend-mit=B vs position/1=A');
    Assert::null(WordListFilters::fromPath('5-buchstaben/endend-mit/b/position/5/a'), 'conflit endend-mit=B vs position/5=A');
    $noConflict = WordListFilters::fromPath('5-buchstaben/beginnend-mit/a/position/1/a');
    Assert::notNull($noConflict, 'meme lettre : pas un conflit, collapse accepte');
    Assert::same('/woerter/5-buchstaben/beginnend-mit/a', $noConflict->canonicalUrl());

    // Position combinee a une autre contrainte (pas degeneree) : coexiste normalement.
    $combined = WordListFilters::fromPath('5-buchstaben/beginnend-mit/c/position/3/a');
    Assert::notNull($combined);
    Assert::same('C', $combined->prefix);
    Assert::same(3, $combined->position);
    Assert::same('/woerter/5-buchstaben/beginnend-mit/c/position/3/a', $combined->canonicalUrl());

    // --- Collapse "mit-buchstaben/X" redondant avec un beginnend-mit/endend-mit d'une seule lettre X (D-032) :
    // --- "beginnend-mit/X/mit-buchstaben/X" (minCount = 1) est toujours vrai des que le mot commence deja
    // --- par X -- garder cette entree withLetters ferait basculer a tort en regime BORNE
    // --- plafonne (voir reports/query-plans/beginnend-mit-avec-no-length-full-sweep.md section 5,
    // --- 17/26 cas sous-affichant un total tronque a 10 000 au lieu du vrai total, jusqu'a
    // --- 224 205 pour R). Force brute sur les 26 lettres, cote parsing uniquement (pas
    // --- d'acces base ici, voir WordListSolverTest.php pour la verification via le vrai
    // --- solveur) : chaque combinaison degeneree doit voir son entree withLetters retiree et
    // --- son canonicalUrl() identique a celui de la forme simplifiee.
    foreach (range('a', 'z') as $letter) {
        $degeneratePrefix = WordListFilters::fromPath("beginnend-mit/$letter/mit-buchstaben/$letter");
        $simplePrefix = WordListFilters::fromPath("beginnend-mit/$letter");
        Assert::notNull($degeneratePrefix, "beginnend-mit/$letter/mit-buchstaben/$letter doit rester une entree valide");
        Assert::notNull($simplePrefix);
        Assert::same([], $degeneratePrefix->withLetters, "mit-buchstaben/$letter redondant avec beginnend-mit/$letter doit etre retire");
        Assert::same($simplePrefix->canonicalUrl(), $degeneratePrefix->canonicalUrl(), "beginnend-mit/$letter/mit-buchstaben/$letter doit collapser vers beginnend-mit/$letter");
        Assert::true(!$degeneratePrefix->needsUnindexedPredicates(), "plus aucun predicat non indexe une fois le avec redondant retire ($letter)");

        $degenerateSuffix = WordListFilters::fromPath("endend-mit/$letter/mit-buchstaben/$letter");
        $simpleSuffix = WordListFilters::fromPath("endend-mit/$letter");
        Assert::notNull($degenerateSuffix, "endend-mit/$letter/mit-buchstaben/$letter doit rester une entree valide");
        Assert::notNull($simpleSuffix);
        Assert::same([], $degenerateSuffix->withLetters, "mit-buchstaben/$letter redondant avec endend-mit/$letter doit etre retire");
        Assert::same($simpleSuffix->canonicalUrl(), $degenerateSuffix->canonicalUrl(), "endend-mit/$letter/mit-buchstaben/$letter doit collapser vers endend-mit/$letter");
    }

    // Les DEUX cotes a la fois (beginnend-mit/X ET endend-mit/X ET avec/X) : la meme lettre X est
    // redondante des deux points de vue simultanement, l'entree doit disparaitre une seule fois
    // (unset() est deja idempotent), les deux contraintes beginnend-mit/endend-mit restent.
    $bothSidesDegenerate = WordListFilters::fromPath('5-buchstaben/beginnend-mit/a/endend-mit/a/mit-buchstaben/a');
    Assert::notNull($bothSidesDegenerate);
    Assert::same([], $bothSidesDegenerate->withLetters, 'mit-buchstaben/a redondant des deux cotes a la fois doit etre retire');
    Assert::same('/woerter/5-buchstaben/beginnend-mit/a/endend-mit/a', $bothSidesDegenerate->canonicalUrl());

    // --- Non-regression : cas NON degeneres, qui doivent rester parfaitement inchanges. ---

    // Lettre "mit-buchstaben" differente du prefixe/suffixe : jamais retiree.
    $differentLetter = WordListFilters::fromPath('beginnend-mit/a/mit-buchstaben/b');
    Assert::notNull($differentLetter);
    Assert::same(['B' => 1], $differentLetter->withLetters, 'mit-buchstaben/b non redondant avec beginnend-mit/a : jamais retire');
    Assert::same('/woerter/beginnend-mit/a/mit-buchstaben/b', $differentLetter->canonicalUrl());

    // minCount >= 2 (deuxieme occurrence exigee, "mit-buchstaben/x/x") : PAS redondant avec un prefixe
    // d'une seule lettre -- le mot doit contenir un DEUXIEME X en plus de celui du prefixe,
    // un vrai predicat, jamais garanti par "commence par X" seul.
    $minCountTwo = WordListFilters::fromPath('beginnend-mit/x/mit-buchstaben/x/x');
    Assert::notNull($minCountTwo);
    Assert::same(['X' => 2], $minCountTwo->withLetters, 'mit-buchstaben/x/x (minCount=2) n\'est jamais redondant avec beginnend-mit/x seul');
    Assert::same('/woerter/beginnend-mit/x/mit-buchstaben/x/x', $minCountTwo->canonicalUrl());

    $minCountTwoSuffix = WordListFilters::fromPath('endend-mit/x/mit-buchstaben/x/x');
    Assert::notNull($minCountTwoSuffix);
    Assert::same(['X' => 2], $minCountTwoSuffix->withLetters, 'mit-buchstaben/x/x (minCount=2) n\'est jamais redondant avec endend-mit/x seul');

    // minCount=1 pour X, mais UN AUTRE avec en plus a minCount=2 pour la meme lettre du
    // prefixe -- garde uniquement l'entree strictement redondante, jamais les autres lettres.
    $mixedWithOtherLetters = WordListFilters::fromPath('beginnend-mit/a/mit-buchstaben/a/b/b');
    Assert::notNull($mixedWithOtherLetters);
    Assert::same(['B' => 2], $mixedWithOtherLetters->withLetters, 'seule l\'entree A (redondante) est retiree, B (minCount=2, non redondant) reste');

    // Prefixe/suffixe de PLUSIEURS lettres : hors perimetre de ce collapse (seule la forme
    // mono-lettre est traitee, voir docblock de classe) -- meme si la lettre "mit-buchstaben" fait partie
    // du prefixe multi-buchstaben, elle n'est PAS retiree.
    $multiLetterPrefixUntouched = WordListFilters::fromPath('beginnend-mit/ab/mit-buchstaben/a');
    Assert::notNull($multiLetterPrefixUntouched);
    Assert::same(['A' => 1], $multiLetterPrefixUntouched->withLetters, 'prefixe multi-buchstaben : avec/a jamais retire (hors perimetre de ce collapse)');

    // --- Statut / tri (D-022) : raffinements d'affichage, en derniere position de l'ordre
    // --- canonique (statut avant tri), quel que soit l'ordre recu. ---
    $status = WordListFilters::fromPath('13-buchstaben/status/admis');
    Assert::notNull($status);
    Assert::same('admis', $status->status);
    Assert::same('/woerter/13-buchstaben/status/admis', $status->canonicalUrl());

    $sort = WordListFilters::fromPath('13-buchstaben/sortierung/points-desc');
    Assert::notNull($sort);
    Assert::same('points-desc', $sort->sort);
    Assert::same('/woerter/13-buchstaben/sortierung/points-desc', $sort->canonicalUrl());

    $statusSortReordered = WordListFilters::fromPath('13-buchstaben/sortierung/points/status/admis');
    Assert::notNull($statusSortReordered);
    Assert::same('admis', $statusSortReordered->status);
    Assert::same('points', $statusSortReordered->sort);
    Assert::same('/woerter/13-buchstaben/status/admis/sortierung/points', $statusSortReordered->canonicalUrl(), 'statut toujours avant tri, quel que soit l\'ordre recu');

    // "status" seul, sans longueur : segment valide, vraie contrainte (isEmpty() = false).
    $statusOnly = WordListFilters::fromPath('status/non-admis');
    Assert::notNull($statusOnly);
    Assert::true(!$statusOnly->isEmpty());
    Assert::same('/woerter/status/non-admis', $statusOnly->canonicalUrl());

    // "sortierung" exige toujours une longueur explicite -- refuse sinon (404), y compris avec un
    // autre ancrage (beginnend-mit seul n'est pas mesure pour ce tri, voir WordListSolver).
    Assert::null(WordListFilters::fromPath('sortierung/points'), 'tri sans longueur refuse');
    Assert::null(WordListFilters::fromPath('beginnend-mit/a/sortierung/points'), 'tri sans longueur refuse meme avec un autre ancrage');

    // Valeurs fermees : toute valeur hors de la liste autorisee est refusee, jamais inventee.
    Assert::null(WordListFilters::fromPath('13-buchstaben/status/peut-etre'), 'valeur de statut hors liste fermee');
    Assert::null(WordListFilters::fromPath('13-buchstaben/sortierung/alphabetique'), '"alphabetique" est le defaut implicite (absence de tri), pas une valeur acceptee');
    Assert::null(WordListFilters::fromPath('13-buchstaben/status'), 'statut sans valeur');
    Assert::null(WordListFilters::fromPath('13-buchstaben/sortierung'), 'tri sans valeur');
    Assert::null(WordListFilters::fromPath('13-buchstaben/status/admis/status/non-admis'), 'mot-cle statut duplique');

    // isEmpty() : statut seul est une vraie restriction, tri seul ne peut jamais exister sans
    // longueur (donc jamais un cas isEmpty() a lui seul, deja verifie ci-dessus indirectement).
    Assert::true(WordListFilters::fromPath('')->isEmpty());

    // --- Rejets : hors perimetre, malformes, ou hors bornes -- toujours null, jamais d'exception. ---
    Assert::null(WordListFilters::fromPath('position/3/r'), '"position" hors perimetre de cette phase (absent de docs/08)');
    Assert::null(WordListFilters::fromPath('beginnend-mit/ch/beginnend-mit/ta'), 'mot-cle "beginnend-mit" duplique');
    Assert::null(WordListFilters::fromPath('20-buchstaben'), 'longueur au-dessus de la borne D-010 (15)');
    Assert::null(WordListFilters::fromPath('1-buchstaben'), 'longueur en dessous de la borne (2)');
    Assert::null(WordListFilters::fromPath('beginnend-mit'), 'mot-cle sans valeur');
    Assert::null(WordListFilters::fromPath('inconnu/valeur'), 'mot-cle non reconnu');
    Assert::null(WordListFilters::fromPath('mit-buchstaben/ab'), 'segment "mit-buchstaben" de plus d\'une lettre');
    Assert::null(WordListFilters::fromPath("mit-buchstaben/\xFF\xFE"), 'octets UTF-8 invalides');
    Assert::null(WordListFilters::fromPath('beginnend-mit/ch/7-buchstaben'), 'longueur doit ouvrir le chemin, jamais apparaitre ailleurs');

    // --- Chemin vide : etat interne valide (isEmpty), mais WordListSolver le refuse
    // --- explicitement (hors perimetre de docs/05, jamais expose comme route). ---
    $empty = WordListFilters::fromPath('');
    Assert::notNull($empty);
    Assert::true($empty->isEmpty());
};
