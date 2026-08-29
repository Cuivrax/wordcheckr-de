# 05 — URL, SEO Et Indexation

> **Note de portée (site allemand).** Comme le reste de `docs/01` à `docs/08` (CLAUDE.md), ce
> fichier décrit le **site français d'origine** dont ce dépôt est une copie indépendante :
> l'architecture (registre unique, ordre canonique des contraintes, règles de sitemap, règle
> "pages à un résultat", pagination) reste valable telle quelle, mais **toute route concrète
> listée ci-dessous (`/mot/qi`, `/mots/commencant/ch`...), toute famille de sitemap
> (`starts-*`, `avec-triple-*`, `combined-with-*`...) et la section « Fiches Françaises »
> décrivent le site français, pas ce dépôt**. Signalé ici (trouvé absent, 2026-08-29,
> D-DE-013) plutôt que laissé sans préambule comme le reste du fichier, pour éviter qu'un futur
> agent lise une route `/mots/commencant/ch` et la croie réellement servie ici (elle ne l'est
> pas — schéma réel : `/woerter/beginnend-mit/{lettre}`, D-DE-009). État réel de CE dépôt :
> voir la section « Section Allemande » en fin de fichier.
>
> Pas de section « Fiches Françaises » équivalente ici : `is_french` n'existe pas côté allemand
> (CLAUDE.md, "Modèle À Statuts" — deux statuts peuplés seulement, aucune source de
> dictionnaire général allemand indépendante retenue, `data/raw/PROVENANCE.md`) — rien à
> indexer "en masse" sous ce nom tant que cette donnée n'existe pas.

## Registre Unique

Le registre SEO est l’unique source de vérité pour :

```text
index ou noindex
canonical
sitemaps
maillage interne
rollout
métadonnées
```

Une route absente du registre reste :

```text
noindex, follow
```

## Routes Principales

```text
/
/mot/qi
/mot/poser
/jouer/aeinrst
/mots/7-lettres
/mots/commencant/ch
/mots/7-lettres/commencant/ch
/mots/terminant/tion
/mots/contenant/che
/mots/avec/a/a/r
/mots/5-lettres/motif/c--e-
```

## Ordre Canonique

```text
longueur
commençant
contenant
terminant
position
avec
sans
motif
```

Toute autre permutation redirige en 301.

## Fiches Françaises

Après filtrage, toutes les formes `is_french = 1` ont vocation à être
indexables.

L’ouverture reste progressive :

```text
lot initial
lots supplémentaires
contrôle o2switch
contrôle Search Console
```

Une forme française non ODS affiche une réponse négative utile et peut être
indexée.

Un terme absent de la base reste noindex.

## Pages À Un Résultat

Une page avec un résultat n’est pas automatiquement faible.

Décision basée sur :

```text
famille autorisée
intention claire
canonical correct
maillage réel
réponse utile
```

Jamais sur le seul compteur.

## Sitemaps

```text
sitemap-index.xml
words-*.xml
invalid-french-*.xml
starts-*.xml
ends-*.xml
contains-*.xml
letters-*.xml
combined-*.xml
position-*.xml
avec-single-*.xml
avec-pair-*.xml
avec-triple-*.xml
combined-with-*.xml
commencant-avec-*.xml
```

`combined-*.xml` (ajouté le 2026-08-09, D-024 correctif / D-025) : commençant + terminant,
`App\Seo\Family::WORD_LIST_COMBINED`. Espace borné (26×26 sans longueur, 14×26×26 au plus avec
longueur), contrairement à `contains-*` qui reste un espace non borné et n'a d'ailleurs jamais
de ligne en pratique. Le périmètre réellement ouvert à l'indexation à un instant donné (souvent
un sous-ensemble de la famille, conditionné à un maillage interne réel préalable — jamais par
défaut) est une décision de lot, pas une règle de cette page.

`position-*.xml` (ajouté le 2026-08-10, analyse d'ouverture dédiée) : une seule lettre connue à
une seule position, `App\Seo\Family::WORD_LIST_POSITION` (D-023). Espace borné par construction
(2 366 combinaisons réelles au total : 26 lettres × positions 2 à longueur-1 × 14 longueurs) —
contrairement à `WORD_LIST_COMBINED` ci-dessus, cette borne couvre la famille entière, pas
seulement un lot particulier, car le mot-clé "position" n'accepte structurellement qu'un seul
couple position/lettre. Comme pour `combined-*`, le périmètre réellement ouvert à un instant
donné reste une décision de lot conditionnée à un maillage interne réel préalable, jamais par
défaut ; les pages à 0 résultat ne sont jamais indexables (docs/05, section "Pages À Un
Résultat" ne s'applique qu'à partir de 1 résultat).

`avec-single-*.xml` (ajouté le 2026-08-17, ouverture progressive en entonnoir de la famille
"avec") : `App\Seo\Family::WORD_LIST_AVEC_SINGLE_LETTER` — PALIER 1 de cette ouverture, longueur
explicite ET exactement une lettre "avec" (occurrence unique, `minCount=1`), sans aucune autre
contrainte (pas de commençant/contenant/terminant/position/sans/motif, pas de statut/tri).
Espace borné par construction sur ce périmètre précis : 14 longueurs × 26 lettres = 364
combinaisons au plus (`list_counts`, `list_type = 'length_with'`, D-022). Distincte en
permanence de `App\Seo\Family::WORD_LIST_AVEC` (multiensemble général, plusieurs lettres et/ou
répétitions, reste et restera dans `NEVER_SITEMAP`) et de tout futur palier (longueur + 2
lettres, longueur + 3 lettres...), qui devra recevoir sa propre constante `Family`, sa propre
mesure complète et sa propre décision de lot — jamais réutiliser cette constante-ci pour un
périmètre plus large que "longueur + une seule lettre". Balayage complet des 364 combinaisons
réelles via le vrai solveur : 364/364 à ≥ 1 résultat, 0/364 au-dessus du budget TTFB p95 < 250 ms
(p50 = 36,6 ms, p95 = 90,2 ms, max = 168,0 ms), maillage interne déjà 100 % couvert depuis
`/mots/{N}-lettres` (déjà indexée, `Family::WORD_LIST_LENGTH`, D-017) via
`App\Search\LengthLinksBuilder::build()->byWith` — voir
`reports/query-plans/avec-length-1-letter-full-sweep.md` et `app/Seo/Family.php`.

`avec-pair-*.xml` (ajouté le 2026-08-17, PALIER 2 de l'ouverture en entonnoir de la famille
"avec") : `App\Seo\Family::WORD_LIST_AVEC_TWO_LETTERS` — longueur explicite ET EXACTEMENT deux
lettres "avec" DISTINCTES (occurrence unique chacune, `minCount=1` sur chacune), sans aucune
autre contrainte. Espace borné par construction sur ce périmètre précis : 14 longueurs × C(26,2)
= 4 550 combinaisons au plus (`list_counts`, `list_type = 'length_with_pair'`). Distincte en
permanence de `App\Seo\Family::WORD_LIST_AVEC_SINGLE_LETTER` (palier 1, ci-dessus) et de
`App\Seo\Family::WORD_LIST_AVEC` (multiensemble général, reste et restera dans `NEVER_SITEMAP`)
et de tout futur palier (3 lettres...), qui devra recevoir sa propre constante `Family`, sa
propre mesure complète et sa propre décision de lot. Balayage complet des 4 550 combinaisons
réelles via le vrai solveur (agent data-engine, 3 exécutions indépendantes) : 4 276/4 550 à
≥ 1 résultat (274 à 0 résultat, exclues ; 132 à exactement 1 résultat, conservées), maillage
interne déjà construit ET vérifié exhaustivement dans les deux sens (`App\Search\
AvecTwoLettersLinksBuilder`, depuis les 364 pages palier 1, déjà indexées) avant application du
lot — couverture 4 276/4 276 (100 %). Un signal de bruit de mesure transitoire a été trouvé,
investigué et documenté sur ce lot (jamais un défaut de plan de requête, `EXPLAIN QUERY PLAN`
stable sur toutes les mesures) : re-vérifié indépendamment par l'agent seo-registry avant
application (deux balayages complets supplémentaires du sous-ensemble longueur 12+13, jusqu'à
109 643,982 ms observé au pire, plus sévère que les runs de data-engine). La reproduction en
vérification isolée répétée s'est révélée dépendante du MOMENT du test, pas de la requête testée :
une 1re vérification isolée juste après un balayage a reproduit des dépassements sur 10/13 cas
déjà cités par data-engine, une 2e vérification des mêmes cas après un second balayage n'en a
reproduit aucun — signature d'une contention système transitoire consécutive à une activité
disque/CPU intense, pas d'un défaut de plan de requête. Voir
`reports/query-plans/avec-length-2-letters-full-sweep.md` et `app/Seo/Family.php` (docblock
`NEVER_SITEMAP`) pour le détail complet, et la recommandation de re-vérification en conditions
réelles o2switch avant la Phase 7, hors de toute fenêtre de build/rollout concurrent.

`avec-triple-*.xml` (ajouté le 2026-08-18, PALIER 3 de l'ouverture en entonnoir de la famille
"avec") : `App\Seo\Family::WORD_LIST_AVEC_THREE_LETTERS` — longueur explicite ET EXACTEMENT trois
lettres "avec" DISTINCTES (occurrence unique chacune, `minCount=1` sur chacune), sans aucune autre
contrainte. Espace borné par construction sur ce périmètre précis : 14 longueurs × C(26,3) =
36 400 combinaisons au plus (`list_counts`, `list_type = 'length_with_triple'`). Distincte en
permanence de `App\Seo\Family::WORD_LIST_AVEC_SINGLE_LETTER` (palier 1) et
`App\Seo\Family::WORD_LIST_AVEC_TWO_LETTERS` (palier 2, ci-dessus), et de
`App\Seo\Family::WORD_LIST_AVEC` (multiensemble général, reste et restera dans `NEVER_SITEMAP`) et
de tout futur palier (4 lettres...), qui devra recevoir sa propre constante `Family`, sa propre
mesure complète et sa propre décision de lot. Balayage complet des 36 400 combinaisons réelles via
le vrai solveur (agent data-engine, un seul passage complet, demande produit explicite — le bruit
de mesure du palier 2 avait été tranché comme une contention entre agents concurrents, condition
absente ici) : 28 827/36 400 à ≥ 1 résultat (7 573 à 0 résultat, exclues ; 1 682 à exactement
1 résultat, conservées), maillage interne construit ET vérifié exhaustivement DANS LES TROIS SENS
(`App\Search\AvecThreeLettersLinksBuilder`, depuis les 4 276 pages palier 2, déjà indexées) avant
application du lot — couverture 28 827/28 827 (100 %), chaîne complète à trois sauts vérifiée à
chaque maillon (`/mots/{N}-lettres` → `avec/{X}` → `avec/{X}/{Y}` → `avec/{X}/{Y}/{Z}`). Un pic de
latence isolé (683/36 400, 1,88 %, concentré à 18/20 sur la seule longueur 8) a été investigué en
détail et jugé non structurel (0/10 cas isolés reproduisent 15× chacun, 0/2 600 sur le re-balayage
complet de la longueur 8 entière, `EXPLAIN QUERY PLAN` identique et stable partout — cause retenue :
contention transitoire coïncidant avec un redémarrage de l'environnement de développement survenu
pendant le balayage, sans rapport avec la production). Surface de pagination chiffrée (I-2, 2e
audit sur D-030) : 758 497 pages `/page/N` pour ce palier seul, 1 049 502 cumulées avec les
paliers 1 et 2 — jamais indexables, suivi (`rel`) de la chaîne de pagination plafonné à 3 pages
pour toute liste ancrée (`app/View/word-list.php`). Voir
`reports/query-plans/avec-length-3-letters-full-sweep.md` et `app/Seo/Family.php` pour le détail
complet.

`combined-*.xml` reçoit un second fragment (`combined-0002.xml`, ajouté le 2026-08-18) : variante
AVEC longueur de `App\Seo\Family::WORD_LIST_COMBINED` (`/mots/{N}-lettres/commencant/{X}/terminant/{Y}`,
D-027) — même famille que `combined-0001.xml` (sans longueur), aucune nouvelle classification.
5 141 URL (5 193 combinaisons réelles moins 52 paires à contenu strictement dupliqué avec la
variante sans longueur, D-025, déjà gagnante canonique permanente). Maillage interne depuis
`/mots/{N}-lettres` (déjà indexée, D-017) via `App\Search\LengthLinksBuilder::build()->byStartEnd` —
voir `reports/query-plans/combined-length-maillage.md`.

`combined-with-*.xml` (ajouté le 2026-08-18, axe 2 de l'ouverture "commençant+terminant+avec") :
`App\Seo\Family::WORD_LIST_COMBINED_WITH_LETTER` — NOUVELLE classification, distincte de
`WORD_LIST_COMBINED` (préfixe + suffixe seuls). Préfixe ET suffixe chacun d'une seule lettre, SANS
longueur, PLUS une lettre "avec" d'occurrence unique (`/mots/commencant/{X}/terminant/{Y}/avec/{Z}`,
D-033). Espace borné par construction sur ce périmètre précis : 611 paires commençant+terminant
réelles (déjà indexées, `WORD_LIST_COMBINED`) × 26 lettres = 15 886 combinaisons candidates au plus.
Balayage complet des 15 886 combinaisons réelles via le vrai solveur : 0/15 886 au-dessus du budget
TTFB p95 < 250 ms, toujours ancré sur `idx_terms_startletter_endletter_normalized` (D-025bis).
1 198 des 11 348 combinaisons à ≥ 1 résultat sont dégénérées (la lettre "avec" égale la lettre de
début ou de fin — collapsées vers la page parente elle-même par `WordListFilters::fromPath()`,
D-032) — exclues. CORRECTIF (2026-08-19, audit seo-technical-auditor consolidé sur D-035/D-036,
bloquant C-1) : 227 lignes supplémentaires sont des doublons de CONTENU avec la page parente SANS
lettre "avec" (`list_type = 'start_end'`) — la lettre "avec" y est bien différente du début/de la
fin, mais son compte égale EXACTEMENT celui de la page parente (tous les mots de la page parente
contiennent déjà cette lettre) — même contrôle que celui déjà appliqué à `combined-0002.xml`
ci-dessus (D-027), qui manquait ici avant ce correctif. Preuve concrète : F:Q (FAQ, 1 seul mot) +
`avec/a`, et X:O (XIPHO, 1 seul mot) + `avec/{h,i,p}`, listaient le même contenu que leur page
parente déjà indexée. 9 923 pages réellement indexables (11 348 − 1 198 − 227), dont 1 385 à
exactement 1 résultat (conservées, même consigne produit que tous les axes précédents). Maillage
interne construit et vérifié exhaustivement dans les deux sens (`App\Search\
StartEndWithLinksBuilder`, depuis les 611 pages `/mots/commencant/{X}/terminant/{Y}`, déjà
indexées) — couverture inchangée par ce correctif (les 227 pages retirées n'étaient de toute façon
jamais indexables, R3). Voir `reports/query-plans/commencant-terminant-avec-full-sweep.md` et
`reports/query-plans/commencant-terminant-avec-maillage.md` pour le détail complet.
CORRECTIF I-A (2026-08-19, 2e audit seo-technical-auditor sur D-037, non bloquant) : le contrôle
C-1 ci-dessus ne compare une ligne "avec" qu'à sa propre page parente (vertical, parent/enfant) —
jamais aux autres lettres "avec" du même parent entre elles (horizontal, entre lignes sœurs). Pour
un panier parent petit, plusieurs lettres "avec" distinctes peuvent isoler exactement le même mot
ou ensemble de mots entre elles — exemple cité par l'audit, confirmé : paire X:M, XALAM (1 mot)
derrière `avec/a` ET `avec/l` ; XENODOCHIUM (1 mot) derrière 8 lettres distinctes. Détecté par
`findSiblingContentDuplicates()` (`scripts/propose_seo_batch.php`, panier complet par paire, 564
paires vérifiées, 9 919 lettres) — la lettre "avec" alphabétiquement la plus petite de chaque
groupe reste seule candidate. 283 groupes de doublons sœurs trouvés, 428 lignes supplémentaires
exclues, vérifié par 3 méthodes indépendantes (0 divergence). 9 495 pages réellement indexables
(9 923 − 428), dont 1 060 à exactement 1 résultat (1 385 avant I-A, moins 325 doublons sœurs à 1
résultat). Maillage interne inchangé (les 428 pages retirées n'étaient de toute façon jamais
indexables, R3).
CORRECTIF C-2 (2026-08-19, 3e audit seo-technical-auditor consolidé de la série, bloquant) : ni C-1 ni
I-A ne comparaient jamais une tranche de CETTE famille (une lettre "avec", axe 2) à une tranche de la
famille SŒUR `App\Seo\Family::WORD_LIST_COMBINED` (une longueur, axe 1, `combined-0002.xml` ci-dessus,
D-027/D-035) du MÊME panier commençant+terminant. Preuve concrète (exemple cité par l'audit, confirmé) :
paire X:M (2 mots au total) — `/mots/5-lettres/commencant/x/terminant/m` (axe 1) ET
`/mots/commencant/x/terminant/m/avec/a` (axe 2, gagnant I-A du groupe {a,l}) listent EXACTEMENT le même
contenu (XALAM) ; `/mots/11-lettres/commencant/x/terminant/m` ET `.../avec/c` (gagnant I-A de l'autre
groupe) listent EXACTEMENT XENODOCHIUM — les DEUX gagnants I-A de cette paire se révèlent des doublons
croisés avec l'axe 1. Règle de priorité (tranchée côté produit, cohérente D-025 — la forme la plus
simple/générale gagne) : la tranche longueur (axe 1) reste seule candidate, la tranche "avec" (axe 2,
CETTE famille) est exclue. Détection EXHAUSTIVE sur les 611 paires réelles (pas un échantillon — l'audit
n'avait lui-même sondé que 9 paires à 5 lettres), vérifiée par DEUX méthodes indépendantes (0
divergence) : (1) panier complet par paire, filtré en PHP pour les deux axes ; (2) requête SQL directe
par tranche (GROUP_CONCAT + sha1). 333 collisions trouvées sur 191 paires distinctes — voir
`findLengthAvecContentCollisions()` dans `scripts/propose_seo_batch.php` (cas `'combined_with_letter'`).
9 162 pages réellement indexables (9 495 − 333), dont 754 à exactement 1 résultat (1 060 avant C-2,
moins 306 collisions croisées à 1 résultat) — GARDÉES, même consigne produit que tous les paliers "avec"
précédents. Maillage interne (`App\Search\StartEndWithLinksBuilder`, agent data-engine) : détection
calculée en parallèle et indépendamment côté data-engine, recoupement des deux listes à confirmer (voir
le rapport AFTER de ce correctif) — les 333 pages retirées du registre n'étaient de toute façon jamais
indexables (R3).

`commencant-avec-*.xml` (ajouté le 2026-08-18, dernier des quatre axes commençant/terminant/avec
travaillés ce jour) : `App\Seo\Family::WORD_LIST_COMMENCANT_WITH_LETTER` — NOUVELLE classification,
distincte à la fois de `App\Seo\Family::WORD_LIST_COMMENCANT` (préfixe seul) ET de
`App\Seo\Family::WORD_LIST_COMBINED_WITH_LETTER` (préfixe+terminant+avec, forme de route
syntaxiquement différente : trois segments de lettre contre deux ici, aucun terminant). Préfixe
d'une seule lettre, SANS longueur, SANS terminant, PLUS une lettre "avec" d'occurrence unique
(`/mots/commencant/{X}/avec/{Y}`). Espace borné par construction sur ce périmètre précis : 26
préfixes réels (déjà indexés, `WORD_LIST_COMMENCANT`) × 26 lettres = 676 combinaisons brutes au
plus (`list_counts`, `list_type = 'start_with'`). Les 26 combinaisons dégénérées (la lettre "avec"
égale le préfixe — collapsées vers la page parente elle-même par `WordListFilters::fromPath()`,
D-032) sont exclues DIRECTEMENT AU PRÉCALCUL cette fois (choix distinct de `combined-with-*`
ci-dessus, qui filtre au niveau du builder) : 650 combinaisons non dégénérées, dont 4 à 0 résultat
(exclues) et 1 à exactement 1 résultat (conservée, même consigne produit que tous les axes "avec"
précédents) — 646 pages réellement indexables. CORRECTIF (2026-08-19, même audit consolidé que
`combined-with-*` ci-dessus) : le même contrôle de doublon de contenu contre la page parente sans
lettre "avec" (`list_type = 'start'`) a été ajouté ici aussi par discipline — recalculé
indépendamment, 0/650 lignes concernées, le compte reste donc à 646, inchangé. Régime BORNE (le prédicat "avec" force
`WordListSolver::needsUnindexedPredicates()`) : `result_count` plafonné à
`ROW_EXAMINATION_CEILING` = 10 000 (D-019), 150/646 combinaisons réellement au-dessus de ce
plafond — l'ancrage reste toujours sur le préfixe (`sqlite_autoindex_terms_1`), jamais un parcours
complet. Balayage complet des 26 pages sources réelles via le vrai code
(`App\Search\PrefixAvecLinksBuilder`, deux passages indépendants) : 0/26 au-dessus du budget TTFB
p95 < 250 ms (max 1,836 ms puis 0,853 ms). Maillage interne construit et vérifié exhaustivement
dans les deux sens (`App\Search\PrefixAvecLinksBuilder`, depuis les 26 pages
`/mots/commencant/{X}`, déjà indexées) — couverture 646/646 (100 %). Voir
`reports/query-plans/commencant-avec-no-length-full-sweep.md` et
`reports/query-plans/commencant-avec-maillage.md` pour le détail complet.
CORRECTIF I-A (2026-08-19, 2e audit seo-technical-auditor sur D-037, non bloquant, même contrôle
`findSiblingContentDuplicates()` que `combined-with-*` ci-dessus) : recalculé sur les 26 préfixes
(panier complet par préfixe, jusqu'à 219 076 mots pour R) — 0 groupe de doublons sœurs trouvé sur
646 lettres avec vérifiées, confirmé par 3 méthodes indépendantes. Compte inchangé à 646/646 —
paniers par préfixe seul en moyenne bien plus grands qu'un panier commençant+terminant, rendant
une coïncidence exacte entre deux lettres bien plus rare (résultat négatif vérifié, pas supposé).

`starts-*.xml`/`ends-*.xml` reçoivent chacun un second fragment (`starts-0002.xml`,
`ends-0002.xml`, ajoutés le 2026-08-18) : extension multi-lettres (préfixes/suffixes réels de 2 à
4 lettres) de `App\Seo\Family::WORD_LIST_COMMENCANT`/`WORD_LIST_TERMINANT` — mêmes familles que
`starts-0001.xml`/`ends-0001.xml` (mono-lettre, D-017), aucune nouvelle classification, aucun
changement de `App\Seo\Family`. 39 539 combinaisons réelles (21 734 préfixes + 17 805 suffixes),
0/39 539 au-dessus du budget TTFB au premier balayage complet (2/39 539 investigués et non
reproduits en isolation, contention transitoire déjà documentée pour ce projet, D-030/D-031).
1 982 pages (1 022 côté préfixe, 960 côté suffixe) à contenu strictement dupliqué avec leur page
parente immédiate exclues (page la plus longue de chaque paire reste `noindex,follow` en
permanence, R3 — même logique que les 52 paires déjà tranchées pour `WORD_LIST_COMBINED`, D-025) :
37 557 URL réellement indexées (20 712 préfixes + 16 845 suffixes). Maillage interne en entonnoir
construit et vérifié exhaustivement dans les deux sens (`App\Search\PrefixExtensionLinksBuilder`/
`SuffixExtensionLinksBuilder`, depuis les 26+26 pages mono-lettre déjà indexées, D-017) — couverture
39 539/39 539 (100 %). Voir `reports/query-plans/commencant-terminant-multi-lettres-
dimensionnement.md` pour le détail complet.

Limite interne :

```text
40 000 URL par fragment
```

Chaque URL du sitemap doit répondre :

```text
200
index
canonical autonome
contenu non vide
aucune redirection
```

## Pagination

```text
/page/2
/page/3
```

Canonical autonome et vrais liens précédent/suivant.

Les tris et paramètres ne sont pas indexables.

---

# Section Allemande

État réel de CE dépôt (indépendant du site français ci-dessus), mis à jour le 2026-08-29
(D-DE-017, dernier palier appliqué — historique complet D-DE-013 → D-DE-017). Toutes les règles
génériques ci-dessus (registre unique, ordre canonique, règle "pages à un résultat", limite de
40 000 URL/fragment, pagination) s'appliquent telles quelles ; seules les routes concrètes et
les familles réellement peuplées diffèrent.

## Routes Réelles (D-DE-009, mots-clés localisés en second palier par D-DE-015)

```text
/
/wort/qi
/wort/schreiben
/pruefen/{wort}          (redirection pure vers /wort/{wort})
/wortsuche/{buchstaben}
/woerter                 (hub de navigation, App\Search\ExploreHub)
/woerter/7-buchstaben
/woerter/beginnend-mit/ch
/woerter/7-buchstaben/beginnend-mit/ch
/woerter/endend-mit/ung
/woerter/enthalten/sch
/woerter/mit-buchstaben/a/a/r
/woerter/5-buchstaben/muster/c--e-
```

Les 7 mots-clés restants (`contenant`→`enthalten`, `avec`→`mit-buchstaben`, `sans`→`ohne`,
`motif`→`muster`, `statut`→`status`, `tri`→`sortierung`, `position` inchangé — cognate allemand)
ont été localisés par D-DE-015 ; plus aucun segment français dans le schéma d'URL. Les VALEURS
d'énumération (`admis`/`non-admis`, `points`/`points-desc`) restent volontairement françaises
(lot suivant, non fait, signalé D-DE-015). `enthalten`/`mit-buchstaben`/`ohne`/`muster` restent
dans `App\Seo\Family::NEVER_SITEMAP` en permanence (combinaisons non bornées) ; `status`/
`sortierung`/`position` sont des raffinements d'affichage ou exigent toujours une longueur
explicite.

## Ordre Canonique

Identique à l'ordre générique ci-dessus, mots-clés localisés (D-DE-009, D-DE-015) :

```text
longueur (N-buchstaben)
beginnend-mit
enthalten
endend-mit
position
mit-buchstaben
ohne
muster
status
sortierung
```

## Modèle À Statuts (différence structurelle avec le dépôt français)

Aucune section « Fiches Françaises » équivalente : `is_french`/`is_german`-non-admis n'existe
pas dans `storage/dictionary_de.sqlite` (CLAUDE.md, "Modèle À Statuts" — deux statuts peuplés
seulement : `is_admitted` / inconnu). `App\Seo\Family` n'a donc aucune constante équivalente à
`WORD_FRENCH_NOT_ADMITTED`/`WORD_SPANISH_NOT_ADMITTED` des dépôts cousins — à ajouter le jour où
une source réelle existera, jamais avant (voir `app/Seo/Family.php`).

## Familles Réellement Peuplées (D-DE-013, D-DE-016, D-DE-017)

```text
home                   '/' uniquement (PAS '/woerter' -- voir plus bas)
word_admitted          /wort/{mot}, intégralité, 590 856 pages (D-DE-016)
word_list_length       /woerter/{N}-buchstaben, les 14 longueurs (2 à 15) (D-DE-013)
word_list_commencant   /woerter/beginnend-mit/{lettre}, 1 lettre, 29 pages (D-DE-017)
word_list_terminant    /woerter/endend-mit/{lettres}, 2 lettres, 455 pages RÉALISÉES sur 841
                        combinaisons théoriques (D-DE-017 -- PAS 1 lettre, voir "Maillage
                        Interne Vérifié" ci-dessous pour la raison mesurée)
```

`/woerter` (hub) reste **noindex,follow** : `list_counts` est vide sur ce dépôt (même décision
que côté espagnol cousin) — les trois sections de grille ("Nach Länge"/"Beginnend
Mit"/"Endend Mit") rendent `<div class="related-links"></div>` strictement vide, vérifié en
direct (`curl`/`file_get_contents` contre un vrai serveur `php -S`), pas seulement en lisant le
code. Seuls les formulaires "Enthält"/"Prüfen" et le texte d'introduction constituent un contenu
réel — jugé insuffisant pour indexer une page dont les trois quarts du contenu annoncé sont
vides. Bloque aussi, par ricochet, tout maillage `list_counts`-dépendant (longueur+beginnend-mit
combiné, beginnend-mit+endend-mit combiné, `App\Search\LengthLinksBuilder`,
`StartEndWithLinksBuilder` et les autres `*LinksBuilder` hérités du dépôt français — tous
produisent des sections vides tant que cette table reste à 0 ligne, hors périmètre de l'agent
seo-registry, à router vers l'agent data-engine).

## Maillage Interne Vérifié (D-DE-013, affiné D-DE-017)

`App\Search\RelationsFinder::relatedSearches()` émet, sur **chaque** fiche de mot admis
(qu'elle soit elle-même indexée ou non — `noindex,follow` continue de faire suivre ses liens
sortants) :

```text
length      1 lien vers /woerter/{sa-longueur}-buchstaben, toujours present, jamais evince
startsWith  1 lien vers beginnend-mit/{1re lettre}, TOUJOURS present (word_list_commencant,
            D-DE-017) ; +1 lien beginnend-mit/{3 premieres lettres} si longueur > 3 (candidat
            mesure pour un palier 2, PAS applique ce lot -- 3703 pages, volume non discute)
endsWith    1 lien vers endend-mit/{2 dernieres lettres}, TOUJOURS 2 lettres, JAMAIS 1 seule
            (mb_substr($word, -min(2, $length)) avec Normalizer::MIN_LENGTH = 2 constant) --
            consequence mesuree (D-DE-017) : sur les 29 pages endend-mit/{1 lettre}
            possibles, UNE SEULE (endend-mit/s, lien statique app/View/home.php) a un lien
            entrant reel -- les 28 autres seraient orphelines si ouvertes a 1 lettre, d'ou le
            choix mesure d'ouvrir endend-mit a 2 lettres plutot qu'a 1 (asymetrie deliberee
            avec beginnend-mit, PAS une erreur)
with        1 lien vers {longueur}-buchstaben/mit-buchstaben/{jusqu'a 3 lettres distinctes
            triees DU MOT LUI-MEME} -- maillage EPARPILLE (6861 combinaisons (longueur,lettres)
            reellement liees, chacune par SEULEMENT les mots qui partagent EXACTEMENT cet
            ensemble de lettres), pas un entonnoir propre par palier de nombre de lettres
            (contrairement a D-029/D-030/D-031 cote depot francais) -- NE constitue PAS a lui
            seul une justification d'ouverture, signale pour mesure plus fine avant tout palier
            futur
```

`app/View/home.php` ajoute 3 liens statiques supplémentaires (`beginnend-mit/a`, `endend-mit/s`,
`beginnend-mit/a/endend-mit/e`) — ce dernier est, à ce jour, le SEUL lien réel vers la famille
combinée beginnend-mit+endend-mit (690 combinaisons réalisées, 689 orphelines si ouvertes).

Balayage COMPLET (pas un échantillon) mené le 2026-08-29 (D-DE-017) sur les deux familles
ouvertes par ce lot :

```text
beginnend-mit (29/29 lettres)             0/29 au-dessus de 250 ms, p95 3,27 ms, max 5,21 ms
                                           EXPLAIN QUERY PLAN : SEARCH ... COVERING INDEX
                                           sqlite_autoindex_terms_1, jamais SCAN
endend-mit (455/455 suffixes réalisés)    0/455 au-dessus de 250 ms, p95 30,38 ms, max 84,22 ms
                                           EXPLAIN QUERY PLAN : SEARCH ... COVERING INDEX
                                           idx_terms_reversed, jamais SCAN
                                           12/455 pages TRONQUÉES (résultat réel > 10 000,
                                           WordListSolver::ROW_EXAMINATION_CEILING) -- compte
                                           affiché honnête ("Mindestens 10 000... pas garanti
                                           complet au-delà"), même précédent accepté côté
                                           français D-028bis/D-029, pas un défaut nouveau
```

Le sondage borné antérieur (14 mots, ~40 URL cibles, D-DE-013 point 8 ; étendu à 36 cibles lors
de l'ouverture de `word_admitted`, D-DE-016) reste la seule mesure disponible pour `enthalten`,
`mit-buchstaben` à 2/3 lettres, `ohne`, `muster` et `position` — favorable mais **pas** un
balayage exhaustif au sens de D-024/D-025/D-029 à D-031 côté dépôt français ; à mener par
l'agent data-engine avant toute décision d'ouverture de ces familles.

## Sitemaps Réellement Générés

```text
core-0001.xml     1 URL ('/')
words-0001..0015  590 856 URL (/wort/{mot}, 14 fragments à 40 000 + 1 à 30 856)
letters-0001.xml  14 URL (/woerter/{N}-buchstaben)
starts-0001.xml   29 URL (/woerter/beginnend-mit/{lettre})
ends-0001.xml     455 URL (/woerter/endend-mit/{2 lettres})
```

19 fragments, 591 355 URL au total (2026-08-29). Préfixes réservés mais non générés à ce
stade : `contains-*`/`combined-*`/`position-*`/`avec-*`/`avec-single-*` (aucune famille
correspondante mesurée assez finement ni ouverte sur ce dépôt — voir "Maillage Interne Vérifié"
ci-dessus pour ce qui reste à mesurer).
