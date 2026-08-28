<?php

declare(strict_types=1);

// Configuration du site allemand. Structure heritee de config/sites/fr.php (docs/02),
// adaptee (pas de double lexique ODS8/ODS9, pas de colonne de dictionnaire general --
// voir docs/DECISIONS.md et data/raw/PROVENANCE.md pour le detail des sources).
// dictionary_path pointe vers storage/, hors du dossier web (public/) sur l'hebergement.
// seo_path reste inerte tant que storage/seo_de.sqlite n'existe pas -- App\Seo\Registry
// gere nativement son absence (noindex,follow par defaut), aucun impact fonctionnel.

return [
    'language' => 'de',
    'dictionary_path' => __DIR__ . '/../../storage/dictionary_de.sqlite',
    'seo_path' => __DIR__ . '/../../storage/seo_de.sqlite',

    // Un seul lexique (enz/german-wordlist, CC0-1.0) -- pas d'equivalent ODS8/ODS9
    // (aucune liste officielle allemande librement telechargeable en masse, voir
    // reports/de-site-feasibility-audit.md cote depot francais, §1). 'column' pointe
    // vers terms.is_admitted (schema.sql), toujours 1 pour toute ligne presente en base
    // dans cette premiere passe (source unique) -- conserve comme colonne distincte
    // plutot que supprimee pour permettre une extension future sans migration de schema.
    'lexicons' => [
        ['column' => 'is_admitted', 'badge' => 'Wortliste'],
    ],

    // Pas de colonne "dictionnaire general independant" pour l'allemand dans cette
    // premiere passe (pas d'equivalent is_french/Kartmaan -- voir data/raw/PROVENANCE.md,
    // section "Ce Qui N'est PAS Construit"). Modele a DEUX statuts pour l'instant : admis
    // / inconnu -- le troisieme statut du modele ferme (CLAUDE.md) reste structurellement
    // possible (TermPage::STATUS_FRENCH_NOT_ADMITTED, nom herite du site francais -- pas
    // renomme, changer un nom de constante partagee par tout app/Search est hors perimetre
    // d'un correctif de commentaire) mais n'est produit par aucune donnee
    // actuelle. null : cette cle n'est lue nulle part ailleurs dans app/ (verifie par
    // recherche exhaustive avant ce choix) -- app/Config.php la declare nullable pour ce
    // site, changement signale (fichier partage, CLAUDE.md).
    'general_language_column' => null,

    // Valeurs des tuiles allemandes (102 tuiles : 100 lettres + 2 blancs), confirmees par
    // deux sources independantes concordantes -- voir reports/de-site-feasibility-audit.md
    // cote depot francais, §2 -- puis RE-VERIFIEES directement contre Wikipedia
    // ("Scrabble letter distributions") a la demande explicite du porteur de projet,
    // repartition complete (nombre de tuiles ET valeur) recalculee a la main : totalise
    // exactement 102 (E15 N9 S7 I6 R6 T6 U6 A5 D4 H4 G3 L3 O3 M4 B2 W1 Z1 C2 F2 K2 P1 Ä1
    // J1 Ü1 V1 Ö1 X1 Q1 Y1 + 2 blancs) -- 0 divergence avec les valeurs deja presentes
    // ci-dessous, aucune correction necessaire. Doit rester identique a TILE_SCORES dans
    // scripts/lib/normalize.py -- toute derive entre les deux est detectee par
    // tests/Search/TermLookupTest.php, qui recalcule score() pour les lignes reelles de
    // storage/dictionary_de.sqlite et compare a la colonne stockee. ß n'a pas d'entree :
    // normalize() le convertit toujours en SS avant que score() ne soit appele (pas de
    // tuile ß dediee, regle officielle -- voir app/Search/Normalizer.php).
    'tile_scores' => [
        'E' => 1, 'N' => 1, 'S' => 1, 'I' => 1, 'R' => 1, 'T' => 1, 'U' => 1, 'A' => 1, 'D' => 1,
        'H' => 2, 'G' => 2, 'L' => 2, 'O' => 2,
        'M' => 3, 'B' => 3, 'W' => 3, 'Z' => 3,
        'C' => 4, 'F' => 4, 'K' => 4, 'P' => 4,
        'Ä' => 6, 'J' => 6, 'Ü' => 6, 'V' => 6,
        'Ö' => 8, 'X' => 8,
        'Q' => 10, 'Y' => 10,
    ],

    // Bornes identiques a MIN_LENGTH/MAX_LENGTH de scripts/lib/normalize.py (D-010,
    // heritee du site francais -- plateau 15x15 identique dans toutes les langues).
    'min_term_length' => 2,
    'max_term_length' => 15,

    // Domaine de production prevu : wordcheckr.de (decision utilisateur, voir la memoire
    // de session -- pas encore deploye, ce depot reste un travail local, docs/DECISIONS.md
    // a completer au moment du deploiement reel).
    'canonical_base_url' => 'https://www.wordcheckr.de',
];
