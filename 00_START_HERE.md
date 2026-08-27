# 00 — Démarrer Ici

## But

Construire un moteur ultra léger pour le Scrabble et les jeux de lettres, hébergé sur
o2switch — **déclinaison allemande**, dépôt indépendant issu du site français (`git archive`,
aucun historique partagé). Domaine prévu : `wordcheckr.de`.

## Point D'Entrée

`CLAUDE.md` à la racine est le point d'entrée de toute session. Il contient l'ordre de lecture,
les contraintes dures, la matrice des agents et la boucle de travail — réécrit pour refléter
l'état allemand de ce dépôt (pas une simple traduction du fichier français).

## Avant Toute Modification

Lire, dans cet ordre :

```text
1.  CLAUDE.md                              (réécrit pour l'allemand)
2.  docs/01_MASTER_BRIEF.md                (hérité, décrit le site français d'origine)
3.  docs/02_ARCHITECTURE_DATA_MULTILINGUE.md (hérité, architecture toujours valable)
4.  docs/03_SOURCES_ET_IMPORT_DATA.md      (hérité -- voir data/raw/PROVENANCE.md pour les
                                            sources allemandes réelles)
5.  docs/04_UI_PAGES.md                    (hérité)
6.  docs/05_URL_SEO_INDEXATION.md          (hérité)
7.  docs/06_PHASES_IMPLEMENTATION.md       (hérité)
8.  docs/07_CLAUDE_CODE_WORKFLOW.md        (hérité)
9.  docs/08_PROMPTS_PHASES.md              (hérité, prompts de lancement français)
10. docs/DECISIONS.md                      (D-001 à D-043 hérités ; D-DE-XXX en fin de fichier)
11. docs/PHASE_STATUS.md                   (idem)
12. .claude/agents/*.md                    les 8 agents, build et audit (inchangés)
```

Les documents 2 à 9 décrivent le site **français d'origine** : l'architecture technique reste
valable telle quelle (schéma, index, budget de requêtes, grammaire d'URL), mais tout exemple
concret (ODS8/ODS9, mots français, comptes de lignes) ne s'applique pas à ce dépôt. Pour l'état
réel du site allemand, voir `README.md`, `data/raw/PROVENANCE.md` et la fin de
`docs/DECISIONS.md`/`docs/PHASE_STATUS.md`.

## État Des Données

```text
data/raw/enz_german_wordlist/words   685 789 mots bruts (CC0-1.0), hors Git — présent
storage/dictionary_de.sqlite         590 850 termes, 150,6 Mo — construite, integrity ok
```

Reconstruction : `python scripts/import_de.py`.
Provenance et empreintes : `data/raw/PROVENANCE.md`.

Non construits cette passe (décisions explicites, pas des oublis — voir `CLAUDE.md` et
`docs/DECISIONS.md` D-DE-XXX) : dictionnaire général indépendant, nature grammaticale,
définitions lexicales, registre SEO, déploiement.

## Historique

Ce dépôt a été créé par `git archive` depuis le site français (`Scrabble Light FR`), sans
historique Git partagé, à la demande explicite du porteur de projet pour construire un site
Scrabble allemand indépendant. La base de code applicative (`app/`, `scripts/`, `tests/`) est
partagée avec le site français au moment de la copie ; elle a ensuite divergé pour l'allemand
(voir `docs/DECISIONS.md`, section D-DE-XXX, pour le détail complet des adaptations : Ä/Ö/Ü,
ß, schéma simplifié, source de données unique).

Le pack de lancement d'origine (côté français) avait été promu à la racine du dépôt le
2026-08-03 ; les documents d'amorçage sont conservés dans `docs/archive/` (hérités,
non pertinents pour l'allemand).
