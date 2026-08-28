# Provenance Des Données Brutes — Site Allemand

**Document interne.** Il existe pour une seule raison : rendre l'import reproductible à
l'identique. Rien de son contenu n'est publié sur le site — aucun crédit de source n'y figure
(D-015, hérité du site français, toujours en vigueur ici).

Ce dossier est exclu de Git (voir `.gitignore`). Les fichiers y sont reconstitués grâce aux
empreintes ci-dessous.

## enz_german_wordlist/ — liste de mots allemande (source principale)

```text
source          https://github.com/enz/german-wordlist
fichier récupéré words (brut, un mot par ligne)
URL directe     https://raw.githubusercontent.com/enz/german-wordlist/master/words
commit source   e8618fbd2a996780d60005b7d3f04e4431b864fd (2026-08-16T10:46:34Z, auteur
                Markus Enzenberger, message "Rephrased")
téléchargé le   2026-08-27
licence         CC0-1.0 (Creative Commons Zero — domaine public, usage commercial
                explicitement autorisé, aucune attribution requise). Fichier COPYING
                téléchargé en parallèle et vérifié : contenu du texte légal CC0 1.0
                Universal, sha256 identique au COPYING de hippler/german-wordlist
                (dépôt d'origine dont enz est un fork continué) — licence confirmée
                inchangée depuis l'origine.
```

```text
words       8 643 558 octets   sha256 445c8e09e0efe63e76beadc25607f521c7e09893ac68d585a822c7c6ecbebf7b
COPYING         7 048 octets   sha256 a2010f343487d3f7618affe54f789f5487602331c0a8d03f49e9a7c547cf0499
```

Structure vérifiée directement (`python`, lecture ligne à ligne, UTF-8) :

```text
685 789 lignes, 685 789 formes distinctes (aucun doublon brut, aucune ligne vide)
encodage UTF-8, fins de ligne LF
longueurs de 2 à 25 caractères
0 espace, 0 trait d'union, 0 apostrophe, 0 chiffre dans tout le fichier
11 974 formes contiennent un ß (Eszett)
475 formes (0,07 %) contiennent un diacritique hors A-Z/ÄÖÜäöüß — emprunts étrangers
  conservés tels quels par la source (ex. Abbé, Abrégé, Açaï, Acheuléen, Agrément,
  Ampère, Ångström, Aperçu, Apéro, Åsar) — traités par le pliage générique NFD déjà en
  place pour le français, aucune règle supplémentaire nécessaire
84 433 formes (12,31 %) dépassent 15 caractères une fois comptées en CARACTÈRES (pas en
  octets) — voir note de correction ci-dessous
```

**Correction apportée à la recherche de faisabilité préalable**
(`reports/de-site-feasibility-audit.md` côté dépôt français, section 1.b) : ce document
annonçait 94 130 formes (13,7 %) au-dessus de 15 lettres. Le compte vérifié directement pour
cet import est de 84 433 (12,31 %). L'écart vient très probablement d'un comptage en OCTETS
UTF-8 plutôt qu'en CARACTÈRES lors de la mesure précédente (un mot contenant Ä/Ö/Ü/ß compte 2
octets par lettre étendue mais 1 seul caractère) — le fichier source lui-même est identique
(même sha256, même nombre de lignes) entre les deux mesures. Le plafond D-010 (15 lettres,
hérité du français) est défini en CARACTÈRES (une case de plateau = une lettre), donc 84 433
est le chiffre correct à utiliser pour le filtrage réel de `scripts/import_de.py` — pas une
divergence de données, une divergence de méthode de comptage corrigée ici.

**Aucun filtre de noms propres/toponymes/sigles appliqué à l'import** (contrairement au
pipeline français, qui dispose d'une étiquette `pos = NP` côté Kartmaan et d'un filtre sur la
casse côté hbenbel) : cette source n'offre aucun marqueur exploitable de ce type — l'allemand
met une majuscule à TOUS les noms communs, la casse ne distingue donc pas un nom propre d'un
nom commun ici (contrairement au français). Le README du projet source déclare une politique
éditoriale explicite d'exclusion des noms propres/toponymes/sigles/formes archaïques, avec un
biais assumé vers l'inclusion en cas de doute ("Tanglet fonctionne mieux avec des faux positifs
que des faux négatifs") — cette politique est acceptée telle quelle pour cette première passe,
faute de source de recoupement indépendante et gratuite (voir
`reports/de-site-feasibility-audit.md` §1 et §3 côté dépôt français : aucune source de
dictionnaire général allemand ne réunit licence commerciale claire et indépendance réelle vis-
à-vis de la liste Scrabble elle-même).

## hippler_de/ — liste de mots allemande (source secondaire, fusionnée — D-DE-006)

```text
source          hippler/german-wordlist (github.com/hippler/german-wordlist), instantané
                local figé (~janvier 2023), ancêtre direct dont enz/german-wordlist est un
                fork continué
fichier reçu    C:\Users\reka0\Website Windsurf\01. Data Scrabble\JSON\
                scrabble-german-DE-HIPPLER.json
copié vers      data/raw/hippler_de/scrabble-german-DE-HIPPLER.json
copié le        2026-08-28
licence         CC0-1.0 — même lignée qu'enz, licence déjà confirmée identique (COPYING au
                même sha256, voir la section enz ci-dessus, vérifié lors de la recherche de
                faisabilité initiale)
```

```text
scrabble-german-DE-HIPPLER.json   5 539 150 octets
sha256                            06d39a983dd2d9523928c173954457569bea2dc5695cf2d3333436796bbe5b3b
```

Structure vérifiée directement (`python`, `json.load`) :

```text
{"words": [...]} -- même forme que data/raw/ods8.json côté dépôt français
336 208 entrées, 336 207 distinctes (1 doublon brut exact)
0 espace, 0 trait d'union, 0 apostrophe, 0 chiffre dans tout le fichier
longueurs de 2 à 25 caractères (comptées en caractères Python, pas en octets)
```

**Décision révisée (D-DE-006, remplace la décision "non fusionné" de la première passe) :
FUSIONNÉ.** Le porteur de projet a explicitement demandé la fusion des deux sources avec
provenance par mot plutôt que la simple mise à l'écart du fichier local — voir
`schema.sql` (colonnes `is_enz`/`is_hippler`) et `scripts/import_de.py`.

**Mesure réelle après normalisation** (pas une estimation) :

```text
590 850 formes normalisées distinctes retenues côté enz (inchangé)
293 166 formes normalisées distinctes retenues côté hippler
293 160 formes présentes dans LES DEUX sources après normalisation
    6   formes présentes UNIQUEMENT dans hippler après normalisation : ALF, ALFE, BÄT,
        ELAK, ETH, KÄM
590 856 termes au total dans la base fusionnée (590 850 + 6)
```

**Confirmation empirique de l'hypothèse de la recherche de faisabilité initiale** ("Les
6 398 mots présents localement mais absents de la version actuelle d'enz sont très
majoritairement des variantes orthographiques suisses en 'ss' à la place de 'ß'... probablement
retirées volontairement par enz en tant que doublons de la forme standard avec ß, pas une perte
de couverture réelle") : cette hypothèse était fondée sur une comparaison de CHAÎNES BRUTES
(6 398 formes brutes hippler absentes telles quelles du fichier enz). Une fois les deux sources
passées par le même pipeline de normalisation (ß → SS dans `normalize()`, comme pour toute autre
forme), une forme brute hippler en "ss" (ex. `Abschiedsgruss`) et la forme brute enz
correspondante en "ß" (ex. `Abschiedsgruß`) se rejoignent TOUTES DEUX en la même forme normalisée
(`ABSCHIEDSGRUSS`) — donc comptent comme UNE seule ligne, présente dans les DEUX sources, pas
deux lignes distinctes ni une perte. Sur les 6 398 formes brutes citées par la recherche
initiale, seules **6** sont de VRAIES formes absentes d'enz une fois normalisées — l'hypothèse
initiale ("quasi-totalité = doublons de graphie, pas une perte de couverture réelle") est donc
confirmée à 99,9 %, pas seulement plausible.

## Ce Qui N'est PAS Construit Dans Cette Passe (rappel explicite)

```text
aucun dictionnaire général allemand indépendant (pas d'équivalent Kartmaan/hbenbel/is_french) —
  modèle à deux statuts pour l'instant (admis / inconnu), troisième statut fermé faute de
  source combinant licence commerciale claire et indépendance réelle (voir section 3 du rapport
  de faisabilité)
aucune donnée de nature grammaticale/genre (pas d'équivalent D-018)
aucune définition lexicale (pas d'équivalent D-043)
aucun registre SEO (storage/seo_de.sqlite non construit) — Registry gère nativement son absence
  (noindex,follow par défaut, voir app/Seo/Registry.php), aucun impact fonctionnel
```

Ce document n'est pas un avis juridique.
