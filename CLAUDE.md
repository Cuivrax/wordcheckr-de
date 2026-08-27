# Scrabble Light — Site Allemand

Moteur ultra léger pour le Scrabble et les jeux de lettres. Le site répond à deux questions :

```text
Quel mot puis-je jouer avec mes lettres et mes contraintes ?
Ce terme est-il admis au Scrabble ?
```

Ce n'est ni un blog, ni un CMS, ni un dictionnaire éditorial.

**Ce dépôt est une copie indépendante**, issue du site français (`git archive`, sans historique
partagé) et dédiée exclusivement au site allemand (`wordcheckr.de`, prévu). Il ne sert et ne
servira jamais le site français — contrairement au dépôt d'origine, dont la documentation
envisageait un futur site anglais partageant le même code, ce dépôt-ci n'a pas vocation à
redevenir multilingue : toute référence restante à « site français », ODS8/ODS9, ou au
français dans les documents hérités (`docs/01` à `docs/08`) décrit l'historique du dépôt
d'origine, pas ce dépôt.

## Ordre De Lecture Obligatoire

Avant toute modification, dans cet ordre :

```text
docs/01_MASTER_BRIEF.md                      (hérité, décrit le site français d'origine)
docs/02_ARCHITECTURE_DATA_MULTILINGUE.md     (hérité, architecture toujours valable)
docs/03_SOURCES_ET_IMPORT_DATA.md            (hérité, sources françaises -- voir data/raw/PROVENANCE.md pour les sources allemandes réelles)
docs/04_UI_PAGES.md                          (hérité)
docs/05_URL_SEO_INDEXATION.md                (hérité)
docs/06_PHASES_IMPLEMENTATION.md             (hérité)
docs/07_CLAUDE_CODE_WORKFLOW.md              (hérité)
docs/08_PROMPTS_PHASES.md                    (hérité)
docs/DECISIONS.md                            (D-001 à D-043 : historique français hérité --
                                              décisions allemandes en D-DE-XXX, en fin de fichier)
docs/PHASE_STATUS.md                         (idem : état français hérité, état allemand en fin de fichier)
```

`docs/01` à `docs/08` décrivent le site **français d'origine** dont ce dépôt est issu —
l'architecture technique (schéma, index, budget de requêtes, grammaire d'URL) reste valable
telle quelle, mais tout exemple concret (ODS8/ODS9, mots français, comptes de lignes) décrit le
site français, pas celui-ci. Consulter `README.md`, `data/raw/PROVENANCE.md` et la fin de
`docs/DECISIONS.md`/`docs/PHASE_STATUS.md` pour l'état réel du site allemand.

## Contraintes Dures

```text
PHP 8.4 sans framework
SQLite local, ouvert en lecture seule au runtime
HTML rendu côté serveur
CSS natif, JavaScript minimal et progressif
hébergement mutualisé o2switch, plusieurs workers PHP concurrents
```

Interdits :

```text
React, Vue, SPA, framework frontend
police externe, image décorative, animation lourde
base distante, processus applicatif permanent
scan complet de la table (590 850 lignes) au runtime
cache produisant des millions de petits fichiers
texte SEO artificiellement rallongé
dépendance ajoutée sans entrée ## D-DE-XXX dans docs/DECISIONS.md
```

Cibles de performance :

```text
moins de 10 requêtes SQLite indexées par fiche mot
requêtes préparées uniquement, LIMIT strict systématique
résultat principal présent dans le HTML initial, sans JavaScript
TTFB chaud p95 sous 250 ms
```

Toute requête nouvelle ou modifiée fournit son `EXPLAIN QUERY PLAN`, son temps d'exécution,
son nombre de lignes, et un benchmark avant/après.

## Modèle À Statuts — Fermé, Deux Statuts Peuplés Cette Passe

Le modèle reste conceptuellement à **trois** statuts (aucun quatrième sens à inventer), mais
seuls **deux** sont peuplés par les données allemandes actuelles — voir `data/raw/PROVENANCE.md`
pour la raison (aucune source de dictionnaire général allemand ne réunit licence commerciale
claire et indépendance réelle vis-à-vis de la liste Scrabble) :

```text
is_admitted = 1        → admis (liste enz/german-wordlist, CC0-1.0 -- voir data/raw/PROVENANCE.md)
absent de la base       → terme inconnu
```

`TermPage::STATUS_FRENCH_NOT_ADMITTED` (nom hérité, pas renommé — voir
`app/Search/TermPage.php`) reste défini dans le code pour le modèle fermé, mais n'est produit
par **aucune** donnée actuelle : troisième statut structurellement possible, pas actif.

## Séparation Build / Runtime (D-007, héritée)

```text
scripts/*     hors ligne (Python pour l'import des sources externes, PHP pour les
              artefacts dérivés du runtime), jamais accessible depuis public/, jamais
              exécuté au runtime
app/, public/  runtime, PHP 8.4 uniquement, lecture seule sur SQLite
```

Aucune écriture sur la base de production au runtime.

## Agents

Les 8 définitions vivent dans `.claude/agents/` — **source unique**. Ne pas en créer de copie
ailleurs dans le dépôt.

Build — droit d'écriture dans leur périmètre :

| Agent | Périmètre |
|---|---|
| `data-engine` | `app/Database/`, `app/Search/`, `scripts/import_*`, `scripts/build_*`, `tests/Search/`, `tests/Database/` |
| `frontend` | `app/View/`, `public/assets/`, `tests/Frontend/` |
| `seo-registry` | `app/Seo/`, `scripts/build_sitemaps*`, `tests/Seo/`, `public/robots.txt` |
| `microcopy` | `resources/copy/`, `resources/translations/` |

Audit — lecture seule, prononcent **GO / NO GO** :

```text
code-reviewer                 correction, contraintes dures, cohérence des comptes
code-optimizer                uniquement si un problème mesuré existe
design-consistency-reviewer   cohérence visuelle, accessibilité, sans-JS
seo-technical-auditor         registre SEO, canonicals, sitemaps, rollout
```

Matrice d'audit par phase : `docs/06_PHASES_IMPLEMENTATION.md`. Ne pas lancer les quatre audits
après chaque micro-tâche.

## Fichiers Partagés

```text
schema.sql
app/Config.php
public/index.php
docs/DECISIONS.md
docs/PHASE_STATUS.md
```

Sous contrôle de la session principale. Un agent peut proposer un diff, jamais les modifier
silencieusement.

## Boucle De Travail

```text
1 agent build   → rapport BEFORE, implémentation, rapport AFTER + READY FOR AUDIT
1 agent audit   → GO ou NO GO
validation humaine
commit
phase suivante
```

Séquence pour tout changement d'architecture : analyser sans rien modifier → proposer →
**attendre validation explicite** → implémenter → tester → rapport diff + mesures.

## Commits

Un commit par unité validée, nommé par phase :

```text
phase-de-001-normalizer-fix
phase-de-002-schema-import
phase-de-003-search-layer
phase-de-004-prune-out-of-scope-tests
phase-de-005 / 006 / 007-adapt-core-tests
```

## État Des Données

```text
data/raw/enz_german_wordlist/words  685 789 mots bruts (CC0-1.0), dont 590 850 retenus — présent
storage/dictionary_de.sqlite        590 850 termes, 150,6 Mo — construite, integrity ok
```

La base ne retient aucune forme de plus de 15 lettres : injouable sur un plateau (D-010,
héritée). Impact plus lourd qu'en français (12,31 % rejetées contre 2,2 %) — composition
allemande, voir `data/raw/PROVENANCE.md`.

Ä/Ö/Ü sont des lettres allemandes distinctes de A/O/U, jamais repliées l'une sur l'autre (voir
`app/Search/Normalizer.php`). ß (Eszett) est accepté et converti en SS (règle officielle, pas
de tuile ß dédiée).

Empreintes et provenance : `data/raw/PROVENANCE.md`. Reconstruire la base :
`python scripts/import_de.py`.

La base est notre construction propre : formes normalisées, indicateurs et scores, aucune
définition. **Le site ne publie aucun crédit de source** (D-015, héritée) — ni page de licence,
ni mention en pied de page, ni commentaire dans le HTML servi.

## Ce Qui N'est Pas (Encore) Construit Dans Ce Dépôt

```text
pas de dictionnaire général allemand indépendant (modèle à deux statuts, voir plus haut)
pas de nature grammaticale / genre (pas d'équivalent D-018 français)
pas de définitions lexicales (pas d'équivalent D-043 français)
pas de registre SEO (storage/seo_de.sqlite non construit -- App\Seo\Registry gère nativement
  son absence, noindex,follow par défaut, aucun impact fonctionnel)
pas de maillage interne SEO peuplé (list_counts conservée dans schema.sql mais vide --
  App\Search\ExploreHubBuilder et les *LinksBuilder restent fonctionnels, sections vides
  plutôt qu'une erreur)
pas de déploiement (ce dépôt reste un travail local, voir docs/09_DEPLOIEMENT_O2SWITCH.md
  hérité pour la marche à suivre le moment venu)
```

Chacun de ces points est une décision explicite documentée, pas un oubli — voir
`docs/DECISIONS.md` (section D-DE-XXX) pour le détail complet.
