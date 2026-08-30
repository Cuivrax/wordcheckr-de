<?php

declare(strict_types=1);

/**
 * Front controller unique, sans framework (CLAUDE.md).
 *
 * Routes livrees en Phase 1 (docs/08) :
 *   GET /                pas de requete SQLite
 *   GET /wort/{wort}       jusqu'a 3 requetes indexees (App\Search\TermLookup::find())
 *   GET /pruefen/{wort}    redirection pure vers /wort/{slug}, 0 requete SQLite
 *   GET /pruefen?mot=..    meme redirection, entree par formulaire GET sans JavaScript
 *
 * Route ajoutee en Phase 2 (docs/08, agent data-engine) :
 *   GET /wortsuche/{buchstaben}  au plus 10 requetes indexees (App\Search\RackSolver::solve()),
 *                         0 requete si le plafond de securite est declenche
 *                         (voir reports/query-plans/phase2.md)
 *   GET /wortsuche?lettres=.. redirection pure vers /wortsuche/{slug}, 0 requete SQLite --
 *                         ajoute par l'agent frontend (rendu du solveur, Phase 2 UI)
 *                         pour que le formulaire chevalet de la home fonctionne sans
 *                         JavaScript, meme principe que /pruefen?mot=.. ci-dessus.
 *                         Fichier partage (CLAUDE.md) : ajout signale pour validation.
 *
 * Route ajoutee en Phase 3 (docs/08, agent data-engine) :
 *   GET /woerter/...       listes de mots par longueur, beginnend-mit, enthalten, endend-mit,
 *                          mit-buchstaben, ohne, muster, seules ou combinees dans l'ordre
 *                          canonique
 *                          (docs/05) -- App\Search\WordListSolver::solve(), au plus 2
 *                          requetes indexees (reports/query-plans/phase3.md). Toute
 *                          permutation non canonique redirige en 301 vers la forme imposee
 *                          (App\Search\WordListFilters::canonicalPath()).
 *
 * ADAPTATION ALLEMANDE (localisation d'URL, D-DE-009, docs/DECISIONS.md) : "/mot" -> "/wort",
 *   "/mots" -> "/woerter", "/jouer" -> "/wortsuche", "/verifier" -> "/pruefen",
 *   "commencant"/"terminant" -> "beginnend-mit"/"endend-mit" -- partout dans ce fichier, routes
 *   ET repli formulaire GET.
 *
 * D-DE-015 (second palier de localisation d'URL, docs/DECISIONS.md) : "contenant"/"avec"/
 *   "sans"/"motif"/"statut"/"tri" -> "enthalten"/"mit-buchstaben"/"ohne"/"muster"/"status"/
 *   "sortierung". "position" reste "position" (substantif allemand a part entiere, cognate
 *   exact) -- voir App\Search\WordListFilters, docblock de classe, pour la source de chaque
 *   terme. Les VALEURS d'enumeration ("admis"/"non-admis", "points"/"points-desc") restent
 *   francaises, TOUJOURS pas traitees ici (perimetre distinct, non couvert par D-DE-020).
 *
 * D-DE-020 : les NOMS DE CHAMP du repli GET (laenge, enthalten, mit-buchstaben, ohne, muster,
 *   wort, buchstaben) sont desormais localises -- fils internes entre app/View/*.php et ce
 *   fichier, jamais visibles dans l'URL finale (toujours une 302 vers la forme canonique),
 *   renommes en meme temps que la regle CSS #muster (public/assets/, ex-#motif, perimetre de
 *   l'agent frontend) dans le meme commit.
 *
 * Route ajoutee en Phase 5 (docs/08, agent data-engine) :
 *   GET /api/suggest?q=..  autocompletion, backend seul (une combobox cote frontend consomme
 *                          cette route dans un lot ulterieur) -- App\Search\Suggester::suggest(),
 *                          exactement 1 requete indexee (reports/query-plans/phase5.md), jamais
 *                          plus de 8 entrees, jamais d'erreur HTTP (entree vide, trop courte ou
 *                          hors A-Z -> tableau JSON vide, code 200). Reponse JSON pure, jamais de
 *                          vue app/View/ -- endpoint volontairement non indexable : aucun lien
 *                          HTML ne pointe vers cette route ailleurs sur le site.
 *
 * Enrichissement D-018 (docs/DECISIONS.md, agent data-engine) : /wort/{wort} passe desormais
 *   'conjugation' (App\Search\Conjugation) a la vue, en plus de 'page'/'relations' -- +1
 *   requete indexee (App\Search\ConjugationLookup::find()), pour tout mot TROUVE, admis ou
 *   non (independant du statut Scrabble). TermPage porte aussi pos/posSecondary/gender
 *   (colonnes ajoutees a `terms`, zero requete supplementaire, deja incluses dans le SELECT
 *   de TermLookup). Budget par fiche : 9 requetes pour un mot admis, 4 pour un mot francais
 *   non admis (reports/query-plans/d018-conjugation.md).
 *
 * Repli additif (Phase 2, refonte du champ unique de la home, agent frontend) :
 *   GET /pruefen?q=..     accepte en plus de ?mot=.. (non supprime), meme redirection
 *   GET /wortsuche?q=..   accepte en plus de ?lettres=.. (non supprime), meme redirection
 *                         Necessaire car un seul <input> HTML ne peut porter qu'un nom :
 *                         la home n'a plus qu'un champ commun aux deux boutons submit
 *                         (formaction differents vers /pruefen et /wortsuche). "mot" et
 *                         "lettres" restent lus en priorite, "q" est un repli, jamais
 *                         un remplacement -- aucune route existante n'est modifiee.
 *                         Fichier partage (CLAUDE.md) : ajout signale pour validation.
 *
 * Etat d'erreur visible (audit final, design-consistency-reviewer, bloquant F3) : toute
 *   saisie invalide sur /pruefen, /wortsuche ou le formulaire GET de /woerter redirige
 *   desormais vers /?erreur=1 ou /woerter?erreur=1 plutot que vers l'URL nue -- "erreur" est
 *   un simple indicateur de presentation lu par app/View/home.php et app/View/explore-hub.php
 *   pour afficher un bandeau ARIA role="alert" (WCAG 3.3.1), jamais une URL indexable distincte
 *   (canonicalPath reste "/" ou "/woerter" dans les deux cas, voir $render).
 *
 * Contrat de rendu : chaque route prepare un nom de vue et un tableau de donnees, puis
 * inclut app/View/{vue}.php (livre par l'agent frontend, Phase 1b) en exposant ce
 * tableau comme variables locales. Tant que app/View/ n'existe pas, un message texte
 * explicite remplace la vue plutot qu'une erreur fatale -- permet de tester le routage
 * et les donnees des maintenant.
 *
 * Fichier partage (CLAUDE.md) : cree ici a la demande explicite de la Phase 1 (docs/08),
 * signale pour validation avant edition future par un autre agent.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Config;
use App\Database\Connection;
use App\Search\AvecSansLengthLinksBuilder;
use App\Search\AvecThreeLettersLinksBuilder;
use App\Search\AvecTwoLettersLinksBuilder;
use App\Search\ConjugationLookup;
use App\Search\ExploreHubBuilder;
use App\Search\LengthCombinedLinksBuilder;
use App\Search\LengthLinksBuilder;
use App\Search\LetterCombinedLinksBuilder;
use App\Search\Normalizer;
use App\Search\PositionLinksBuilder;
use App\Search\PrefixAvecLinksBuilder;
use App\Search\PrefixExtensionLinksBuilder;
use App\Search\Rack;
use App\Search\RackSolver;
use App\Search\RelationsFinder;
use App\Search\SenseLookup;
use App\Search\StartEndWithLinksBuilder;
use App\Search\Suggester;
use App\Search\SuffixExtensionLinksBuilder;
use App\Search\TermLookup;
use App\Search\TermPage;
use App\Search\WordListFilters;
use App\Search\WordListSolver;
use App\Seo\Registry;
use App\Seo\SeoMeta;

const MAX_RAW_SEGMENT_LENGTH = 64;
// /woerter/... peut enchainer plusieurs contraintes (longueur, beginnend-mit, contenant,
// endend-mit, avec, sans, motif -- voir docs/05), donc plusieurs segments : la meme borne
// de 64 par segment individuel serait trop stricte pour la combinaison complete, mais le
// chemin dans son ensemble reste borne pour ecarter tout abus (Phase 3, agent data-engine).
const MAX_RAW_WORDLIST_PATH_LENGTH = 512;

header('Content-Type: text/html; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = rawurldecode($path);

if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

// POST autorise UNIQUEMENT sur /contact (formulaire de contact, D-025ter) -- premiere et
// seule route du site qui accepte une soumission (toutes les autres restent GET, D-007 :
// aucune ecriture sur la base au runtime, ce formulaire n'y touche pas non plus, il envoie
// un email via mail() native PHP). Toute autre methode sur toute autre route reste refusee.
$allowedMethods = $path === '/contact' ? ['GET', 'HEAD', 'POST'] : ['GET', 'HEAD'];

if (!in_array($method, $allowedMethods, true)) {
    http_response_code(405);
    header('Allow: ' . implode(', ', $allowedMethods));
    echo '405 Methode non autorisee';

    return;
}

// CHANGEMENT SIGNALE (fichier partage, CLAUDE.md) : defaut "fr" -> "de". Ce depot est une
// copie independante dediee exclusivement au site allemand (voir CLAUDE.md) -- il n'existe
// aucun config/sites/fr.php ici, laisser "fr" par defaut aurait fait echouer Config::load()
// sur toute requete qui n'exporte pas explicitement SCRABBLE_SITE au niveau de l'hebergement.
$config = Config::load(getenv('SCRABBLE_SITE') ?: 'de');
$seoRegistry = new Registry($config->seoPath, $config->canonicalBaseUrl);

/**
 * Rend une vue via app/View/{name}.php, ou un message d'attente si elle n'existe pas
 * encore. $data est expose comme variables locales dans le fichier de vue.
 *
 * $canonicalPath, quand fourni, doit deja etre la forme canonique exacte servie pour cette
 * requete (Phase 6, agent seo-registry) -- resolue via le registre SEO en une requete
 * indexee sur storage/seo_fr.sqlite, jamais une erreur si la base ou la ligne est absente
 * (App\Seo\Registry::resolve(), noindex,follow par defaut, D-005). null pour les reponses
 * qui n'ont pas d'URL canonique propre (404, JSON) -- la vue omet alors <link rel="canonical">.
 *
 * @param array<string, mixed> $data
 */
$render = static function (string $view, array $data, int $status = 200, ?string $canonicalPath = null) use ($seoRegistry): void {
    http_response_code($status);

    $file = __DIR__ . '/../app/View/' . $view . '.php';

    if (!is_file($file)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Vue non implementee : {$view}\n";
        echo "Donnees pretes, en attente du gabarit (Phase 1b, agent frontend).\n";

        return;
    }

    $data['seo'] = $canonicalPath !== null ? $seoRegistry->resolve($canonicalPath) : SeoMeta::noindex(null);

    (static function (string $__file, array $__data): void {
        extract($__data, EXTR_SKIP);
        require $__file;
    })($file, $data);
};

$redirect = static function (string $location, int $status): void {
    http_response_code($status);
    header('Location: ' . $location);
};

if ($path === '/') {
    $render('home', [
        'minTermLength' => $config->minTermLength,
        'maxTermLength' => $config->maxTermLength,
        'error' => isset($_GET['erreur']),
    ], 200, '/');

    return;
}

if ($path === '/api/suggest') {
    // Phase 5 (docs/08, agent data-engine) : JSON pur, jamais de vue app/View/, jamais
    // d'erreur HTTP -- entree vide/trop courte/hors A-Z -> tableau vide, code 200. Remplace
    // le Content-Type text/html defini plus haut : aucune sortie n'a encore ete envoyee a ce
    // stade, header() peut donc encore le remplacer sans avertissement.
    header('Content-Type: application/json; charset=utf-8');

    $raw = $_GET['q'] ?? '';
    $raw = is_string($raw) ? $raw : '';

    $suggestions = strlen($raw) > MAX_RAW_SEGMENT_LENGTH
        ? []
        : (new Suggester(new Connection($config->dictionaryPath)))->suggest($raw);

    echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);

    return;
}

if (preg_match('#^/wort/([^/]+)$#u', $path, $matches) === 1) {
    $segment = $matches[1];

    if ($segment === '' || strlen($segment) > MAX_RAW_SEGMENT_LENGTH) {
        $render('not-found', ['requestPath' => $path], 404);

        return;
    }

    $connection = new Connection($config->dictionaryPath);
    $lookup = new TermLookup($connection, $config->tileScores);
    $page = $lookup->find($segment);

    if ($page === null) {
        $render('not-found', ['requestPath' => $path], 404);

        return;
    }

    if ($segment !== $page->slug) {
        $redirect('/wort/' . $page->slug, 301);

        return;
    }

    // Relations (Phase 4, docs/08, agent data-engine) : 5 requetes indexees
    // supplementaires (App\Search\RelationsFinder), uniquement pour un mot
    // effectivement admis -- jamais calculees pour "francais non admis" ou
    // "inconnu", ou elles n'auraient aucun sens produit. null sinon, la vue
    // ne rend alors aucune section de relations (voir reports/query-plans/phase4.md).
    $relations = $page->status === TermPage::STATUS_ADMITTED
        ? (new RelationsFinder($connection))->find($page->normalized)
        : null;

    // Conjugaison (D-018) : 1 requete indexee supplementaire (App\Search\ConjugationLookup),
    // pour tout mot TROUVE, admis ou non -- independant du statut Scrabble (docs/DECISIONS.md,
    // D-018). Jamais pour "inconnu" ($page est toujours trouve a ce point du routeur).
    $conjugation = (new ConjugationLookup($connection))->find($page->normalized);

    // Definitions (D-043) : 1 requete indexee supplementaire (App\Search\SenseLookup), meme
    // perimetre que ConjugationLookup ci-dessus -- tout mot TROUVE, admis ou non. Compensee
    // par la fusion des requetes "mot precedent"/"mot suivant" de TermLookup (D-043) : budget
    // dictionnaire reste a 9 requetes pour un mot admis, sous le plafond "moins de 10"
    // (CLAUDE.md) -- voir App\Search\SenseLookup, docblock de budget.
    $senses = (new SenseLookup($connection))->find($page->normalized);

    $render(
        'word',
        ['page' => $page, 'relations' => $relations, 'conjugation' => $conjugation, 'senses' => $senses],
        200,
        '/wort/' . $page->slug,
    );

    return;
}

if ($path === '/wortsuche') {
    // Repli formulaire GET sans JavaScript (home, agent frontend, Phase 2) --
    // meme principe que /pruefen?mot=.. juste au-dessus : 0 requete SQLite,
    // redirection pure vers la forme canonique /wortsuche/{slug} (App\Search\Rack,
    // deja construite par le solveur, reutilisee ici sans le lancer).
    //
    // Fichier partage (CLAUDE.md) : repli supplementaire ajoute par l'agent frontend
    // pour la refonte du champ unique de la home (un seul <input name="q">, deux
    // boutons submit avec des formaction differents vers /pruefen et /wortsuche -- deux
    // noms de champ sur un seul input HTML sont impossibles). "buchstaben" reste lu en
    // premier et continue de fonctionner seul (repli existant, non supprime) ; "q"
    // n'est qu'un second nom accepte, additif uniquement. Renomme depuis "lettres"
    // (D-DE-020, dernier champ GET encore francais, signale comme lot suivant par
    // D-DE-015 -- jamais visible dans l'URL finale, toujours une 302 vers la forme
    // canonique).
    $raw = $_GET['buchstaben'] ?? ($_GET['q'] ?? '');
    $raw = is_string($raw) ? trim($raw) : '';

    if ($raw === '' || strlen($raw) > MAX_RAW_SEGMENT_LENGTH) {
        // F3 (audit final, design-consistency-reviewer) : silencieusement revenir sur "/"
        // sans indication laissait l'utilisateur face a un formulaire vide, sans savoir
        // que sa saisie avait ete rejetee (WCAG 3.3.1). "erreur" est un simple indicateur
        // de presentation, consomme uniquement par app/View/home.php -- jamais une URL
        // indexable distincte (canonicalPath de "/" reste "/", voir $render ci-dessus).
        $redirect('/?erreur=1', 302);

        return;
    }

    $rack = Rack::fromInput($raw);

    if ($rack === null) {
        $redirect('/?erreur=1', 302);

        return;
    }

    $redirect('/wortsuche/' . $rack->slug, 302);

    return;
}

if (preg_match('#^/wortsuche/([^/]+)$#u', $path, $matches) === 1) {
    $segment = $matches[1];

    if ($segment === '' || strlen($segment) > MAX_RAW_SEGMENT_LENGTH) {
        $render('not-found', ['requestPath' => $path], 404);

        return;
    }

    $connection = new Connection($config->dictionaryPath);
    $solver = new RackSolver($connection);
    $page = $solver->solve($segment);

    if ($page === null) {
        $render('not-found', ['requestPath' => $path], 404);

        return;
    }

    if ($segment !== $page->slug) {
        $redirect('/wortsuche/' . $page->slug, 301);

        return;
    }

    // tileScores : les tuiles du chevalet n'affichaient pas leur valeur en points,
    // contrairement a la fiche mot (audit final, C3) -- meme table que partout ailleurs
    // (config/sites/de.php), jamais recalculee.
    $render('play', ['page' => $page, 'tileScores' => $config->tileScores], 200, '/wortsuche/' . $page->slug);

    return;
}

if ($path === '/woerter' || preg_match('#^/woerter(/.*)$#u', $path, $matches) === 1) {
    // Route ajoutee en Phase 3 (docs/08, agent data-engine) : listes de mots par longueur,
    // beginnend-mit, contenant, endend-mit, avec, sans, motif -- App\Search\WordListSolver::
    // solve(), au plus 2 requetes indexees (voir reports/query-plans/phase3.md). Fichier
    // partage (CLAUDE.md) : ajout signale pour validation, meme convention que les routes
    // precedentes.
    $rest = $matches[1] ?? '';

    // Repli formulaire GET sans JavaScript pour le constructeur de contraintes de la home
    // (app/View/home.php, rapprochement de prototype/index.html) et l'outil "enthalten" de la
    // page hub /woerter (App\Search\ExploreHub ci-dessous) : 0 requete SQLite, redirection pure
    // vers la forme canonique -- meme principe que /pruefen?mot=.. et /wortsuche?lettres=...
    // Chaque champ texte devient un segment de chemin ; "mit-buchstaben"/"ohne" eclatent leur
    // valeur en un segment par lettre (App\Search\WordListFilters::readLetterMultiset()
    // n'accepte qu'une lettre par segment). Un champ absent ou vide n'ajoute aucun segment.
    // "enthalten", "mit-buchstaben", "ohne", "muster" restent volontairement hors sitemap/index
    // (combinaisons infinies, App\Seo\Family::NEVER_SITEMAP) : ce formulaire est un outil,
    // jamais une liste de pages pre-generees.
    //
    // D-DE-015 : les segments de chemin construits ci-dessous ne sont PLUS ecrits a la main.
    // Ils viennent des constantes App\Search\WordListFilters::KEYWORD_*, seule source du
    // vocabulaire d'URL. C'est exactement ce bloc qui avait laisse passer un bug reel lors de
    // D-DE-009 (segments "commencant/..."/"terminant/..." encore francais alors que KEYWORDS
    // avait deja bascule : le repli sans JavaScript renvoyait alors systematiquement
    // /woerter?erreur=1). Les NOMS DE CHAMP GET ($field('enthalten')...) sont desormais
    // localises (D-DE-020, lot signale par D-DE-015 -- jamais visibles dans l'URL finale,
    // toujours une 302 vers la forme canonique).
    if ($rest === '' && $_GET !== []) {
        $field = static function (string $name) use ($config): string {
            $raw = $_GET[$name] ?? '';
            $raw = is_string($raw) ? trim($raw) : '';

            return strlen($raw) > MAX_RAW_SEGMENT_LENGTH ? '' : $raw;
        };

        $segments = [];

        $length = $field('laenge');
        if ($length !== '' && ctype_digit($length)) {
            $segments[] = $length . '-buchstaben';
        }

        $beginnendMit = $field('beginnend-mit');
        if ($beginnendMit !== '') {
            $segments[] = WordListFilters::KEYWORD_PREFIX . '/' . $beginnendMit;
        }

        $contenant = $field('enthalten');
        if ($contenant !== '') {
            $segments[] = WordListFilters::KEYWORD_CONTAINS . '/' . $contenant;
        }

        $endendMit = $field('endend-mit');
        if ($endendMit !== '') {
            $segments[] = WordListFilters::KEYWORD_SUFFIX . '/' . $endendMit;
        }

        $avec = $field('mit-buchstaben');
        if ($avec !== '') {
            $segments[] = WordListFilters::KEYWORD_WITH . '/' . implode('/', mb_str_split($avec));
        }

        $sans = $field('ohne');
        if ($sans !== '') {
            $segments[] = WordListFilters::KEYWORD_WITHOUT . '/' . implode('/', mb_str_split($sans));
        }

        $motif = $field('muster');
        if ($motif !== '') {
            $segments[] = WordListFilters::KEYWORD_PATTERN . '/' . $motif;
        }

        if ($segments !== []) {
            $formFilters = WordListFilters::fromPath(implode('/', $segments));

            // F3 (audit final, design-consistency-reviewer) : une combinaison de contraintes
            // invalide (ex. longueur hors plage) revenait silencieusement sur /woerter, sans
            // que l'utilisateur sache que sa saisie avait ete rejetee. "erreur" reste un
            // indicateur de presentation pur, consomme uniquement par app/View/explore-hub.php.
            $redirect($formFilters !== null ? '/woerter/' . $formFilters->canonicalPath() : '/woerter?erreur=1', 302);

            return;
        }
    }

    if (strlen($rest) > MAX_RAW_WORDLIST_PATH_LENGTH) {
        $render('not-found', ['requestPath' => $path], 404);

        return;
    }

    // Page hub /woerter (audit SEO final, seo-technical-auditor, C4 : les pages /woerter/...
    // sans aucun lien entrant depuis le site) : trois grilles completes vers les familles deja
    // indexees (longueur, beginnend-mit, endend-mit -- 66 liens au total, D-017), construites
    // depuis App\Search\ExploreHub, jamais un GROUP BY au runtime (voir sa propre entete pour
    // la mesure qui l'impose). Remplace le 404 que WordListSolver::solve('') aurait renvoye.
    $connection = new Connection($config->dictionaryPath);

    if ($rest === '') {
        $hub = (new ExploreHubBuilder($connection))->build();

        $render('explore-hub', ['hub' => $hub, 'error' => isset($_GET['erreur'])], 200, '/woerter');

        return;
    }

    $solver = new WordListSolver($connection);
    $page = $solver->solve($rest);

    if ($page === null) {
        $render('not-found', ['requestPath' => $path], 404);

        return;
    }

    $canonical = $page->canonicalPath === '' ? '/woerter' : '/woerter/' . $page->canonicalPath;
    $canonical .= $page->page > 1 ? '/page/' . $page->page : '';

    if ($path !== $canonical) {
        $redirect($canonical, 301);

        return;
    }

    // Maillage interne precalcule (D-022/D-023bis/D-024/D-024bis/D-027/D-029/D-030/D-031/
    // D-033/D-034) : chaque *LinksBuilder lit list_counts (1 requete triviale), jamais un
    // calcul sur `terms`. $filters est garanti non-null : $page->canonicalPath vient de
    // WordListFilters::canonicalPath(), sa reanalyse par fromPath() reussit toujours.
    $filters = WordListFilters::fromPath($page->canonicalPath);

    $hasNoOtherConstraint = static function (WordListFilters $f, array $ignore = []): bool {
        $active = [
            'length' => $f->length !== null,
            'prefix' => $f->prefix !== null,
            'suffix' => $f->suffix !== null,
            'contains' => $f->contains !== null,
            'withLetters' => $f->withLetters !== [],
            'withoutLetters' => $f->withoutLetters !== [],
            'pattern' => $f->pattern !== null,
            'position' => $f->position !== null || $f->positionLetter !== null,
            'status' => $f->status !== null,
            'sort' => $f->sort !== null,
        ];

        foreach ($ignore as $key) {
            unset($active[$key]);
        }

        return !in_array(true, $active, true);
    };

    $isBareSingleLetterPrefix = $filters->prefix !== null && strlen($filters->prefix) === 1
        && $hasNoOtherConstraint($filters, ['prefix']);
    $isBareSingleLetterSuffix = $filters->suffix !== null && strlen($filters->suffix) === 1
        && $hasNoOtherConstraint($filters, ['suffix']);
    $isBarePrefixOnly = $filters->prefix !== null && $hasNoOtherConstraint($filters, ['prefix']);
    $isBareSuffixOnly = $filters->suffix !== null && $hasNoOtherConstraint($filters, ['suffix']);
    $isBarePrefixSuffixPair = $filters->prefix !== null && strlen($filters->prefix) === 1
        && $filters->suffix !== null && strlen($filters->suffix) === 1
        && $hasNoOtherConstraint($filters, ['prefix', 'suffix']);
    $isLengthPlusSinglePrefixOnly = $filters->length !== null && $filters->prefix !== null
        && strlen($filters->prefix) === 1 && $hasNoOtherConstraint($filters, ['length', 'prefix']);
    $isLengthPlusSingleSuffixOnly = $filters->length !== null && $filters->suffix !== null
        && strlen($filters->suffix) === 1 && $hasNoOtherConstraint($filters, ['length', 'suffix']);

    $singleAvecLetter = null;
    if (count($filters->withLetters) === 1) {
        $onlyLetter = array_key_first($filters->withLetters);
        if ($filters->withLetters[$onlyLetter] === 1) {
            $singleAvecLetter = $onlyLetter;
        }
    }

    $twoAvecLetters = null;
    if (count($filters->withLetters) === 2) {
        $letters = array_keys($filters->withLetters);
        if ($filters->withLetters[$letters[0]] === 1 && $filters->withLetters[$letters[1]] === 1) {
            $twoAvecLetters = $letters;
        }
    }

    $isLengthPlusSingleAvecOnly = $filters->length !== null && $singleAvecLetter !== null
        && $hasNoOtherConstraint($filters, ['length', 'withLetters']);
    $isLengthPlusTwoAvecOnly = $filters->length !== null && $twoAvecLetters !== null
        && $hasNoOtherConstraint($filters, ['length', 'withLetters']);
    $isAvecSansOnlyNoLength = $filters->length === null && $singleAvecLetter !== null
        && count($filters->withoutLetters) === 1
        && $hasNoOtherConstraint($filters, ['withLetters', 'withoutLetters']);

    $lengthLinks = $filters->length !== null
        ? (new LengthLinksBuilder($connection))->build($filters->length)
        : null;

    $letterCombinedLinks = match (true) {
        $isBareSingleLetterPrefix => (new LetterCombinedLinksBuilder($connection))->buildForStart($filters->prefix),
        $isBareSingleLetterSuffix => (new LetterCombinedLinksBuilder($connection))->buildForEnd($filters->suffix),
        default => null,
    };

    $prefixAvecLinks = $isBareSingleLetterPrefix
        ? (new PrefixAvecLinksBuilder($connection))->build($filters->prefix)
        : null;

    $prefixExtensionLinks = $isBarePrefixOnly
        ? (new PrefixExtensionLinksBuilder($connection))->build($filters->prefix)
        : null;

    $suffixExtensionLinks = $isBareSuffixOnly
        ? (new SuffixExtensionLinksBuilder($connection))->build($filters->suffix)
        : null;

    $lengthCombinedLinks = match (true) {
        $isLengthPlusSinglePrefixOnly => (new LengthCombinedLinksBuilder($connection))->buildForStart($filters->length, $filters->prefix),
        $isLengthPlusSingleSuffixOnly => (new LengthCombinedLinksBuilder($connection))->buildForEnd($filters->length, $filters->suffix),
        default => null,
    };

    $startEndWithLinks = $isBarePrefixSuffixPair
        ? (new StartEndWithLinksBuilder($connection))->build($filters->prefix, $filters->suffix)
        : null;

    $positionLinks = $isLengthPlusSingleAvecOnly
        ? (new PositionLinksBuilder($connection))->build($filters->length, $singleAvecLetter)
        : null;

    $avecTwoLettersLinks = $isLengthPlusSingleAvecOnly
        ? (new AvecTwoLettersLinksBuilder($connection))->build($filters->length, $singleAvecLetter)
        : null;

    $avecThreeLettersLinks = $isLengthPlusTwoAvecOnly
        ? (new AvecThreeLettersLinksBuilder($connection))->build($filters->length, $twoAvecLetters[0], $twoAvecLetters[1])
        : null;

    $avecSansLengthLinks = $isAvecSansOnlyNoLength
        ? (new AvecSansLengthLinksBuilder($connection))->build($singleAvecLetter, $filters->withoutLetters[0])
        : null;

    $render('word-list', [
        'page' => $page,
        'lengthLinks' => $lengthLinks,
        'letterCombinedLinks' => $letterCombinedLinks,
        'prefixAvecLinks' => $prefixAvecLinks,
        'prefixExtensionLinks' => $prefixExtensionLinks,
        'suffixExtensionLinks' => $suffixExtensionLinks,
        'lengthCombinedLinks' => $lengthCombinedLinks,
        'startEndWithLinks' => $startEndWithLinks,
        'positionLinks' => $positionLinks,
        'avecTwoLettersLinks' => $avecTwoLettersLinks,
        'avecThreeLettersLinks' => $avecThreeLettersLinks,
        'avecSansLengthLinks' => $avecSansLengthLinks,
    ], 200, $canonical);

    return;
}

if ($path === '/pruefen' || preg_match('#^/pruefen/([^/]*)$#u', $path, $matches) === 1) {
    // Fichier partage (CLAUDE.md) : "q" ajoute par l'agent frontend, meme raison et
    // meme convention que le repli "q" de /wortsuche juste au-dessus -- "wort" reste lu
    // en premier et continue de fonctionner seul (repli existant, non supprime). Renomme
    // depuis "mot" (D-DE-020, meme lot que "lettres"->"buchstaben" ci-dessus).
    $raw = $matches[1] ?? ($_GET['wort'] ?? ($_GET['q'] ?? ''));
    $raw = is_string($raw) ? trim($raw) : '';

    if ($raw === '' || strlen($raw) > MAX_RAW_SEGMENT_LENGTH) {
        // F3 : voir le commentaire equivalent sur /wortsuche ci-dessus, meme raison.
        $redirect('/?erreur=1', 302);

        return;
    }

    $normalized = Normalizer::normalize($raw);

    if (!Normalizer::isValid($normalized)) {
        $redirect('/?erreur=1', 302);

        return;
    }

    // Correctif signale (audit independant, docs/DECISIONS.md) : mb_strtolower (pas
    // strtolower ASCII) -- un mot normalise contenant Ä/Ö/Ü (2 octets UTF-8 chacune) restait
    // sinon en MAJUSCULE dans le slug d'URL genere ici, provoquant un aller-retour de
    // redirection supplementaire (302 -> 301 -> 200 au lieu de 302 -> 200) sur 115 536 termes
    // (19,6% de la base) avant que TermLookup::find() ne redirige lui-meme vers la forme
    // canonique en minuscules.
    $redirect('/wort/' . mb_strtolower($normalized, 'UTF-8'), 302);

    return;
}

// Pages legales (D-025ter) : /mentions-legales, /confidentialite, /contact -- volontairement
// non indexees par defaut (D-026, aucune ligne au registre SEO pour elles), $render() avec
// leur propre chemin en canonicalPath suffit (App\Seo\Registry::resolve() renvoie
// noindex,follow par defaut en l'absence de ligne, exactement l'etat voulu ici).
if ($path === '/contact') {
    if ($method === 'POST') {
        // Piege a bots (D-025ter) : champ cache hors du flux visuel/tabulation cote vue --
        // un bot qui le remplit recoit une fausse confirmation, aucun mail envoye.
        $honeypot = is_string($_POST['site_web'] ?? null) ? trim($_POST['site_web']) : '';

        if ($honeypot !== '') {
            $redirect('/contact?envoye=1', 302);

            return;
        }

        $rawEmail = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $rawEmail = str_replace(["\r", "\n"], '', $rawEmail);
        $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);

        $name = is_string($_POST['nom'] ?? null) ? mb_substr(trim($_POST['nom']), 0, 100) : '';
        $message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';

        // Adresse jamais versee au depot (demande anti-spam explicite, D-025ter) -- lue
        // exclusivement via l'environnement, meme convention que SCRABBLE_SITE ci-dessus ; a
        // definir cote hebergement (cPanel / o2switch : "Environment Variables"), jamais dans
        // un fichier .php ni .env commite.
        $contactEmail = getenv('SCRABBLE_CONTACT_EMAIL');

        if ($email === false || $message === '' || mb_strlen($message) > 5000 || $contactEmail === false || $contactEmail === '') {
            $redirect('/contact?erreur=1', 302);

            return;
        }

        $subject = 'Nouveau message via WORD CHECKR';
        $body = ($name !== '' ? "Nom : {$name}\n" : '') . "Email : {$email}\n\n{$message}\n";
        $sent = @mail($contactEmail, $subject, $body, 'Reply-To: ' . $email);

        $redirect($sent ? '/contact?envoye=1' : '/contact?erreur=1', 302);

        return;
    }

    $render('contact', [
        'error' => isset($_GET['erreur']),
        'success' => isset($_GET['envoye']),
    ], 200, '/contact');

    return;
}

if ($path === '/mentions-legales') {
    $render('mentions-legales', [], 200, '/mentions-legales');

    return;
}

if ($path === '/confidentialite') {
    $render('confidentialite', [], 200, '/confidentialite');

    return;
}

$render('not-found', ['requestPath' => $path], 404);
