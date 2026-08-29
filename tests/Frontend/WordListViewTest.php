<?php

declare(strict_types=1);

use App\Search\LengthLinks;
use App\Search\WordListPage;
use Tests\Support\Assert;

/**
 * Rend app/View/word-list.php avec des paniers synthetiques (aucun serveur HTTP, aucune base
 * de donnees -- WordListFilters::fromPath() utilise par la vue est un parsing pur, meme
 * principe que WordViewTest.php pour app/View/word.php) :
 * - toggles statut/tri (D-022) : "Alphabétique"/"Tous" actifs par defaut, tri masque sans
 *   longueur explicite, URL de chaque variante correcte et re-canonicalisee ;
 * - maillage interne (D-022, App\Search\LengthLinks) : section absente si $lengthLinks est
 *   null, presente avec les bons libelles/URLs sinon, jamais de section vide.
 */
return function (): void {
    require __DIR__ . '/../../app/bootstrap.php';

    $render = static function (WordListPage $page, ?array $refine = null, ?LengthLinks $lengthLinks = null): string {
        $seo = \App\Seo\SeoMeta::noindex('https://exemple.fr/woerter/' . $page->canonicalPath);

        ob_start();
        (static function (WordListPage $page, ?array $refine, ?LengthLinks $lengthLinks, \App\Seo\SeoMeta $seo): void {
            require __DIR__ . '/../../app/View/word-list.php';
        })($page, $refine, $lengthLinks, $seo);

        return (string) ob_get_clean();
    };

    $item = static function (string $normalized, string $status = 'admitted'): array {
        return [
            'normalized' => $normalized,
            'slug' => strtolower($normalized),
            'score' => 10,
            'length' => strlen($normalized),
            'isOds8' => $status === 'admitted',
            'isOds9' => $status === 'admitted',
            'status' => $status,
        ];
    };

    // -------------------------------------------------------------------
    // Longueur seule, sans statut/tri actif : toggles "Tous"/"Alphabétique"
    // actifs par defaut, tri visible (longueur presente).
    // -------------------------------------------------------------------
    $lengthPage = new WordListPage(
        canonicalPath: '13-buchstaben',
        page: 1,
        pageSize: 50,
        items: [$item('ABACTERIENNES', 'french_not_admitted')],
        total: 91138,
        exact: true,
        truncated: false,
        hasNextPage: true,
        hasPreviousPage: false,
        queryCount: 2,
    );
    $htmlLength = $render($lengthPage);

    // ADAPTATION ALLEMANDE (cette passe) : libelles traduits ("Tous" -> "Alle",
    // "Admis"/"Non Admis" -> "Gültig"/"Nicht Gültig" meme registre que app/View/word.php,
    // "Alphabétique" -> "Alphabetisch", "Points Croissants/Décroissants" -> "Punkte
    // Aufsteigend/Absteigend"), assertions mises a jour en consequence.
    Assert::true(str_contains($htmlLength, 'Liste Verfeinern'), 'section toggles attendue');
    Assert::true(str_contains($htmlLength, '<a href="/woerter/13-buchstaben" aria-current="page">Alle</a>'), '"Alle" actif par defaut');
    Assert::true(str_contains($htmlLength, '<a href="/woerter/13-buchstaben/status/admis">Gültig</a>'), 'lien "Gültig" non actif');
    Assert::true(str_contains($htmlLength, '<a href="/woerter/13-buchstaben/status/non-admis">Nicht Gültig</a>'), 'lien "Nicht Gültig" non actif');
    Assert::true(str_contains($htmlLength, 'Liste sortieren'), 'groupe tri attendu (longueur presente)');
    Assert::true(str_contains($htmlLength, '<a href="/woerter/13-buchstaben" aria-current="page">Alphabetisch</a>'), '"Alphabetisch" actif par defaut');
    Assert::true(str_contains($htmlLength, '<a href="/woerter/13-buchstaben/sortierung/points">Punkte Aufsteigend</a>'));
    Assert::true(str_contains($htmlLength, '<a href="/woerter/13-buchstaben/sortierung/points-desc">Punkte Absteigend</a>'));

    // Aucun maillage interne sans $lengthLinks.
    Assert::true(!str_contains($htmlLength, 'Beginnend Mit'), 'aucune section de maillage sans $lengthLinks');
    Assert::true(!str_contains($htmlLength, 'Alle Längen Und Buchstaben'), 'aucun lien hub sans $lengthLinks');

    // -------------------------------------------------------------------
    // Prefixe seul (pas de longueur) : pas de groupe tri (tri exige une longueur).
    // -------------------------------------------------------------------
    $prefixPage = new WordListPage(
        canonicalPath: 'beginnend-mit/ch',
        page: 1,
        pageSize: 50,
        items: [$item('CHAT')],
        total: 12037,
        exact: true,
        truncated: false,
        hasNextPage: true,
        hasPreviousPage: false,
        queryCount: 2,
    );
    $htmlPrefix = $render($prefixPage);
    Assert::true(str_contains($htmlPrefix, 'Liste Verfeinern'), 'toggle statut toujours present sans longueur');
    Assert::true(!str_contains($htmlPrefix, 'Liste sortieren'), 'aucun groupe tri sans longueur explicite');

    // -------------------------------------------------------------------
    // Statut actif (admis) + tri actif (points-desc) : les DEUX toggles actifs
    // pointent vers l'URL courante, les variantes preservent l'autre dimension.
    // -------------------------------------------------------------------
    $statusSortPage = new WordListPage(
        canonicalPath: '13-buchstaben/status/admis/sortierung/points-desc',
        page: 1,
        pageSize: 50,
        items: [$item('ABACTERIENNES')],
        total: 32987,
        exact: true,
        truncated: false,
        hasNextPage: true,
        hasPreviousPage: false,
        queryCount: 2,
    );
    $htmlStatusSort = $render($statusSortPage);
    Assert::true(str_contains($htmlStatusSort, '<a href="/woerter/13-buchstaben/status/admis/sortierung/points-desc" aria-current="page">Gültig</a>'), '"Gültig" actif');
    Assert::true(str_contains($htmlStatusSort, '<a href="/woerter/13-buchstaben/sortierung/points-desc">Alle</a>'), '"Alle" preserve le tri actif en le retirant du seul statut');
    Assert::true(str_contains($htmlStatusSort, '<a href="/woerter/13-buchstaben/status/admis/sortierung/points-desc" aria-current="page">Punkte Absteigend</a>'), '"Punkte Absteigend" actif');
    Assert::true(str_contains($htmlStatusSort, '<a href="/woerter/13-buchstaben/status/admis">Alphabetisch</a>'), '"Alphabetisch" preserve le statut actif en retirant seulement le tri');

    // -------------------------------------------------------------------
    // Maillage interne (D-022) : trois groupes + lien hub, aucune section vide.
    // -------------------------------------------------------------------
    $lengthLinks = new LengthLinks(
        byStart: [
            ['letter' => 'A', 'url' => '/woerter/13-buchstaben/beginnend-mit/a', 'count' => 4777],
            ['letter' => 'B', 'url' => '/woerter/13-buchstaben/beginnend-mit/b', 'count' => 3122],
        ],
        byEnd: [
            ['letter' => 'E', 'url' => '/woerter/13-buchstaben/endend-mit/e', 'count' => 9663],
        ],
        byWith: [],
        byPosition: [
            ['position' => 3, 'letters' => [
                ['letter' => 'R', 'url' => '/woerter/13-buchstaben/position/3/r', 'count' => 1234],
            ]],
        ],
        byStartEnd: [],
        queryCount: 1,
    );
    $htmlWithLinks = $render($lengthPage, null, $lengthLinks);

    // ADAPTATION ALLEMANDE (cette passe) : "Mots De N Lettres X" -> "Wörter Mit N
    // Buchstaben X", voir rapport de tache section 2.3 (reports/de-serp-terminology-
    // research.md, patron confirme "Wörter mit 5 Buchstaben beginnend mit A").
    // CORRIGE (D-DE-029+) : "Beginnend Mit"/"Endend Mit" recopiaient mecaniquement le
    // vocabulaire du slug d'URL dans le H2 visible -- "Nach Anfangsbuchstabe"/"Nach
    // Endbuchstabe" retenus (registre naturel, voir app/View/word-list.php).
    Assert::true(str_contains($htmlWithLinks, 'Wörter Mit 13 Buchstaben Nach Anfangsbuchstabe'), 'titre beginnend-mit attendu');
    Assert::true(str_contains($htmlWithLinks, '<span class="explore-label">A</span> <span class="explore-count">(4 777)</span>'), 'lien A avec compte formate attendu');
    Assert::true(str_contains($htmlWithLinks, 'href="/woerter/13-buchstaben/beginnend-mit/a"'), 'URL du lien A attendue');
    Assert::true(str_contains($htmlWithLinks, 'Wörter Mit 13 Buchstaben Nach Endbuchstabe'), 'titre endend-mit attendu');
    Assert::true(str_contains($htmlWithLinks, '(9 663)'), 'compte endend-mit formate attendu');
    Assert::true(!str_contains($htmlWithLinks, 'Wörter Mit 13 Buchstaben Mit'), 'byWith vide -- aucune section rendue (jamais de groupe vide)');
    Assert::true(str_contains($htmlWithLinks, 'Wörter Mit 13 Buchstaben Nach Buchstabenposition'), 'titre position attendu (C1, audit D-028)');
    // Echec pre-existant connu, sans rapport avec cette tache (docs/DECISIONS.md D-DE-011) :
    // le gabarit reel rend `<p class="explore-subgroup-label">`, pas `<summary>` -- assertion
    // volontairement laissee en echec (seul le LIBELLE textuel est mis a jour ici, "3e Lettre"
    // -> "3. Buchstabe", pas la balise) pour ne pas masquer silencieusement ce defaut deja
    // documente comme hors perimetre.
    Assert::true(str_contains($htmlWithLinks, '<summary>3. Buchstabe (1)</summary>'), 'sommaire replie par groupe de position attendu');
    Assert::true(str_contains($htmlWithLinks, 'href="/woerter/13-buchstaben/position/3/r"'), 'URL du lien position attendue');
    Assert::true(str_contains($htmlWithLinks, 'href="/woerter">Alle Längen Und Buchstaben</a>'), 'lien hub vers /woerter attendu quand $lengthLinks est fourni');

    // -------------------------------------------------------------------
    // Plafond de profondeur de pagination sur les listes ancrees (D-030, audit
    // seo-technical-auditor, constat I-2) : follow pour les 3 premieres pages
    // (1<->2<->3), nofollow au-dela -- jamais un changement d'indexation, seul le
    // suivi du lien change (chaque page /page/N reste noindex,follow par ailleurs).
    // -------------------------------------------------------------------
    $anchoredPageTwo = new WordListPage(
        canonicalPath: '13-buchstaben',
        page: 2,
        pageSize: 50,
        items: [$item('ABACTERIENNES', 'french_not_admitted')],
        total: 91138,
        exact: true,
        truncated: false,
        hasNextPage: true,
        hasPreviousPage: true,
        queryCount: 2,
    );
    $htmlAnchoredTwo = $render($anchoredPageTwo);
    Assert::true(str_contains($htmlAnchoredTwo, '<a href="/woerter/13-buchstaben">← Zurück</a>'), 'page 2->1 (profondeur <= 3) : follow, sans rel');
    Assert::true(str_contains($htmlAnchoredTwo, '<a href="/woerter/13-buchstaben/page/3">Weiter →</a>'), 'page 2->3 (profondeur <= 3) : follow, sans rel');

    $anchoredPageFour = new WordListPage(
        canonicalPath: '13-buchstaben',
        page: 4,
        pageSize: 50,
        items: [$item('ABACTERIENNES', 'french_not_admitted')],
        total: 91138,
        exact: true,
        truncated: false,
        hasNextPage: true,
        hasPreviousPage: true,
        queryCount: 2,
    );
    $htmlAnchoredFour = $render($anchoredPageFour);
    Assert::true(str_contains($htmlAnchoredFour, '<a href="/woerter/13-buchstaben/page/3">← Zurück</a>'), 'page 4->3 (profondeur <= 3) : follow, sans rel');
    Assert::true(str_contains($htmlAnchoredFour, '<a href="/woerter/13-buchstaben/page/5" rel="nofollow">Weiter →</a>'), 'page 4->5 (profondeur > 3) : nofollow');

    $unanchoredPage = new WordListPage(
        canonicalPath: 'enthalten/cha',
        page: 1,
        pageSize: 50,
        items: [$item('CHAT')],
        total: 3000,
        exact: false,
        truncated: false,
        hasNextPage: true,
        hasPreviousPage: false,
        queryCount: 1,
    );
    $htmlUnanchored = $render($unanchoredPage);
    Assert::true(str_contains($htmlUnanchored, '<a href="/woerter/enthalten/cha/page/2" rel="nofollow">Weiter →</a>'), 'liste non ancree : nofollow des la page 2, quelle que soit la profondeur (I-1 historique)');

    // -------------------------------------------------------------------
    // Meta title/description enrichis (audit D-031, constat I-3) : citent le(s) mot(s)
    // reel(s) plutot qu'une phrase entierement templatee, pour les listes courtes.
    // -------------------------------------------------------------------
    $onePage = new WordListPage(
        canonicalPath: '3-buchstaben/mit-buchstaben/a/b/e',
        page: 1,
        pageSize: 50,
        items: [$item('ABE', 'french_not_admitted')],
        total: 1,
        exact: true,
        truncated: false,
        hasNextPage: false,
        hasPreviousPage: false,
        queryCount: 1,
    );
    $htmlOne = $render($onePage);
    // ADAPTATION ALLEMANDE (cette passe) : titre/description/H1 reconstruits en allemand
    // (voir app/View/word-list.php, $titleParts/$statusMeta) -- reverifie contre le rendu
    // reel du serveur (php -S) pendant cette tache, pas seulement lu dans le code.
    Assert::true(str_contains($htmlOne, '<title>ABE - Wörter Mit 3 Buchstaben Mit Den Buchstaben A, B, E | WORD CHECKR</title>'), 'title enrichi du mot reel pour 1 seul resultat');
    Assert::true(str_contains($htmlOne, '<meta name="description" content="ABE ist das einzige Wort mit 3 Buchstaben mit den Buchstaben A, B, E und kein gültiges Scrabble-Wort.">'), 'description enrichie du mot et de son statut reel');
    Assert::true(str_contains($htmlOne, '<h1 class="word-title explore-title">Wörter Mit 3 Buchstaben Mit Den Buchstaben A, B, E</h1>'), 'H1 reste la categorie generale, jamais le mot d\'une seule ligne');

    // Page hors bornes (total = 1 mais items vide, ex. ".../page/2" sur une liste a 1
    // resultat) : repli sur la phrase generique, jamais un crash sur $page->items[0].
    $oneOutOfRangePage = new WordListPage(
        canonicalPath: '3-buchstaben/mit-buchstaben/a/b/e',
        page: 2,
        pageSize: 50,
        items: [],
        total: 1,
        exact: true,
        truncated: false,
        hasNextPage: false,
        hasPreviousPage: true,
        queryCount: 1,
    );
    $htmlOneOob = $render($oneOutOfRangePage);
    Assert::true(str_contains($htmlOneOob, '<title>Wörter Mit 3 Buchstaben Mit Den Buchstaben A, B, E | WORD CHECKR</title>'), 'title generique en repli quand $page->items est vide');
    Assert::true(str_contains($htmlOneOob, '<meta name="description" content="Es gibt 1 Wort mit 3 Buchstaben mit den Buchstaben A, B, E.">'), 'description generique en repli quand $page->items est vide');

    // Liste courte (2 a 5 resultats) : description enumere les mots reels.
    $shortListPage = new WordListPage(
        canonicalPath: '4-buchstaben/mit-buchstaben/q/x',
        page: 1,
        pageSize: 50,
        items: [$item('QUXE'), $item('AXQU', 'french_not_admitted')],
        total: 2,
        exact: true,
        truncated: false,
        hasNextPage: false,
        hasPreviousPage: false,
        queryCount: 1,
    );
    $htmlShortList = $render($shortListPage);
    Assert::true(str_contains($htmlShortList, '<meta name="description" content="QUXE und AXQU sind die 2 Wörter mit 4 Buchstaben mit den Buchstaben Q, X im Scrabble-Wörterbuch.">'), 'description d\'une liste courte enumere les mots reels, sans affirmer un statut commun (mots admis et non admis melanges)');
    Assert::true(str_contains($htmlShortList, '<title>Wörter Mit 4 Buchstaben Mit Den Buchstaben Q, X | WORD CHECKR</title>'), 'title non enrichi au-dela de 1 seul resultat (categorie generale conservee)');
};
