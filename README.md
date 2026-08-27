# Scrabble Light — Site Allemand

Moteur ultra léger pour le Scrabble et les jeux de lettres. PHP 8.4 sans framework,
SQLite en lecture seule, hébergement mutualisé o2switch.

**Ce dépôt est une copie indépendante du site français** (`git archive`, aucun historique
partagé), dédiée exclusivement au site allemand (domaine prévu : `wordcheckr.de`). Il ne sert
et ne servira jamais le site français.

Point d'entrée pour toute session de travail : `CLAUDE.md`, puis `00_START_HERE.md`.

## Décision Multilingue (héritée, contexte du dépôt d'origine)

```text
une base de production par langue et par site
un registre SEO séparé par domaine
un code partagé
```

Ce dépôt N'implémente que la branche allemande de cette décision — pas un site multilingue à
lui seul.

Fichiers de production de CE dépôt :

```text
storage/dictionary_de.sqlite   590 850 termes, 150,6 Mo -- construite
storage/seo_de.sqlite          non construite cette passe (voir CLAUDE.md, "Ce Qui N'est Pas
                                Encore Construit") -- App\Seo\Registry gère nativement son
                                absence, aucun impact fonctionnel
```

## Arborescence

```text
CLAUDE.md            constitution du projet (réécrite pour l'allemand)
00_START_HERE.md     ordre de lecture et état des données
.claude/agents/      les 8 agents, source unique
docs/                cadrage 01 à 07 HÉRITÉ du site français (architecture toujours valable,
                     exemples en français) ; DECISIONS et PHASE_STATUS : historique français
                     hérité + section allemande en fin de fichier (D-DE-XXX)
docs/archive/        documents d'amorçage du pack de lancement (hérité)
data/raw/            sources brutes, hors Git -- voir data/raw/PROVENANCE.md pour les sources
                     réellement utilisées (enz/german-wordlist, CC0-1.0)
data/ods9/           patch ODS9 français, hérité, INUTILISÉ par ce dépôt (aucun équivalent
                     allemand -- pas de double lexique ODS8/ODS9 pour l'allemand)
schema/              proposition de schéma (héritée, française)
scripts/             téléchargement, vérification, import (Python) -- scripts/import_de.py est
                     le script réellement utilisé par ce dépôt
prototype/           références de rendu HTML (héritées)
reports/             rapports générés, hors Git
storage/             bases de production générées, hors Git
```

## Démarrage

```text
1. lire CLAUDE.md
2. les sources allemandes sont déjà téléchargées (data/raw/enz_german_wordlist/, hors Git --
   voir data/raw/PROVENANCE.md pour les reconstituer si absentes)
3. exécuter python scripts/import_de.py pour (re)construire storage/dictionary_de.sqlite
4. exécuter php tests/run.php pour vérifier la suite de tests
```

## Reconstruire La Base Allemande

```bash
python scripts/import_de.py
```

Recrée intégralement `storage/dictionary_de.sqlite` depuis `data/raw/enz_german_wordlist/`
(source unique, CC0-1.0). Déterministe : deux exécutions successives produisent un fichier
strictement identique (vérifié, sha256 identique).

## Important

Aucun dictionnaire général allemand indépendant n'est utilisé cette passe (modèle à deux
statuts : admis / inconnu — voir `CLAUDE.md` et `data/raw/PROVENANCE.md` pour la justification
complète). Aucune nature grammaticale, aucun genre, aucune définition lexicale, aucun registre
SEO ne sont construits dans ce dépôt à ce stade.

Le site ne publie aucun crédit de source pour la liste de mots allemande (D-015, héritée). Les
URL et empreintes des sources restent dans `data/raw/PROVENANCE.md`, à usage interne, pour que
l'import reste reproductible.
