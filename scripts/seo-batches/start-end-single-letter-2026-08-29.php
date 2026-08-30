<?php

declare(strict_types=1);

/**
 * Palier 1 de "beginnend-mit"/"endend-mit" (docs/DECISIONS.md D-DE-017) : les 29 pages
 * /woerter/beginnend-mit/{lettre} (1 lettre, alphabet allemand complet -- A-Z + Ä/Ö/Ü) et les
 * 455 pages /woerter/endend-mit/{2 lettres} REELLEMENT REALISEES (sur 29x29 = 841 combinaisons
 * theoriques). Applique via :
 *
 *     php scripts/apply_seo_batch.php scripts/seo-batches/start-end-single-letter-2026-08-29.php
 *
 * ASYMETRIE DELIBEREE, MESUREE (PAS une erreur) -- beginnend-mit ouvre a 1 LETTRE, endend-mit
 * ouvre a 2 LETTRES : App\Search\RelationsFinder::relatedSearches() emet un lien reel et
 * inconditionnel vers le prefixe D'UNE SEULE LETTRE de CHAQUE mot admis (categorie startsWith,
 * toujours present), mais emet TOUJOURS un lien vers un suffixe de DEUX lettres, jamais un
 * seul (mb_substr($word, -min(2, $length)) avec Normalizer::MIN_LENGTH = 2 constant -- le
 * "min(2, $length)" vaut donc toujours 2). Consequence verifiee par mesure directe (pas
 * supposee) : sur les 29 pages endend-mit/{1 lettre} possibles, UNE SEULE (endend-mit/s) a un
 * lien entrant reel (app/View/home.php, phrase d'aide statique) -- les 28 autres seraient des
 * PAGES ORPHELINES si ouvertes (contrainte de role dure : "orphan pages marked index... should
 * not be indexable, full stop"). Les 455 pages endend-mit/{2 lettres} realisees, elles, ont
 * TOUTES un lien entrant reel et verifie (chaque mot se terminant par cette paire de lettres).
 * Voir docs/DECISIONS.md D-DE-017 pour le detail complet de cette mesure.
 *
 * result_count = compte REEL (mode EXACT pour beginnend-mit -- App\Search\WordListSolver::
 * solveExact(), aucun plafond ; mode BORNE pour endend-mit -- solveBounded(), plafonne a
 * WordListSolver::ROW_EXAMINATION_CEILING = 10 000 CORRESPONDANCES pour 12 des 455 suffixes,
 * voir D-DE-017 pour le detail et le precedent accepte cote francais D-028bis/D-029, pas un
 * defaut nouveau introduit ici). 0 page a resultat vide dans ce lot (chaque candidat vient
 * d'au moins un mot reel par construction) -- 0 exclusion R5 necessaire. 61 pages
 * endend-mit/{2 lettres} et 0 page beginnend-mit/{1 lettre} ont exactement 1 resultat --
 * GARDEES, signalees separement dans le rapport de tache, pas auto-exclues (docs/05, meme
 * consigne que le site francais D-029/D-030/D-031).
 *
 * Performance mesuree (pas supposee), balayage COMPLET des deux familles (pas un echantillon) :
 * beginnend-mit (29/29 combinaisons) : 0/29 au-dessus de 250 ms (min 0,23 ms, median 1,73 ms,
 *   max 5,21 ms, p95 3,27 ms) -- EXPLAIN QUERY PLAN : SEARCH terms USING COVERING INDEX
 *   sqlite_autoindex_terms_1, jamais un SCAN.
 * endend-mit (455/455 combinaisons realisees) : 0/455 au-dessus de 250 ms (min 0,14 ms, median
 *   0,46 ms, max 84,22 ms sur endend-mit/en, le plus gros panier plafonne, p95 30,38 ms) --
 *   EXPLAIN QUERY PLAN : SEARCH terms USING COVERING INDEX idx_terms_reversed, jamais un SCAN.
 *
 * Familles NON ouvertes par ce lot, chacune pour une raison technique distincte mesuree (voir
 * D-DE-017 pour le detail complet) :
 *   longueur+beginnend-mit combine : 0 lien entrant reel (list_counts vide, App\Search
 *     LengthLinksBuilder ne produit aucune section tant que cette table n'est pas peuplee --
 *     hors perimetre de cet agent, app/Search/, a router vers l'agent data-engine)
 *   beginnend-mit+endend-mit combine (les deux a 1 lettre) : 1 SEULE page reellement liee
 *     (beginnend-mit/a/endend-mit/e, lien statique unique app/View/home.php) sur 690
 *     combinaisons realisees -- 689 pages seraient orphelines
 *   beginnend-mit a 3 lettres : 3703 pages CANDIDATES avec un lien reel demontre (meme
 *     mecanisme relatedSearches, categorie startsWith 3 lettres si longueur > 3) -- mesure
 *     faite (0/echantillon au-dessus du budget), mais volume NON discute avec le proprietaire
 *     du produit dans cette passe (contrainte de role dure : jamais un lot sans discussion de
 *     volume) -- candidat explicite pour un palier 2 futur, chiffres complets dans le rapport
 *     de tache
 *   mit-buchstaben (avec) : liens reels EXISTANTS mais eparpilles (6861 combinaisons
 *     longueur+jusqu'a-3-lettres, chacune liee par SEULEMENT les mots qui ont EXACTEMENT cet
 *     ensemble comme leurs 3 premieres lettres distinctes triees -- pas un entonnoir propre
 *     comme le site francais D-029/D-030/D-031, un maillage ad hoc sans structure de palier
 *     coherente) -- explicitement exclu de cette passe par la tache recue, a mesurer plus
 *     finement avant un futur palier
 *   enthalten/ohne/muster : structurellement fermees en permanence (App\Seo\Family::
 *     NEVER_SITEMAP), aucune mesure ne change cette conclusion -- espace de combinaisons non
 *     borne, requete sans ancrage equivalente fonctionnellement a un parcours de table complet
 *     des que longueur/prefixe/suffixe sont absents (App\Search\WordListSolver, docblock de
 *     classe)
 *   position : 0 lien entrant reel (RelationsFinder::relatedSearches() n'emet aucun lien vers
 *     cette famille) -- orpheline par construction, fermee
 */
return array (
  'batch_id' => 'start-end-single-letter-2026-08-29',
  'added_at' => '2026-08-29',
  'rows' => 
  array (
    0 => 
    array (
      'route_path' => '/woerter/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 67727,
      'notes' => 'Verlinkt von JEDER der 67727 admittierten Wortseiten, die mit "A" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    1 => 
    array (
      'route_path' => '/woerter/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 40430,
      'notes' => 'Verlinkt von JEDER der 40430 admittierten Wortseiten, die mit "B" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    2 => 
    array (
      'route_path' => '/woerter/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 4470,
      'notes' => 'Verlinkt von JEDER der 4470 admittierten Wortseiten, die mit "C" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    3 => 
    array (
      'route_path' => '/woerter/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 21645,
      'notes' => 'Verlinkt von JEDER der 21645 admittierten Wortseiten, die mit "D" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    4 => 
    array (
      'route_path' => '/woerter/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 35914,
      'notes' => 'Verlinkt von JEDER der 35914 admittierten Wortseiten, die mit "E" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    5 => 
    array (
      'route_path' => '/woerter/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 25655,
      'notes' => 'Verlinkt von JEDER der 25655 admittierten Wortseiten, die mit "F" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    6 => 
    array (
      'route_path' => '/woerter/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 35790,
      'notes' => 'Verlinkt von JEDER der 35790 admittierten Wortseiten, die mit "G" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    7 => 
    array (
      'route_path' => '/woerter/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 29213,
      'notes' => 'Verlinkt von JEDER der 29213 admittierten Wortseiten, die mit "H" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    8 => 
    array (
      'route_path' => '/woerter/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 8275,
      'notes' => 'Verlinkt von JEDER der 8275 admittierten Wortseiten, die mit "I" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    9 => 
    array (
      'route_path' => '/woerter/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 3546,
      'notes' => 'Verlinkt von JEDER der 3546 admittierten Wortseiten, die mit "J" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    10 => 
    array (
      'route_path' => '/woerter/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 34466,
      'notes' => 'Verlinkt von JEDER der 34466 admittierten Wortseiten, die mit "K" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    11 => 
    array (
      'route_path' => '/woerter/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 17195,
      'notes' => 'Verlinkt von JEDER der 17195 admittierten Wortseiten, die mit "L" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    12 => 
    array (
      'route_path' => '/woerter/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 23788,
      'notes' => 'Verlinkt von JEDER der 23788 admittierten Wortseiten, die mit "M" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    13 => 
    array (
      'route_path' => '/woerter/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 13441,
      'notes' => 'Verlinkt von JEDER der 13441 admittierten Wortseiten, die mit "N" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    14 => 
    array (
      'route_path' => '/woerter/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 5768,
      'notes' => 'Verlinkt von JEDER der 5768 admittierten Wortseiten, die mit "O" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    15 => 
    array (
      'route_path' => '/woerter/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 25078,
      'notes' => 'Verlinkt von JEDER der 25078 admittierten Wortseiten, die mit "P" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    16 => 
    array (
      'route_path' => '/woerter/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 1943,
      'notes' => 'Verlinkt von JEDER der 1943 admittierten Wortseiten, die mit "Q" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    17 => 
    array (
      'route_path' => '/woerter/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 20996,
      'notes' => 'Verlinkt von JEDER der 20996 admittierten Wortseiten, die mit "R" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    18 => 
    array (
      'route_path' => '/woerter/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 55834,
      'notes' => 'Verlinkt von JEDER der 55834 admittierten Wortseiten, die mit "S" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    19 => 
    array (
      'route_path' => '/woerter/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 19910,
      'notes' => 'Verlinkt von JEDER der 19910 admittierten Wortseiten, die mit "T" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    20 => 
    array (
      'route_path' => '/woerter/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 19763,
      'notes' => 'Verlinkt von JEDER der 19763 admittierten Wortseiten, die mit "U" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    21 => 
    array (
      'route_path' => '/woerter/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 31441,
      'notes' => 'Verlinkt von JEDER der 31441 admittierten Wortseiten, die mit "V" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    22 => 
    array (
      'route_path' => '/woerter/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 21011,
      'notes' => 'Verlinkt von JEDER der 21011 admittierten Wortseiten, die mit "W" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    23 => 
    array (
      'route_path' => '/woerter/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 377,
      'notes' => 'Verlinkt von JEDER der 377 admittierten Wortseiten, die mit "X" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    24 => 
    array (
      'route_path' => '/woerter/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 165,
      'notes' => 'Verlinkt von JEDER der 165 admittierten Wortseiten, die mit "Y" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    25 => 
    array (
      'route_path' => '/woerter/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 19953,
      'notes' => 'Verlinkt von JEDER der 19953 admittierten Wortseiten, die mit "Z" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    26 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 1171,
      'notes' => 'Verlinkt von JEDER der 1171 admittierten Wortseiten, die mit "Ä" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    27 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 836,
      'notes' => 'Verlinkt von JEDER der 836 admittierten Wortseiten, die mit "Ö" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    28 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0001',
      'result_count' => 5055,
      'notes' => 'Verlinkt von JEDER der 5055 admittierten Wortseiten, die mit "Ü" beginnen, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie startsWith, 1-Buchstabe-Praefix, immer vorhanden, nie durch MAX_RELATED_SEARCHES=12 verdraengt) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    29 => 
    array (
      'route_path' => '/woerter/endend-mit/aa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/aa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "AA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    30 => 
    array (
      'route_path' => '/woerter/endend-mit/ab',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ab',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 215,
      'notes' => 'Verlinkt von JEDER der 215 admittierten Wortseiten, die auf "AB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    31 => 
    array (
      'route_path' => '/woerter/endend-mit/ac',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ac',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "AC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    32 => 
    array (
      'route_path' => '/woerter/endend-mit/ad',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ad',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 279,
      'notes' => 'Verlinkt von JEDER der 279 admittierten Wortseiten, die auf "AD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    33 => 
    array (
      'route_path' => '/woerter/endend-mit/ae',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ae',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 72,
      'notes' => 'Verlinkt von JEDER der 72 admittierten Wortseiten, die auf "AE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    34 => 
    array (
      'route_path' => '/woerter/endend-mit/af',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/af',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 111,
      'notes' => 'Verlinkt von JEDER der 111 admittierten Wortseiten, die auf "AF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    35 => 
    array (
      'route_path' => '/woerter/endend-mit/ag',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ag',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 759,
      'notes' => 'Verlinkt von JEDER der 759 admittierten Wortseiten, die auf "AG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    36 => 
    array (
      'route_path' => '/woerter/endend-mit/ah',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ah',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 108,
      'notes' => 'Verlinkt von JEDER der 108 admittierten Wortseiten, die auf "AH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    37 => 
    array (
      'route_path' => '/woerter/endend-mit/ai',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ai',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 60,
      'notes' => 'Verlinkt von JEDER der 60 admittierten Wortseiten, die auf "AI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    38 => 
    array (
      'route_path' => '/woerter/endend-mit/aj',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/aj',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "AJ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    39 => 
    array (
      'route_path' => '/woerter/endend-mit/ak',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ak',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 110,
      'notes' => 'Verlinkt von JEDER der 110 admittierten Wortseiten, die auf "AK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    40 => 
    array (
      'route_path' => '/woerter/endend-mit/al',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/al',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1157,
      'notes' => 'Verlinkt von JEDER der 1157 admittierten Wortseiten, die auf "AL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    41 => 
    array (
      'route_path' => '/woerter/endend-mit/am',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/am',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 299,
      'notes' => 'Verlinkt von JEDER der 299 admittierten Wortseiten, die auf "AM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    42 => 
    array (
      'route_path' => '/woerter/endend-mit/an',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/an',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 587,
      'notes' => 'Verlinkt von JEDER der 587 admittierten Wortseiten, die auf "AN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    43 => 
    array (
      'route_path' => '/woerter/endend-mit/ao',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ao',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "AO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    44 => 
    array (
      'route_path' => '/woerter/endend-mit/ap',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ap',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 28,
      'notes' => 'Verlinkt von JEDER der 28 admittierten Wortseiten, die auf "AP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    45 => 
    array (
      'route_path' => '/woerter/endend-mit/aq',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/endend-mit/q',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'CORRECTIF (D-DE-023, 2026-08-30) : DOUBLON DE CONTENU EXACT avec /woerter/endend-mit/q (INUPIAQ, seul mot des deux pages), desormais indexee -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php, portee cote DE par le meme raisonnement que le precedent D-DE-019 /woerter/7-buchstaben/endend-mit/q -> endend-mit/q) : entre deux pages terminant de contenu identique, la forme la PLUS COURTE/generale gagne ("q", profondeur 1, contre "aq", profondeur 2). Etait a tort la gagnante avant ce correctif (seule forme existante a l\'epoque, D-DE-017) ; canonical redirige desormais vers la forme courte.',
    ),
    46 => 
    array (
      'route_path' => '/woerter/endend-mit/ar',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ar',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1405,
      'notes' => 'Verlinkt von JEDER der 1405 admittierten Wortseiten, die auf "AR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    47 => 
    array (
      'route_path' => '/woerter/endend-mit/as',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/as',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1089,
      'notes' => 'Verlinkt von JEDER der 1089 admittierten Wortseiten, die auf "AS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    48 => 
    array (
      'route_path' => '/woerter/endend-mit/at',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/at',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1017,
      'notes' => 'Verlinkt von JEDER der 1017 admittierten Wortseiten, die auf "AT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    49 => 
    array (
      'route_path' => '/woerter/endend-mit/au',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/au',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 627,
      'notes' => 'Verlinkt von JEDER der 627 admittierten Wortseiten, die auf "AU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    50 => 
    array (
      'route_path' => '/woerter/endend-mit/av',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/av',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "AV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    51 => 
    array (
      'route_path' => '/woerter/endend-mit/aw',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/aw',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "AW" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    52 => 
    array (
      'route_path' => '/woerter/endend-mit/ax',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ax',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 34,
      'notes' => 'Verlinkt von JEDER der 34 admittierten Wortseiten, die auf "AX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    53 => 
    array (
      'route_path' => '/woerter/endend-mit/ay',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ay',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 42,
      'notes' => 'Verlinkt von JEDER der 42 admittierten Wortseiten, die auf "AY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    54 => 
    array (
      'route_path' => '/woerter/endend-mit/az',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/az',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "AZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    55 => 
    array (
      'route_path' => '/woerter/endend-mit/ba',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ba',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 40,
      'notes' => 'Verlinkt von JEDER der 40 admittierten Wortseiten, die auf "BA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    56 => 
    array (
      'route_path' => '/woerter/endend-mit/bb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 16,
      'notes' => 'Verlinkt von JEDER der 16 admittierten Wortseiten, die auf "BB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    57 => 
    array (
      'route_path' => '/woerter/endend-mit/bc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "BC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    58 => 
    array (
      'route_path' => '/woerter/endend-mit/be',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/be',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1843,
      'notes' => 'Verlinkt von JEDER der 1843 admittierten Wortseiten, die auf "BE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    59 => 
    array (
      'route_path' => '/woerter/endend-mit/bi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 39,
      'notes' => 'Verlinkt von JEDER der 39 admittierten Wortseiten, die auf "BI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    60 => 
    array (
      'route_path' => '/woerter/endend-mit/bl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "BL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    61 => 
    array (
      'route_path' => '/woerter/endend-mit/bo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 30,
      'notes' => 'Verlinkt von JEDER der 30 admittierten Wortseiten, die auf "BO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    62 => 
    array (
      'route_path' => '/woerter/endend-mit/bs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 504,
      'notes' => 'Verlinkt von JEDER der 504 admittierten Wortseiten, die auf "BS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    63 => 
    array (
      'route_path' => '/woerter/endend-mit/bt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 957,
      'notes' => 'Verlinkt von JEDER der 957 admittierten Wortseiten, die auf "BT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    64 => 
    array (
      'route_path' => '/woerter/endend-mit/bu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "BU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    65 => 
    array (
      'route_path' => '/woerter/endend-mit/by',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/by',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 21,
      'notes' => 'Verlinkt von JEDER der 21 admittierten Wortseiten, die auf "BY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    66 => 
    array (
      'route_path' => '/woerter/endend-mit/bä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "BÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    67 => 
    array (
      'route_path' => '/woerter/endend-mit/bö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/bö',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "BÖ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    68 => 
    array (
      'route_path' => '/woerter/endend-mit/ca',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ca',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 25,
      'notes' => 'Verlinkt von JEDER der 25 admittierten Wortseiten, die auf "CA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    69 => 
    array (
      'route_path' => '/woerter/endend-mit/cc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/cc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "CC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    70 => 
    array (
      'route_path' => '/woerter/endend-mit/cd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/cd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "CD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    71 => 
    array (
      'route_path' => '/woerter/endend-mit/ce',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ce',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 120,
      'notes' => 'Verlinkt von JEDER der 120 admittierten Wortseiten, die auf "CE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    72 => 
    array (
      'route_path' => '/woerter/endend-mit/ch',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ch',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6479,
      'notes' => 'Verlinkt von JEDER der 6479 admittierten Wortseiten, die auf "CH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    73 => 
    array (
      'route_path' => '/woerter/endend-mit/ci',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ci',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12,
      'notes' => 'Verlinkt von JEDER der 12 admittierten Wortseiten, die auf "CI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    74 => 
    array (
      'route_path' => '/woerter/endend-mit/ck',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ck',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1251,
      'notes' => 'Verlinkt von JEDER der 1251 admittierten Wortseiten, die auf "CK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    75 => 
    array (
      'route_path' => '/woerter/endend-mit/co',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/co',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 16,
      'notes' => 'Verlinkt von JEDER der 16 admittierten Wortseiten, die auf "CO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    76 => 
    array (
      'route_path' => '/woerter/endend-mit/cs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/cs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 34,
      'notes' => 'Verlinkt von JEDER der 34 admittierten Wortseiten, die auf "CS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    77 => 
    array (
      'route_path' => '/woerter/endend-mit/ct',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ct',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 14,
      'notes' => 'Verlinkt von JEDER der 14 admittierten Wortseiten, die auf "CT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    78 => 
    array (
      'route_path' => '/woerter/endend-mit/cu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/cu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "CU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    79 => 
    array (
      'route_path' => '/woerter/endend-mit/cy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/cy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "CY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    80 => 
    array (
      'route_path' => '/woerter/endend-mit/da',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/da',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 88,
      'notes' => 'Verlinkt von JEDER der 88 admittierten Wortseiten, die auf "DA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    81 => 
    array (
      'route_path' => '/woerter/endend-mit/dd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "DD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    82 => 
    array (
      'route_path' => '/woerter/endend-mit/de',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/de',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 13508,
      'notes' => 'Verlinkt von JEDER der 13508 admittierten Wortseiten, die auf "DE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    83 => 
    array (
      'route_path' => '/woerter/endend-mit/di',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/di',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 56,
      'notes' => 'Verlinkt von JEDER der 56 admittierten Wortseiten, die auf "DI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    84 => 
    array (
      'route_path' => '/woerter/endend-mit/dl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 23,
      'notes' => 'Verlinkt von JEDER der 23 admittierten Wortseiten, die auf "DL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    85 => 
    array (
      'route_path' => '/woerter/endend-mit/dm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "DM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    86 => 
    array (
      'route_path' => '/woerter/endend-mit/dn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "DN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    87 => 
    array (
      'route_path' => '/woerter/endend-mit/do',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/do',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 88,
      'notes' => 'Verlinkt von JEDER der 88 admittierten Wortseiten, die auf "DO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    88 => 
    array (
      'route_path' => '/woerter/endend-mit/dr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "DR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    89 => 
    array (
      'route_path' => '/woerter/endend-mit/ds',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ds',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2072,
      'notes' => 'Verlinkt von JEDER der 2072 admittierten Wortseiten, die auf "DS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    90 => 
    array (
      'route_path' => '/woerter/endend-mit/dt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 166,
      'notes' => 'Verlinkt von JEDER der 166 admittierten Wortseiten, die auf "DT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    91 => 
    array (
      'route_path' => '/woerter/endend-mit/du',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/du',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12,
      'notes' => 'Verlinkt von JEDER der 12 admittierten Wortseiten, die auf "DU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    92 => 
    array (
      'route_path' => '/woerter/endend-mit/dv',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dv',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "DV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    93 => 
    array (
      'route_path' => '/woerter/endend-mit/dy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 32,
      'notes' => 'Verlinkt von JEDER der 32 admittierten Wortseiten, die auf "DY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    94 => 
    array (
      'route_path' => '/woerter/endend-mit/dä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/dä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "DÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    95 => 
    array (
      'route_path' => '/woerter/endend-mit/ea',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ea',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 25,
      'notes' => 'Verlinkt von JEDER der 25 admittierten Wortseiten, die auf "EA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    96 => 
    array (
      'route_path' => '/woerter/endend-mit/eb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/eb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 429,
      'notes' => 'Verlinkt von JEDER der 429 admittierten Wortseiten, die auf "EB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    97 => 
    array (
      'route_path' => '/woerter/endend-mit/ec',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ec',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "EC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    98 => 
    array (
      'route_path' => '/woerter/endend-mit/ed',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ed',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 162,
      'notes' => 'Verlinkt von JEDER der 162 admittierten Wortseiten, die auf "ED" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    99 => 
    array (
      'route_path' => '/woerter/endend-mit/ee',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ee',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 274,
      'notes' => 'Verlinkt von JEDER der 274 admittierten Wortseiten, die auf "EE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    100 => 
    array (
      'route_path' => '/woerter/endend-mit/ef',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ef',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 234,
      'notes' => 'Verlinkt von JEDER der 234 admittierten Wortseiten, die auf "EF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    101 => 
    array (
      'route_path' => '/woerter/endend-mit/eg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/eg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 459,
      'notes' => 'Verlinkt von JEDER der 459 admittierten Wortseiten, die auf "EG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    102 => 
    array (
      'route_path' => '/woerter/endend-mit/eh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/eh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 314,
      'notes' => 'Verlinkt von JEDER der 314 admittierten Wortseiten, die auf "EH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    103 => 
    array (
      'route_path' => '/woerter/endend-mit/ei',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ei',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1009,
      'notes' => 'Verlinkt von JEDER der 1009 admittierten Wortseiten, die auf "EI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    104 => 
    array (
      'route_path' => '/woerter/endend-mit/ek',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ek',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 50,
      'notes' => 'Verlinkt von JEDER der 50 admittierten Wortseiten, die auf "EK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    105 => 
    array (
      'route_path' => '/woerter/endend-mit/el',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/el',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3955,
      'notes' => 'Verlinkt von JEDER der 3955 admittierten Wortseiten, die auf "EL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    106 => 
    array (
      'route_path' => '/woerter/endend-mit/em',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/em',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 38346,
      'notes' => 'Verlinkt von JEDER der 38346 admittierten Wortseiten, die auf "EM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    107 => 
    array (
      'route_path' => '/woerter/endend-mit/en',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/en',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 108008,
      'notes' => 'Verlinkt von JEDER der 108008 admittierten Wortseiten, die auf "EN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    108 => 
    array (
      'route_path' => '/woerter/endend-mit/eo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/eo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 19,
      'notes' => 'Verlinkt von JEDER der 19 admittierten Wortseiten, die auf "EO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    109 => 
    array (
      'route_path' => '/woerter/endend-mit/ep',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ep',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 13,
      'notes' => 'Verlinkt von JEDER der 13 admittierten Wortseiten, die auf "EP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    110 => 
    array (
      'route_path' => '/woerter/endend-mit/er',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/er',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 52787,
      'notes' => 'Verlinkt von JEDER der 52787 admittierten Wortseiten, die auf "ER" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    111 => 
    array (
      'route_path' => '/woerter/endend-mit/es',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/es',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 55999,
      'notes' => 'Verlinkt von JEDER der 55999 admittierten Wortseiten, die auf "ES" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    112 => 
    array (
      'route_path' => '/woerter/endend-mit/et',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/et',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 22030,
      'notes' => 'Verlinkt von JEDER der 22030 admittierten Wortseiten, die auf "ET" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    113 => 
    array (
      'route_path' => '/woerter/endend-mit/eu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/eu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 82,
      'notes' => 'Verlinkt von JEDER der 82 admittierten Wortseiten, die auf "EU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    114 => 
    array (
      'route_path' => '/woerter/endend-mit/ev',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ev',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "EV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    115 => 
    array (
      'route_path' => '/woerter/endend-mit/ew',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ew',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "EW" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    116 => 
    array (
      'route_path' => '/woerter/endend-mit/ex',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ex',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 91,
      'notes' => 'Verlinkt von JEDER der 91 admittierten Wortseiten, die auf "EX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    117 => 
    array (
      'route_path' => '/woerter/endend-mit/ey',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ey',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 31,
      'notes' => 'Verlinkt von JEDER der 31 admittierten Wortseiten, die auf "EY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    118 => 
    array (
      'route_path' => '/woerter/endend-mit/ez',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ez',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 18,
      'notes' => 'Verlinkt von JEDER der 18 admittierten Wortseiten, die auf "EZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    119 => 
    array (
      'route_path' => '/woerter/endend-mit/fa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10,
      'notes' => 'Verlinkt von JEDER der 10 admittierten Wortseiten, die auf "FA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    120 => 
    array (
      'route_path' => '/woerter/endend-mit/fe',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fe',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2431,
      'notes' => 'Verlinkt von JEDER der 2431 admittierten Wortseiten, die auf "FE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    121 => 
    array (
      'route_path' => '/woerter/endend-mit/ff',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ff',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 424,
      'notes' => 'Verlinkt von JEDER der 424 admittierten Wortseiten, die auf "FF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    122 => 
    array (
      'route_path' => '/woerter/endend-mit/fi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 24,
      'notes' => 'Verlinkt von JEDER der 24 admittierten Wortseiten, die auf "FI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    123 => 
    array (
      'route_path' => '/woerter/endend-mit/fl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "FL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    124 => 
    array (
      'route_path' => '/woerter/endend-mit/fm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "FM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    125 => 
    array (
      'route_path' => '/woerter/endend-mit/fn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "FN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    126 => 
    array (
      'route_path' => '/woerter/endend-mit/fo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "FO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    127 => 
    array (
      'route_path' => '/woerter/endend-mit/fs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1187,
      'notes' => 'Verlinkt von JEDER der 1187 admittierten Wortseiten, die auf "FS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    128 => 
    array (
      'route_path' => '/woerter/endend-mit/ft',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ft',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1989,
      'notes' => 'Verlinkt von JEDER der 1989 admittierten Wortseiten, die auf "FT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    129 => 
    array (
      'route_path' => '/woerter/endend-mit/fu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "FU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    130 => 
    array (
      'route_path' => '/woerter/endend-mit/fy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "FY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    131 => 
    array (
      'route_path' => '/woerter/endend-mit/fz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/fz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "FZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    132 => 
    array (
      'route_path' => '/woerter/endend-mit/ga',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ga',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 53,
      'notes' => 'Verlinkt von JEDER der 53 admittierten Wortseiten, die auf "GA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    133 => 
    array (
      'route_path' => '/woerter/endend-mit/gd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 38,
      'notes' => 'Verlinkt von JEDER der 38 admittierten Wortseiten, die auf "GD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    134 => 
    array (
      'route_path' => '/woerter/endend-mit/ge',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ge',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8106,
      'notes' => 'Verlinkt von JEDER der 8106 admittierten Wortseiten, die auf "GE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    135 => 
    array (
      'route_path' => '/woerter/endend-mit/gg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 20,
      'notes' => 'Verlinkt von JEDER der 20 admittierten Wortseiten, die auf "GG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    136 => 
    array (
      'route_path' => '/woerter/endend-mit/gh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "GH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    137 => 
    array (
      'route_path' => '/woerter/endend-mit/gi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 20,
      'notes' => 'Verlinkt von JEDER der 20 admittierten Wortseiten, die auf "GI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    138 => 
    array (
      'route_path' => '/woerter/endend-mit/gl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "GL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    139 => 
    array (
      'route_path' => '/woerter/endend-mit/gn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "GN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    140 => 
    array (
      'route_path' => '/woerter/endend-mit/go',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/go',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 66,
      'notes' => 'Verlinkt von JEDER der 66 admittierten Wortseiten, die auf "GO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    141 => 
    array (
      'route_path' => '/woerter/endend-mit/gr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "GR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    142 => 
    array (
      'route_path' => '/woerter/endend-mit/gs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2332,
      'notes' => 'Verlinkt von JEDER der 2332 admittierten Wortseiten, die auf "GS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    143 => 
    array (
      'route_path' => '/woerter/endend-mit/gt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2098,
      'notes' => 'Verlinkt von JEDER der 2098 admittierten Wortseiten, die auf "GT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    144 => 
    array (
      'route_path' => '/woerter/endend-mit/gu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "GU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    145 => 
    array (
      'route_path' => '/woerter/endend-mit/gy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/gy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "GY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    146 => 
    array (
      'route_path' => '/woerter/endend-mit/ha',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ha',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 56,
      'notes' => 'Verlinkt von JEDER der 56 admittierten Wortseiten, die auf "HA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    147 => 
    array (
      'route_path' => '/woerter/endend-mit/hd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "HD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    148 => 
    array (
      'route_path' => '/woerter/endend-mit/he',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/he',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7340,
      'notes' => 'Verlinkt von JEDER der 7340 admittierten Wortseiten, die auf "HE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    149 => 
    array (
      'route_path' => '/woerter/endend-mit/hi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 61,
      'notes' => 'Verlinkt von JEDER der 61 admittierten Wortseiten, die auf "HI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    150 => 
    array (
      'route_path' => '/woerter/endend-mit/hl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 570,
      'notes' => 'Verlinkt von JEDER der 570 admittierten Wortseiten, die auf "HL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    151 => 
    array (
      'route_path' => '/woerter/endend-mit/hm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 198,
      'notes' => 'Verlinkt von JEDER der 198 admittierten Wortseiten, die auf "HM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    152 => 
    array (
      'route_path' => '/woerter/endend-mit/hn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 749,
      'notes' => 'Verlinkt von JEDER der 749 admittierten Wortseiten, die auf "HN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    153 => 
    array (
      'route_path' => '/woerter/endend-mit/ho',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ho',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 33,
      'notes' => 'Verlinkt von JEDER der 33 admittierten Wortseiten, die auf "HO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    154 => 
    array (
      'route_path' => '/woerter/endend-mit/hp',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hp',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "HP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    155 => 
    array (
      'route_path' => '/woerter/endend-mit/hr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 681,
      'notes' => 'Verlinkt von JEDER der 681 admittierten Wortseiten, die auf "HR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    156 => 
    array (
      'route_path' => '/woerter/endend-mit/hs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1972,
      'notes' => 'Verlinkt von JEDER der 1972 admittierten Wortseiten, die auf "HS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    157 => 
    array (
      'route_path' => '/woerter/endend-mit/ht',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ht',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3118,
      'notes' => 'Verlinkt von JEDER der 3118 admittierten Wortseiten, die auf "HT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    158 => 
    array (
      'route_path' => '/woerter/endend-mit/hu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 15,
      'notes' => 'Verlinkt von JEDER der 15 admittierten Wortseiten, die auf "HU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    159 => 
    array (
      'route_path' => '/woerter/endend-mit/hz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "HZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    160 => 
    array (
      'route_path' => '/woerter/endend-mit/hä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "HÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    161 => 
    array (
      'route_path' => '/woerter/endend-mit/hö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hö',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "HÖ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    162 => 
    array (
      'route_path' => '/woerter/endend-mit/hü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/hü',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "HÜ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    163 => 
    array (
      'route_path' => '/woerter/endend-mit/ia',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ia',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 192,
      'notes' => 'Verlinkt von JEDER der 192 admittierten Wortseiten, die auf "IA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    164 => 
    array (
      'route_path' => '/woerter/endend-mit/ib',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ib',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 117,
      'notes' => 'Verlinkt von JEDER der 117 admittierten Wortseiten, die auf "IB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    165 => 
    array (
      'route_path' => '/woerter/endend-mit/ic',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ic',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 21,
      'notes' => 'Verlinkt von JEDER der 21 admittierten Wortseiten, die auf "IC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    166 => 
    array (
      'route_path' => '/woerter/endend-mit/id',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/id',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 347,
      'notes' => 'Verlinkt von JEDER der 347 admittierten Wortseiten, die auf "ID" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    167 => 
    array (
      'route_path' => '/woerter/endend-mit/ie',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ie',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2220,
      'notes' => 'Verlinkt von JEDER der 2220 admittierten Wortseiten, die auf "IE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    168 => 
    array (
      'route_path' => '/woerter/endend-mit/if',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/if',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 115,
      'notes' => 'Verlinkt von JEDER der 115 admittierten Wortseiten, die auf "IF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    169 => 
    array (
      'route_path' => '/woerter/endend-mit/ig',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ig',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3332,
      'notes' => 'Verlinkt von JEDER der 3332 admittierten Wortseiten, die auf "IG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    170 => 
    array (
      'route_path' => '/woerter/endend-mit/ih',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ih',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 30,
      'notes' => 'Verlinkt von JEDER der 30 admittierten Wortseiten, die auf "IH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    171 => 
    array (
      'route_path' => '/woerter/endend-mit/ii',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ii',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "II" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    172 => 
    array (
      'route_path' => '/woerter/endend-mit/ik',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ik',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 852,
      'notes' => 'Verlinkt von JEDER der 852 admittierten Wortseiten, die auf "IK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    173 => 
    array (
      'route_path' => '/woerter/endend-mit/il',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/il',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 498,
      'notes' => 'Verlinkt von JEDER der 498 admittierten Wortseiten, die auf "IL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    174 => 
    array (
      'route_path' => '/woerter/endend-mit/im',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/im',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 128,
      'notes' => 'Verlinkt von JEDER der 128 admittierten Wortseiten, die auf "IM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    175 => 
    array (
      'route_path' => '/woerter/endend-mit/in',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/in',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5456,
      'notes' => 'Verlinkt von JEDER der 5456 admittierten Wortseiten, die auf "IN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    176 => 
    array (
      'route_path' => '/woerter/endend-mit/io',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/io',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 78,
      'notes' => 'Verlinkt von JEDER der 78 admittierten Wortseiten, die auf "IO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    177 => 
    array (
      'route_path' => '/woerter/endend-mit/ip',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ip',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 59,
      'notes' => 'Verlinkt von JEDER der 59 admittierten Wortseiten, die auf "IP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    178 => 
    array (
      'route_path' => '/woerter/endend-mit/ir',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ir',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 53,
      'notes' => 'Verlinkt von JEDER der 53 admittierten Wortseiten, die auf "IR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    179 => 
    array (
      'route_path' => '/woerter/endend-mit/is',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/is',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1603,
      'notes' => 'Verlinkt von JEDER der 1603 admittierten Wortseiten, die auf "IS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    180 => 
    array (
      'route_path' => '/woerter/endend-mit/it',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/it',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2349,
      'notes' => 'Verlinkt von JEDER der 2349 admittierten Wortseiten, die auf "IT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    181 => 
    array (
      'route_path' => '/woerter/endend-mit/iv',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/iv',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 450,
      'notes' => 'Verlinkt von JEDER der 450 admittierten Wortseiten, die auf "IV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    182 => 
    array (
      'route_path' => '/woerter/endend-mit/ix',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ix',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 63,
      'notes' => 'Verlinkt von JEDER der 63 admittierten Wortseiten, die auf "IX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    183 => 
    array (
      'route_path' => '/woerter/endend-mit/iz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/iz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 68,
      'notes' => 'Verlinkt von JEDER der 68 admittierten Wortseiten, die auf "IZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    184 => 
    array (
      'route_path' => '/woerter/endend-mit/iä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/iä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "IÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    185 => 
    array (
      'route_path' => '/woerter/endend-mit/ja',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ja',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 30,
      'notes' => 'Verlinkt von JEDER der 30 admittierten Wortseiten, die auf "JA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    186 => 
    array (
      'route_path' => '/woerter/endend-mit/je',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/je',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 40,
      'notes' => 'Verlinkt von JEDER der 40 admittierten Wortseiten, die auf "JE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    187 => 
    array (
      'route_path' => '/woerter/endend-mit/ji',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ji',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "JI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    188 => 
    array (
      'route_path' => '/woerter/endend-mit/jm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/jm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "JM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    189 => 
    array (
      'route_path' => '/woerter/endend-mit/jo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/jo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "JO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    190 => 
    array (
      'route_path' => '/woerter/endend-mit/jt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/jt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "JT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    191 => 
    array (
      'route_path' => '/woerter/endend-mit/ju',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ju',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "JU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    192 => 
    array (
      'route_path' => '/woerter/endend-mit/ka',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ka',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 182,
      'notes' => 'Verlinkt von JEDER der 182 admittierten Wortseiten, die auf "KA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    193 => 
    array (
      'route_path' => '/woerter/endend-mit/ke',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ke',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2604,
      'notes' => 'Verlinkt von JEDER der 2604 admittierten Wortseiten, die auf "KE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    194 => 
    array (
      'route_path' => '/woerter/endend-mit/kh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/kh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "KH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    195 => 
    array (
      'route_path' => '/woerter/endend-mit/ki',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ki',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 57,
      'notes' => 'Verlinkt von JEDER der 57 admittierten Wortseiten, die auf "KI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    196 => 
    array (
      'route_path' => '/woerter/endend-mit/kk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/kk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "KK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    197 => 
    array (
      'route_path' => '/woerter/endend-mit/kl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/kl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "KL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    198 => 
    array (
      'route_path' => '/woerter/endend-mit/ko',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ko',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 55,
      'notes' => 'Verlinkt von JEDER der 55 admittierten Wortseiten, die auf "KO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    199 => 
    array (
      'route_path' => '/woerter/endend-mit/kr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/kr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "KR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    200 => 
    array (
      'route_path' => '/woerter/endend-mit/ks',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ks',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1395,
      'notes' => 'Verlinkt von JEDER der 1395 admittierten Wortseiten, die auf "KS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    201 => 
    array (
      'route_path' => '/woerter/endend-mit/kt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/kt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1798,
      'notes' => 'Verlinkt von JEDER der 1798 admittierten Wortseiten, die auf "KT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    202 => 
    array (
      'route_path' => '/woerter/endend-mit/ku',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ku',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 14,
      'notes' => 'Verlinkt von JEDER der 14 admittierten Wortseiten, die auf "KU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    203 => 
    array (
      'route_path' => '/woerter/endend-mit/kw',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/kw',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "KW" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    204 => 
    array (
      'route_path' => '/woerter/endend-mit/ky',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ky',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "KY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    205 => 
    array (
      'route_path' => '/woerter/endend-mit/la',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/la',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 194,
      'notes' => 'Verlinkt von JEDER der 194 admittierten Wortseiten, die auf "LA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    206 => 
    array (
      'route_path' => '/woerter/endend-mit/lb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 68,
      'notes' => 'Verlinkt von JEDER der 68 admittierten Wortseiten, die auf "LB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    207 => 
    array (
      'route_path' => '/woerter/endend-mit/ld',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ld',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 583,
      'notes' => 'Verlinkt von JEDER der 583 admittierten Wortseiten, die auf "LD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    208 => 
    array (
      'route_path' => '/woerter/endend-mit/le',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/le',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6982,
      'notes' => 'Verlinkt von JEDER der 6982 admittierten Wortseiten, die auf "LE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    209 => 
    array (
      'route_path' => '/woerter/endend-mit/lf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 94,
      'notes' => 'Verlinkt von JEDER der 94 admittierten Wortseiten, die auf "LF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    210 => 
    array (
      'route_path' => '/woerter/endend-mit/lg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 46,
      'notes' => 'Verlinkt von JEDER der 46 admittierten Wortseiten, die auf "LG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    211 => 
    array (
      'route_path' => '/woerter/endend-mit/lh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "LH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    212 => 
    array (
      'route_path' => '/woerter/endend-mit/li',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/li',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 144,
      'notes' => 'Verlinkt von JEDER der 144 admittierten Wortseiten, die auf "LI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    213 => 
    array (
      'route_path' => '/woerter/endend-mit/lk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 94,
      'notes' => 'Verlinkt von JEDER der 94 admittierten Wortseiten, die auf "LK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    214 => 
    array (
      'route_path' => '/woerter/endend-mit/ll',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ll',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1214,
      'notes' => 'Verlinkt von JEDER der 1214 admittierten Wortseiten, die auf "LL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    215 => 
    array (
      'route_path' => '/woerter/endend-mit/lm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 119,
      'notes' => 'Verlinkt von JEDER der 119 admittierten Wortseiten, die auf "LM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    216 => 
    array (
      'route_path' => '/woerter/endend-mit/ln',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ln',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2997,
      'notes' => 'Verlinkt von JEDER der 2997 admittierten Wortseiten, die auf "LN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    217 => 
    array (
      'route_path' => '/woerter/endend-mit/lo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 65,
      'notes' => 'Verlinkt von JEDER der 65 admittierten Wortseiten, die auf "LO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    218 => 
    array (
      'route_path' => '/woerter/endend-mit/lp',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lp',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 18,
      'notes' => 'Verlinkt von JEDER der 18 admittierten Wortseiten, die auf "LP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    219 => 
    array (
      'route_path' => '/woerter/endend-mit/lr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "LR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    220 => 
    array (
      'route_path' => '/woerter/endend-mit/ls',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ls',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4057,
      'notes' => 'Verlinkt von JEDER der 4057 admittierten Wortseiten, die auf "LS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    221 => 
    array (
      'route_path' => '/woerter/endend-mit/lt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3494,
      'notes' => 'Verlinkt von JEDER der 3494 admittierten Wortseiten, die auf "LT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    222 => 
    array (
      'route_path' => '/woerter/endend-mit/lu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "LU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    223 => 
    array (
      'route_path' => '/woerter/endend-mit/lv',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lv',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "LV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    224 => 
    array (
      'route_path' => '/woerter/endend-mit/ly',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ly',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 13,
      'notes' => 'Verlinkt von JEDER der 13 admittierten Wortseiten, die auf "LY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    225 => 
    array (
      'route_path' => '/woerter/endend-mit/lz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 284,
      'notes' => 'Verlinkt von JEDER der 284 admittierten Wortseiten, die auf "LZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    226 => 
    array (
      'route_path' => '/woerter/endend-mit/lä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "LÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    227 => 
    array (
      'route_path' => '/woerter/endend-mit/lü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/lü',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "LÜ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    228 => 
    array (
      'route_path' => '/woerter/endend-mit/ma',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ma',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 281,
      'notes' => 'Verlinkt von JEDER der 281 admittierten Wortseiten, die auf "MA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    229 => 
    array (
      'route_path' => '/woerter/endend-mit/mb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "MB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    230 => 
    array (
      'route_path' => '/woerter/endend-mit/md',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/md',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 50,
      'notes' => 'Verlinkt von JEDER der 50 admittierten Wortseiten, die auf "MD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    231 => 
    array (
      'route_path' => '/woerter/endend-mit/me',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/me',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2867,
      'notes' => 'Verlinkt von JEDER der 2867 admittierten Wortseiten, die auf "ME" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    232 => 
    array (
      'route_path' => '/woerter/endend-mit/mh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "MH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    233 => 
    array (
      'route_path' => '/woerter/endend-mit/mi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 60,
      'notes' => 'Verlinkt von JEDER der 60 admittierten Wortseiten, die auf "MI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    234 => 
    array (
      'route_path' => '/woerter/endend-mit/ml',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ml',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "ML" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    235 => 
    array (
      'route_path' => '/woerter/endend-mit/mm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 416,
      'notes' => 'Verlinkt von JEDER der 416 admittierten Wortseiten, die auf "MM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    236 => 
    array (
      'route_path' => '/woerter/endend-mit/mn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "MN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    237 => 
    array (
      'route_path' => '/woerter/endend-mit/mo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 24,
      'notes' => 'Verlinkt von JEDER der 24 admittierten Wortseiten, die auf "MO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    238 => 
    array (
      'route_path' => '/woerter/endend-mit/mp',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mp',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 44,
      'notes' => 'Verlinkt von JEDER der 44 admittierten Wortseiten, die auf "MP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    239 => 
    array (
      'route_path' => '/woerter/endend-mit/ms',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ms',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2333,
      'notes' => 'Verlinkt von JEDER der 2333 admittierten Wortseiten, die auf "MS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    240 => 
    array (
      'route_path' => '/woerter/endend-mit/mt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1000,
      'notes' => 'Verlinkt von JEDER der 1000 admittierten Wortseiten, die auf "MT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    241 => 
    array (
      'route_path' => '/woerter/endend-mit/mu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "MU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    242 => 
    array (
      'route_path' => '/woerter/endend-mit/my',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/my',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "MY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    243 => 
    array (
      'route_path' => '/woerter/endend-mit/mä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/mä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "MÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    244 => 
    array (
      'route_path' => '/woerter/endend-mit/na',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/na',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 175,
      'notes' => 'Verlinkt von JEDER der 175 admittierten Wortseiten, die auf "NA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    245 => 
    array (
      'route_path' => '/woerter/endend-mit/nc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "NC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    246 => 
    array (
      'route_path' => '/woerter/endend-mit/nd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12545,
      'notes' => 'Verlinkt von JEDER der 12545 admittierten Wortseiten, die auf "ND" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    247 => 
    array (
      'route_path' => '/woerter/endend-mit/ne',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ne',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6112,
      'notes' => 'Verlinkt von JEDER der 6112 admittierten Wortseiten, die auf "NE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    248 => 
    array (
      'route_path' => '/woerter/endend-mit/nf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "NF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    249 => 
    array (
      'route_path' => '/woerter/endend-mit/ng',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ng',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7125,
      'notes' => 'Verlinkt von JEDER der 7125 admittierten Wortseiten, die auf "NG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    250 => 
    array (
      'route_path' => '/woerter/endend-mit/ni',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ni',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 100,
      'notes' => 'Verlinkt von JEDER der 100 admittierten Wortseiten, die auf "NI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    251 => 
    array (
      'route_path' => '/woerter/endend-mit/nk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 490,
      'notes' => 'Verlinkt von JEDER der 490 admittierten Wortseiten, die auf "NK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    252 => 
    array (
      'route_path' => '/woerter/endend-mit/nn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 519,
      'notes' => 'Verlinkt von JEDER der 519 admittierten Wortseiten, die auf "NN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    253 => 
    array (
      'route_path' => '/woerter/endend-mit/no',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/no',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 84,
      'notes' => 'Verlinkt von JEDER der 84 admittierten Wortseiten, die auf "NO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    254 => 
    array (
      'route_path' => '/woerter/endend-mit/nr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "NR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    255 => 
    array (
      'route_path' => '/woerter/endend-mit/ns',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ns',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 14674,
      'notes' => 'Verlinkt von JEDER der 14674 admittierten Wortseiten, die auf "NS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    256 => 
    array (
      'route_path' => '/woerter/endend-mit/nt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1596,
      'notes' => 'Verlinkt von JEDER der 1596 admittierten Wortseiten, die auf "NT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    257 => 
    array (
      'route_path' => '/woerter/endend-mit/nu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "NU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    258 => 
    array (
      'route_path' => '/woerter/endend-mit/nx',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nx',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "NX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    259 => 
    array (
      'route_path' => '/woerter/endend-mit/ny',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ny',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "NY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    260 => 
    array (
      'route_path' => '/woerter/endend-mit/nz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 524,
      'notes' => 'Verlinkt von JEDER der 524 admittierten Wortseiten, die auf "NZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    261 => 
    array (
      'route_path' => '/woerter/endend-mit/nä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "NÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    262 => 
    array (
      'route_path' => '/woerter/endend-mit/nö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nö',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "NÖ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    263 => 
    array (
      'route_path' => '/woerter/endend-mit/nü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/nü',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 16,
      'notes' => 'Verlinkt von JEDER der 16 admittierten Wortseiten, die auf "NÜ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    264 => 
    array (
      'route_path' => '/woerter/endend-mit/oa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "OA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    265 => 
    array (
      'route_path' => '/woerter/endend-mit/ob',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ob',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 101,
      'notes' => 'Verlinkt von JEDER der 101 admittierten Wortseiten, die auf "OB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    266 => 
    array (
      'route_path' => '/woerter/endend-mit/oc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "OC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    267 => 
    array (
      'route_path' => '/woerter/endend-mit/od',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/od',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 64,
      'notes' => 'Verlinkt von JEDER der 64 admittierten Wortseiten, die auf "OD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    268 => 
    array (
      'route_path' => '/woerter/endend-mit/oe',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oe',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 16,
      'notes' => 'Verlinkt von JEDER der 16 admittierten Wortseiten, die auf "OE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    269 => 
    array (
      'route_path' => '/woerter/endend-mit/of',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/of',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 111,
      'notes' => 'Verlinkt von JEDER der 111 admittierten Wortseiten, die auf "OF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    270 => 
    array (
      'route_path' => '/woerter/endend-mit/og',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/og',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 198,
      'notes' => 'Verlinkt von JEDER der 198 admittierten Wortseiten, die auf "OG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    271 => 
    array (
      'route_path' => '/woerter/endend-mit/oh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 39,
      'notes' => 'Verlinkt von JEDER der 39 admittierten Wortseiten, die auf "OH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    272 => 
    array (
      'route_path' => '/woerter/endend-mit/oi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 32,
      'notes' => 'Verlinkt von JEDER der 32 admittierten Wortseiten, die auf "OI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    273 => 
    array (
      'route_path' => '/woerter/endend-mit/oj',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oj',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "OJ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    274 => 
    array (
      'route_path' => '/woerter/endend-mit/ok',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ok',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 35,
      'notes' => 'Verlinkt von JEDER der 35 admittierten Wortseiten, die auf "OK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    275 => 
    array (
      'route_path' => '/woerter/endend-mit/ol',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ol',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 200,
      'notes' => 'Verlinkt von JEDER der 200 admittierten Wortseiten, die auf "OL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    276 => 
    array (
      'route_path' => '/woerter/endend-mit/om',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/om',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 280,
      'notes' => 'Verlinkt von JEDER der 280 admittierten Wortseiten, die auf "OM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    277 => 
    array (
      'route_path' => '/woerter/endend-mit/on',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/on',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2246,
      'notes' => 'Verlinkt von JEDER der 2246 admittierten Wortseiten, die auf "ON" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    278 => 
    array (
      'route_path' => '/woerter/endend-mit/oo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10,
      'notes' => 'Verlinkt von JEDER der 10 admittierten Wortseiten, die auf "OO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    279 => 
    array (
      'route_path' => '/woerter/endend-mit/op',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/op',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 130,
      'notes' => 'Verlinkt von JEDER der 130 admittierten Wortseiten, die auf "OP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    280 => 
    array (
      'route_path' => '/woerter/endend-mit/or',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/or',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 702,
      'notes' => 'Verlinkt von JEDER der 702 admittierten Wortseiten, die auf "OR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    281 => 
    array (
      'route_path' => '/woerter/endend-mit/os',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/os',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1231,
      'notes' => 'Verlinkt von JEDER der 1231 admittierten Wortseiten, die auf "OS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    282 => 
    array (
      'route_path' => '/woerter/endend-mit/ot',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ot',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 469,
      'notes' => 'Verlinkt von JEDER der 469 admittierten Wortseiten, die auf "OT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    283 => 
    array (
      'route_path' => '/woerter/endend-mit/ou',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ou',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "OU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    284 => 
    array (
      'route_path' => '/woerter/endend-mit/ov',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ov',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "OV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    285 => 
    array (
      'route_path' => '/woerter/endend-mit/ow',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ow',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 37,
      'notes' => 'Verlinkt von JEDER der 37 admittierten Wortseiten, die auf "OW" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    286 => 
    array (
      'route_path' => '/woerter/endend-mit/ox',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ox',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 46,
      'notes' => 'Verlinkt von JEDER der 46 admittierten Wortseiten, die auf "OX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    287 => 
    array (
      'route_path' => '/woerter/endend-mit/oy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/oy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12,
      'notes' => 'Verlinkt von JEDER der 12 admittierten Wortseiten, die auf "OY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    288 => 
    array (
      'route_path' => '/woerter/endend-mit/pa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 27,
      'notes' => 'Verlinkt von JEDER der 27 admittierten Wortseiten, die auf "PA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    289 => 
    array (
      'route_path' => '/woerter/endend-mit/pe',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pe',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 814,
      'notes' => 'Verlinkt von JEDER der 814 admittierten Wortseiten, die auf "PE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    290 => 
    array (
      'route_path' => '/woerter/endend-mit/pf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 413,
      'notes' => 'Verlinkt von JEDER der 413 admittierten Wortseiten, die auf "PF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    291 => 
    array (
      'route_path' => '/woerter/endend-mit/pg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "PG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    292 => 
    array (
      'route_path' => '/woerter/endend-mit/ph',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ph',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 114,
      'notes' => 'Verlinkt von JEDER der 114 admittierten Wortseiten, die auf "PH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    293 => 
    array (
      'route_path' => '/woerter/endend-mit/pi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 22,
      'notes' => 'Verlinkt von JEDER der 22 admittierten Wortseiten, die auf "PI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    294 => 
    array (
      'route_path' => '/woerter/endend-mit/pl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "PL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    295 => 
    array (
      'route_path' => '/woerter/endend-mit/po',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/po',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 34,
      'notes' => 'Verlinkt von JEDER der 34 admittierten Wortseiten, die auf "PO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    296 => 
    array (
      'route_path' => '/woerter/endend-mit/pp',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pp',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 217,
      'notes' => 'Verlinkt von JEDER der 217 admittierten Wortseiten, die auf "PP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    297 => 
    array (
      'route_path' => '/woerter/endend-mit/ps',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ps',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 457,
      'notes' => 'Verlinkt von JEDER der 457 admittierten Wortseiten, die auf "PS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    298 => 
    array (
      'route_path' => '/woerter/endend-mit/pt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 390,
      'notes' => 'Verlinkt von JEDER der 390 admittierten Wortseiten, die auf "PT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    299 => 
    array (
      'route_path' => '/woerter/endend-mit/pu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "PU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    300 => 
    array (
      'route_path' => '/woerter/endend-mit/py',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/py',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "PY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    301 => 
    array (
      'route_path' => '/woerter/endend-mit/pü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/pü',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "PÜ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    302 => 
    array (
      'route_path' => '/woerter/endend-mit/qf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/qf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "QF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    303 => 
    array (
      'route_path' => '/woerter/endend-mit/qi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/qi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "QI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    304 => 
    array (
      'route_path' => '/woerter/endend-mit/qs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/qs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "QS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    305 => 
    array (
      'route_path' => '/woerter/endend-mit/ra',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ra',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 207,
      'notes' => 'Verlinkt von JEDER der 207 admittierten Wortseiten, die auf "RA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    306 => 
    array (
      'route_path' => '/woerter/endend-mit/rb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 174,
      'notes' => 'Verlinkt von JEDER der 174 admittierten Wortseiten, die auf "RB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    307 => 
    array (
      'route_path' => '/woerter/endend-mit/rc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "RC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    308 => 
    array (
      'route_path' => '/woerter/endend-mit/rd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 269,
      'notes' => 'Verlinkt von JEDER der 269 admittierten Wortseiten, die auf "RD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    309 => 
    array (
      'route_path' => '/woerter/endend-mit/re',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/re',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12319,
      'notes' => 'Verlinkt von JEDER der 12319 admittierten Wortseiten, die auf "RE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    310 => 
    array (
      'route_path' => '/woerter/endend-mit/rf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 257,
      'notes' => 'Verlinkt von JEDER der 257 admittierten Wortseiten, die auf "RF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    311 => 
    array (
      'route_path' => '/woerter/endend-mit/rg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 154,
      'notes' => 'Verlinkt von JEDER der 154 admittierten Wortseiten, die auf "RG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    312 => 
    array (
      'route_path' => '/woerter/endend-mit/rh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "RH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    313 => 
    array (
      'route_path' => '/woerter/endend-mit/ri',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ri',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 87,
      'notes' => 'Verlinkt von JEDER der 87 admittierten Wortseiten, die auf "RI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    314 => 
    array (
      'route_path' => '/woerter/endend-mit/rk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 372,
      'notes' => 'Verlinkt von JEDER der 372 admittierten Wortseiten, die auf "RK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    315 => 
    array (
      'route_path' => '/woerter/endend-mit/rl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 139,
      'notes' => 'Verlinkt von JEDER der 139 admittierten Wortseiten, die auf "RL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    316 => 
    array (
      'route_path' => '/woerter/endend-mit/rm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 551,
      'notes' => 'Verlinkt von JEDER der 551 admittierten Wortseiten, die auf "RM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    317 => 
    array (
      'route_path' => '/woerter/endend-mit/rn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10763,
      'notes' => 'Verlinkt von JEDER der 10763 admittierten Wortseiten, die auf "RN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    318 => 
    array (
      'route_path' => '/woerter/endend-mit/ro',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ro',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 105,
      'notes' => 'Verlinkt von JEDER der 105 admittierten Wortseiten, die auf "RO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    319 => 
    array (
      'route_path' => '/woerter/endend-mit/rp',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rp',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 14,
      'notes' => 'Verlinkt von JEDER der 14 admittierten Wortseiten, die auf "RP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    320 => 
    array (
      'route_path' => '/woerter/endend-mit/rr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 153,
      'notes' => 'Verlinkt von JEDER der 153 admittierten Wortseiten, die auf "RR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    321 => 
    array (
      'route_path' => '/woerter/endend-mit/rs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8786,
      'notes' => 'Verlinkt von JEDER der 8786 admittierten Wortseiten, die auf "RS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    322 => 
    array (
      'route_path' => '/woerter/endend-mit/rt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5534,
      'notes' => 'Verlinkt von JEDER der 5534 admittierten Wortseiten, die auf "RT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    323 => 
    array (
      'route_path' => '/woerter/endend-mit/ru',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ru',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "RU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    324 => 
    array (
      'route_path' => '/woerter/endend-mit/rv',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rv',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12,
      'notes' => 'Verlinkt von JEDER der 12 admittierten Wortseiten, die auf "RV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    325 => 
    array (
      'route_path' => '/woerter/endend-mit/ry',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ry',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 33,
      'notes' => 'Verlinkt von JEDER der 33 admittierten Wortseiten, die auf "RY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    326 => 
    array (
      'route_path' => '/woerter/endend-mit/rz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 158,
      'notes' => 'Verlinkt von JEDER der 158 admittierten Wortseiten, die auf "RZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    327 => 
    array (
      'route_path' => '/woerter/endend-mit/rä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "RÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    328 => 
    array (
      'route_path' => '/woerter/endend-mit/rü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/rü',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "RÜ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    329 => 
    array (
      'route_path' => '/woerter/endend-mit/sa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 78,
      'notes' => 'Verlinkt von JEDER der 78 admittierten Wortseiten, die auf "SA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    330 => 
    array (
      'route_path' => '/woerter/endend-mit/sc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "SC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    331 => 
    array (
      'route_path' => '/woerter/endend-mit/sd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "SD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    332 => 
    array (
      'route_path' => '/woerter/endend-mit/se',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/se',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5732,
      'notes' => 'Verlinkt von JEDER der 5732 admittierten Wortseiten, die auf "SE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    333 => 
    array (
      'route_path' => '/woerter/endend-mit/sh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 28,
      'notes' => 'Verlinkt von JEDER der 28 admittierten Wortseiten, die auf "SH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    334 => 
    array (
      'route_path' => '/woerter/endend-mit/si',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/si',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 52,
      'notes' => 'Verlinkt von JEDER der 52 admittierten Wortseiten, die auf "SI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    335 => 
    array (
      'route_path' => '/woerter/endend-mit/sk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 35,
      'notes' => 'Verlinkt von JEDER der 35 admittierten Wortseiten, die auf "SK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    336 => 
    array (
      'route_path' => '/woerter/endend-mit/sl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "SL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    337 => 
    array (
      'route_path' => '/woerter/endend-mit/sm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "SM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    338 => 
    array (
      'route_path' => '/woerter/endend-mit/sn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "SN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    339 => 
    array (
      'route_path' => '/woerter/endend-mit/so',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/so',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 45,
      'notes' => 'Verlinkt von JEDER der 45 admittierten Wortseiten, die auf "SO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    340 => 
    array (
      'route_path' => '/woerter/endend-mit/sp',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sp',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "SP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    341 => 
    array (
      'route_path' => '/woerter/endend-mit/ss',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ss',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1616,
      'notes' => 'Verlinkt von JEDER der 1616 admittierten Wortseiten, die auf "SS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    342 => 
    array (
      'route_path' => '/woerter/endend-mit/st',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/st',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 35481,
      'notes' => 'Verlinkt von JEDER der 35481 admittierten Wortseiten, die auf "ST" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    343 => 
    array (
      'route_path' => '/woerter/endend-mit/su',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/su',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "SU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    344 => 
    array (
      'route_path' => '/woerter/endend-mit/sy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "SY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    345 => 
    array (
      'route_path' => '/woerter/endend-mit/sz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "SZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    346 => 
    array (
      'route_path' => '/woerter/endend-mit/sä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/sä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "SÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    347 => 
    array (
      'route_path' => '/woerter/endend-mit/ta',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ta',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 244,
      'notes' => 'Verlinkt von JEDER der 244 admittierten Wortseiten, die auf "TA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    348 => 
    array (
      'route_path' => '/woerter/endend-mit/te',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/te',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 27954,
      'notes' => 'Verlinkt von JEDER der 27954 admittierten Wortseiten, die auf "TE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    349 => 
    array (
      'route_path' => '/woerter/endend-mit/tg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "TG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    350 => 
    array (
      'route_path' => '/woerter/endend-mit/th',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/th',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 77,
      'notes' => 'Verlinkt von JEDER der 77 admittierten Wortseiten, die auf "TH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    351 => 
    array (
      'route_path' => '/woerter/endend-mit/ti',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ti',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 126,
      'notes' => 'Verlinkt von JEDER der 126 admittierten Wortseiten, die auf "TI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    352 => 
    array (
      'route_path' => '/woerter/endend-mit/tl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 15,
      'notes' => 'Verlinkt von JEDER der 15 admittierten Wortseiten, die auf "TL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    353 => 
    array (
      'route_path' => '/woerter/endend-mit/tm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "TM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    354 => 
    array (
      'route_path' => '/woerter/endend-mit/tn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "TN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    355 => 
    array (
      'route_path' => '/woerter/endend-mit/to',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/to',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 212,
      'notes' => 'Verlinkt von JEDER der 212 admittierten Wortseiten, die auf "TO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    356 => 
    array (
      'route_path' => '/woerter/endend-mit/tr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "TR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    357 => 
    array (
      'route_path' => '/woerter/endend-mit/ts',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ts',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5103,
      'notes' => 'Verlinkt von JEDER der 5103 admittierten Wortseiten, die auf "TS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    358 => 
    array (
      'route_path' => '/woerter/endend-mit/tt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 796,
      'notes' => 'Verlinkt von JEDER der 796 admittierten Wortseiten, die auf "TT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    359 => 
    array (
      'route_path' => '/woerter/endend-mit/tu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 50,
      'notes' => 'Verlinkt von JEDER der 50 admittierten Wortseiten, die auf "TU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    360 => 
    array (
      'route_path' => '/woerter/endend-mit/ty',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ty',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 38,
      'notes' => 'Verlinkt von JEDER der 38 admittierten Wortseiten, die auf "TY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    361 => 
    array (
      'route_path' => '/woerter/endend-mit/tz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 974,
      'notes' => 'Verlinkt von JEDER der 974 admittierten Wortseiten, die auf "TZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    362 => 
    array (
      'route_path' => '/woerter/endend-mit/tä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "TÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    363 => 
    array (
      'route_path' => '/woerter/endend-mit/tö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/tö',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "TÖ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    364 => 
    array (
      'route_path' => '/woerter/endend-mit/ua',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ua',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 17,
      'notes' => 'Verlinkt von JEDER der 17 admittierten Wortseiten, die auf "UA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    365 => 
    array (
      'route_path' => '/woerter/endend-mit/ub',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ub',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 224,
      'notes' => 'Verlinkt von JEDER der 224 admittierten Wortseiten, die auf "UB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    366 => 
    array (
      'route_path' => '/woerter/endend-mit/uc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/uc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "UC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    367 => 
    array (
      'route_path' => '/woerter/endend-mit/ud',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ud',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 41,
      'notes' => 'Verlinkt von JEDER der 41 admittierten Wortseiten, die auf "UD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    368 => 
    array (
      'route_path' => '/woerter/endend-mit/ue',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ue',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 437,
      'notes' => 'Verlinkt von JEDER der 437 admittierten Wortseiten, die auf "UE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    369 => 
    array (
      'route_path' => '/woerter/endend-mit/uf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/uf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 442,
      'notes' => 'Verlinkt von JEDER der 442 admittierten Wortseiten, die auf "UF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    370 => 
    array (
      'route_path' => '/woerter/endend-mit/ug',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ug',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 554,
      'notes' => 'Verlinkt von JEDER der 554 admittierten Wortseiten, die auf "UG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    371 => 
    array (
      'route_path' => '/woerter/endend-mit/uh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/uh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 75,
      'notes' => 'Verlinkt von JEDER der 75 admittierten Wortseiten, die auf "UH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    372 => 
    array (
      'route_path' => '/woerter/endend-mit/ui',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ui',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 13,
      'notes' => 'Verlinkt von JEDER der 13 admittierten Wortseiten, die auf "UI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    373 => 
    array (
      'route_path' => '/woerter/endend-mit/uk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/uk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 35,
      'notes' => 'Verlinkt von JEDER der 35 admittierten Wortseiten, die auf "UK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    374 => 
    array (
      'route_path' => '/woerter/endend-mit/ul',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ul',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 119,
      'notes' => 'Verlinkt von JEDER der 119 admittierten Wortseiten, die auf "UL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    375 => 
    array (
      'route_path' => '/woerter/endend-mit/um',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/um',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1435,
      'notes' => 'Verlinkt von JEDER der 1435 admittierten Wortseiten, die auf "UM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    376 => 
    array (
      'route_path' => '/woerter/endend-mit/un',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/un',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 302,
      'notes' => 'Verlinkt von JEDER der 302 admittierten Wortseiten, die auf "UN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    377 => 
    array (
      'route_path' => '/woerter/endend-mit/uo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/uo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "UO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    378 => 
    array (
      'route_path' => '/woerter/endend-mit/up',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/up',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 33,
      'notes' => 'Verlinkt von JEDER der 33 admittierten Wortseiten, die auf "UP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    379 => 
    array (
      'route_path' => '/woerter/endend-mit/ur',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ur',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 660,
      'notes' => 'Verlinkt von JEDER der 660 admittierten Wortseiten, die auf "UR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    380 => 
    array (
      'route_path' => '/woerter/endend-mit/us',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/us',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1747,
      'notes' => 'Verlinkt von JEDER der 1747 admittierten Wortseiten, die auf "US" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    381 => 
    array (
      'route_path' => '/woerter/endend-mit/ut',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ut',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 786,
      'notes' => 'Verlinkt von JEDER der 786 admittierten Wortseiten, die auf "UT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    382 => 
    array (
      'route_path' => '/woerter/endend-mit/uv',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/uv',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "UV" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    383 => 
    array (
      'route_path' => '/woerter/endend-mit/ux',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ux',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 17,
      'notes' => 'Verlinkt von JEDER der 17 admittierten Wortseiten, die auf "UX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    384 => 
    array (
      'route_path' => '/woerter/endend-mit/uz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/uz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 79,
      'notes' => 'Verlinkt von JEDER der 79 admittierten Wortseiten, die auf "UZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    385 => 
    array (
      'route_path' => '/woerter/endend-mit/va',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/va',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 53,
      'notes' => 'Verlinkt von JEDER der 53 admittierten Wortseiten, die auf "VA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    386 => 
    array (
      'route_path' => '/woerter/endend-mit/vd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/vd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "VD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    387 => 
    array (
      'route_path' => '/woerter/endend-mit/ve',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ve',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 554,
      'notes' => 'Verlinkt von JEDER der 554 admittierten Wortseiten, die auf "VE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    388 => 
    array (
      'route_path' => '/woerter/endend-mit/vi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/vi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "VI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    389 => 
    array (
      'route_path' => '/woerter/endend-mit/vo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/vo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "VO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    390 => 
    array (
      'route_path' => '/woerter/endend-mit/vs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/vs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 153,
      'notes' => 'Verlinkt von JEDER der 153 admittierten Wortseiten, die auf "VS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    391 => 
    array (
      'route_path' => '/woerter/endend-mit/vt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/vt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 25,
      'notes' => 'Verlinkt von JEDER der 25 admittierten Wortseiten, die auf "VT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    392 => 
    array (
      'route_path' => '/woerter/endend-mit/vy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/vy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "VY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    393 => 
    array (
      'route_path' => '/woerter/endend-mit/vä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/vä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "VÄ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    394 => 
    array (
      'route_path' => '/woerter/endend-mit/wa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 20,
      'notes' => 'Verlinkt von JEDER der 20 admittierten Wortseiten, die auf "WA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    395 => 
    array (
      'route_path' => '/woerter/endend-mit/we',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/we',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 41,
      'notes' => 'Verlinkt von JEDER der 41 admittierten Wortseiten, die auf "WE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    396 => 
    array (
      'route_path' => '/woerter/endend-mit/wi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "WI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    397 => 
    array (
      'route_path' => '/woerter/endend-mit/wk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "WK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    398 => 
    array (
      'route_path' => '/woerter/endend-mit/wl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "WL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    399 => 
    array (
      'route_path' => '/woerter/endend-mit/wn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 17,
      'notes' => 'Verlinkt von JEDER der 17 admittierten Wortseiten, die auf "WN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    400 => 
    array (
      'route_path' => '/woerter/endend-mit/wo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10,
      'notes' => 'Verlinkt von JEDER der 10 admittierten Wortseiten, die auf "WO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    401 => 
    array (
      'route_path' => '/woerter/endend-mit/ws',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ws',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 49,
      'notes' => 'Verlinkt von JEDER der 49 admittierten Wortseiten, die auf "WS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    402 => 
    array (
      'route_path' => '/woerter/endend-mit/wt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "WT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    403 => 
    array (
      'route_path' => '/woerter/endend-mit/wu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/wu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "WU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    404 => 
    array (
      'route_path' => '/woerter/endend-mit/ww',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ww',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "WW" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    405 => 
    array (
      'route_path' => '/woerter/endend-mit/xa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "XA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    406 => 
    array (
      'route_path' => '/woerter/endend-mit/xe',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xe',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 160,
      'notes' => 'Verlinkt von JEDER der 160 admittierten Wortseiten, die auf "XE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    407 => 
    array (
      'route_path' => '/woerter/endend-mit/xi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "XI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    408 => 
    array (
      'route_path' => '/woerter/endend-mit/xl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "XL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    409 => 
    array (
      'route_path' => '/woerter/endend-mit/xn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "XN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    410 => 
    array (
      'route_path' => '/woerter/endend-mit/xo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "XO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    411 => 
    array (
      'route_path' => '/woerter/endend-mit/xs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "XS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    412 => 
    array (
      'route_path' => '/woerter/endend-mit/xt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 118,
      'notes' => 'Verlinkt von JEDER der 118 admittierten Wortseiten, die auf "XT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    413 => 
    array (
      'route_path' => '/woerter/endend-mit/xy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/xy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "XY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    414 => 
    array (
      'route_path' => '/woerter/endend-mit/ya',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ya',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 18,
      'notes' => 'Verlinkt von JEDER der 18 admittierten Wortseiten, die auf "YA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    415 => 
    array (
      'route_path' => '/woerter/endend-mit/yd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 30,
      'notes' => 'Verlinkt von JEDER der 30 admittierten Wortseiten, die auf "YD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    416 => 
    array (
      'route_path' => '/woerter/endend-mit/ye',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ye',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 16,
      'notes' => 'Verlinkt von JEDER der 16 admittierten Wortseiten, die auf "YE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    417 => 
    array (
      'route_path' => '/woerter/endend-mit/yk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "YK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    418 => 
    array (
      'route_path' => '/woerter/endend-mit/yl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 26,
      'notes' => 'Verlinkt von JEDER der 26 admittierten Wortseiten, die auf "YL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    419 => 
    array (
      'route_path' => '/woerter/endend-mit/ym',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ym',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 57,
      'notes' => 'Verlinkt von JEDER der 57 admittierten Wortseiten, die auf "YM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    420 => 
    array (
      'route_path' => '/woerter/endend-mit/yn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "YN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    421 => 
    array (
      'route_path' => '/woerter/endend-mit/yo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "YO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    422 => 
    array (
      'route_path' => '/woerter/endend-mit/yp',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yp',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 64,
      'notes' => 'Verlinkt von JEDER der 64 admittierten Wortseiten, die auf "YP" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    423 => 
    array (
      'route_path' => '/woerter/endend-mit/yr',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yr',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10,
      'notes' => 'Verlinkt von JEDER der 10 admittierten Wortseiten, die auf "YR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    424 => 
    array (
      'route_path' => '/woerter/endend-mit/ys',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ys',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 224,
      'notes' => 'Verlinkt von JEDER der 224 admittierten Wortseiten, die auf "YS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    425 => 
    array (
      'route_path' => '/woerter/endend-mit/yt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 53,
      'notes' => 'Verlinkt von JEDER der 53 admittierten Wortseiten, die auf "YT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    426 => 
    array (
      'route_path' => '/woerter/endend-mit/yu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "YU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    427 => 
    array (
      'route_path' => '/woerter/endend-mit/yx',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/yx',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "YX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    428 => 
    array (
      'route_path' => '/woerter/endend-mit/za',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/za',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 25,
      'notes' => 'Verlinkt von JEDER der 25 admittierten Wortseiten, die auf "ZA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    429 => 
    array (
      'route_path' => '/woerter/endend-mit/ze',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ze',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1980,
      'notes' => 'Verlinkt von JEDER der 1980 admittierten Wortseiten, die auf "ZE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    430 => 
    array (
      'route_path' => '/woerter/endend-mit/zg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "ZG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    431 => 
    array (
      'route_path' => '/woerter/endend-mit/zi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 35,
      'notes' => 'Verlinkt von JEDER der 35 admittierten Wortseiten, die auf "ZI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    432 => 
    array (
      'route_path' => '/woerter/endend-mit/zl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "ZL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    433 => 
    array (
      'route_path' => '/woerter/endend-mit/zn',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zn',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "ZN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    434 => 
    array (
      'route_path' => '/woerter/endend-mit/zo',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zo',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 15,
      'notes' => 'Verlinkt von JEDER der 15 admittierten Wortseiten, die auf "ZO" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    435 => 
    array (
      'route_path' => '/woerter/endend-mit/zs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "ZS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    436 => 
    array (
      'route_path' => '/woerter/endend-mit/zt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 874,
      'notes' => 'Verlinkt von JEDER der 874 admittierten Wortseiten, die auf "ZT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    437 => 
    array (
      'route_path' => '/woerter/endend-mit/zu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 16,
      'notes' => 'Verlinkt von JEDER der 16 admittierten Wortseiten, die auf "ZU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    438 => 
    array (
      'route_path' => '/woerter/endend-mit/zy',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zy',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3,
      'notes' => 'Verlinkt von JEDER der 3 admittierten Wortseiten, die auf "ZY" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    439 => 
    array (
      'route_path' => '/woerter/endend-mit/zz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/zz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "ZZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    440 => 
    array (
      'route_path' => '/woerter/endend-mit/äa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 6,
      'notes' => 'Verlinkt von JEDER der 6 admittierten Wortseiten, die auf "ÄA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    441 => 
    array (
      'route_path' => '/woerter/endend-mit/äb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 51,
      'notes' => 'Verlinkt von JEDER der 51 admittierten Wortseiten, die auf "ÄB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    442 => 
    array (
      'route_path' => '/woerter/endend-mit/äe',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äe',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "ÄE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    443 => 
    array (
      'route_path' => '/woerter/endend-mit/äf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12,
      'notes' => 'Verlinkt von JEDER der 12 admittierten Wortseiten, die auf "ÄF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    444 => 
    array (
      'route_path' => '/woerter/endend-mit/äg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 65,
      'notes' => 'Verlinkt von JEDER der 65 admittierten Wortseiten, die auf "ÄG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    445 => 
    array (
      'route_path' => '/woerter/endend-mit/äh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 71,
      'notes' => 'Verlinkt von JEDER der 71 admittierten Wortseiten, die auf "ÄH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    446 => 
    array (
      'route_path' => '/woerter/endend-mit/äi',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äi',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "ÄI" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    447 => 
    array (
      'route_path' => '/woerter/endend-mit/äk',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äk',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 19,
      'notes' => 'Verlinkt von JEDER der 19 admittierten Wortseiten, die auf "ÄK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    448 => 
    array (
      'route_path' => '/woerter/endend-mit/äl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7,
      'notes' => 'Verlinkt von JEDER der 7 admittierten Wortseiten, die auf "ÄL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    449 => 
    array (
      'route_path' => '/woerter/endend-mit/äm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 104,
      'notes' => 'Verlinkt von JEDER der 104 admittierten Wortseiten, die auf "ÄM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    450 => 
    array (
      'route_path' => '/woerter/endend-mit/än',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/än',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 39,
      'notes' => 'Verlinkt von JEDER der 39 admittierten Wortseiten, die auf "ÄN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    451 => 
    array (
      'route_path' => '/woerter/endend-mit/är',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/är',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 215,
      'notes' => 'Verlinkt von JEDER der 215 admittierten Wortseiten, die auf "ÄR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    452 => 
    array (
      'route_path' => '/woerter/endend-mit/äs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 38,
      'notes' => 'Verlinkt von JEDER der 38 admittierten Wortseiten, die auf "ÄS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    453 => 
    array (
      'route_path' => '/woerter/endend-mit/ät',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ät',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 650,
      'notes' => 'Verlinkt von JEDER der 650 admittierten Wortseiten, die auf "ÄT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    454 => 
    array (
      'route_path' => '/woerter/endend-mit/äu',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äu',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 13,
      'notes' => 'Verlinkt von JEDER der 13 admittierten Wortseiten, die auf "ÄU" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    455 => 
    array (
      'route_path' => '/woerter/endend-mit/äz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/äz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Verlinkt von JEDER der 2 admittierten Wortseiten, die auf "ÄZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    456 => 
    array (
      'route_path' => '/woerter/endend-mit/öb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 47,
      'notes' => 'Verlinkt von JEDER der 47 admittierten Wortseiten, die auf "ÖB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    457 => 
    array (
      'route_path' => '/woerter/endend-mit/öc',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öc',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "ÖC" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    458 => 
    array (
      'route_path' => '/woerter/endend-mit/öd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 12,
      'notes' => 'Verlinkt von JEDER der 12 admittierten Wortseiten, die auf "ÖD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    459 => 
    array (
      'route_path' => '/woerter/endend-mit/öe',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öe',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8,
      'notes' => 'Verlinkt von JEDER der 8 admittierten Wortseiten, die auf "ÖE" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    460 => 
    array (
      'route_path' => '/woerter/endend-mit/ög',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ög',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 155,
      'notes' => 'Verlinkt von JEDER der 155 admittierten Wortseiten, die auf "ÖG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    461 => 
    array (
      'route_path' => '/woerter/endend-mit/öh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "ÖH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    462 => 
    array (
      'route_path' => '/woerter/endend-mit/ök',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ök',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 5,
      'notes' => 'Verlinkt von JEDER der 5 admittierten Wortseiten, die auf "ÖK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    463 => 
    array (
      'route_path' => '/woerter/endend-mit/öl',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öl',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 115,
      'notes' => 'Verlinkt von JEDER der 115 admittierten Wortseiten, die auf "ÖL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    464 => 
    array (
      'route_path' => '/woerter/endend-mit/öm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "ÖM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    465 => 
    array (
      'route_path' => '/woerter/endend-mit/ön',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ön',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 22,
      'notes' => 'Verlinkt von JEDER der 22 admittierten Wortseiten, die auf "ÖN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    466 => 
    array (
      'route_path' => '/woerter/endend-mit/ör',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ör',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 81,
      'notes' => 'Verlinkt von JEDER der 81 admittierten Wortseiten, die auf "ÖR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    467 => 
    array (
      'route_path' => '/woerter/endend-mit/ös',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ös',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 161,
      'notes' => 'Verlinkt von JEDER der 161 admittierten Wortseiten, die auf "ÖS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    468 => 
    array (
      'route_path' => '/woerter/endend-mit/öt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 25,
      'notes' => 'Verlinkt von JEDER der 25 admittierten Wortseiten, die auf "ÖT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    469 => 
    array (
      'route_path' => '/woerter/endend-mit/öz',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/öz',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "ÖZ" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    470 => 
    array (
      'route_path' => '/woerter/endend-mit/üa',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üa',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1,
      'notes' => 'Verlinkt von JEDER der 1 admittierten Wortseiten, die auf "ÜA" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    471 => 
    array (
      'route_path' => '/woerter/endend-mit/üb',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üb',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 39,
      'notes' => 'Verlinkt von JEDER der 39 admittierten Wortseiten, die auf "ÜB" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    472 => 
    array (
      'route_path' => '/woerter/endend-mit/üd',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üd',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 25,
      'notes' => 'Verlinkt von JEDER der 25 admittierten Wortseiten, die auf "ÜD" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    473 => 
    array (
      'route_path' => '/woerter/endend-mit/üf',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üf',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 17,
      'notes' => 'Verlinkt von JEDER der 17 admittierten Wortseiten, die auf "ÜF" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    474 => 
    array (
      'route_path' => '/woerter/endend-mit/üg',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üg',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 94,
      'notes' => 'Verlinkt von JEDER der 94 admittierten Wortseiten, die auf "ÜG" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    475 => 
    array (
      'route_path' => '/woerter/endend-mit/üh',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üh',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 41,
      'notes' => 'Verlinkt von JEDER der 41 admittierten Wortseiten, die auf "ÜH" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    476 => 
    array (
      'route_path' => '/woerter/endend-mit/ük',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ük',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 9,
      'notes' => 'Verlinkt von JEDER der 9 admittierten Wortseiten, die auf "ÜK" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    477 => 
    array (
      'route_path' => '/woerter/endend-mit/ül',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ül',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 27,
      'notes' => 'Verlinkt von JEDER der 27 admittierten Wortseiten, die auf "ÜL" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    478 => 
    array (
      'route_path' => '/woerter/endend-mit/üm',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üm',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 11,
      'notes' => 'Verlinkt von JEDER der 11 admittierten Wortseiten, die auf "ÜM" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    479 => 
    array (
      'route_path' => '/woerter/endend-mit/ün',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ün',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 49,
      'notes' => 'Verlinkt von JEDER der 49 admittierten Wortseiten, die auf "ÜN" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    480 => 
    array (
      'route_path' => '/woerter/endend-mit/ür',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ür',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 111,
      'notes' => 'Verlinkt von JEDER der 111 admittierten Wortseiten, die auf "ÜR" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    481 => 
    array (
      'route_path' => '/woerter/endend-mit/üs',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üs',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 17,
      'notes' => 'Verlinkt von JEDER der 17 admittierten Wortseiten, die auf "ÜS" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    482 => 
    array (
      'route_path' => '/woerter/endend-mit/üt',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üt',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 23,
      'notes' => 'Verlinkt von JEDER der 23 admittierten Wortseiten, die auf "ÜT" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    483 => 
    array (
      'route_path' => '/woerter/endend-mit/üx',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/üx',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 4,
      'notes' => 'Verlinkt von JEDER der 4 admittierten Wortseiten, die auf "ÜX" enden, via App\\Search\\RelationsFinder::relatedSearches() (Kategorie endsWith, IMMER exakt 2 Buchstaben, mb_substr($word, -min(2,$length)) mit MIN_LENGTH=2, nie 1 Buchstabe) -- gemessen (nicht angenommen), 0 verwaiste Seite in dieser Familie. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
  ),
);
