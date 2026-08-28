<?php

declare(strict_types=1);

/**
 * Premier palier de rollout SEO du site allemand (docs/DECISIONS.md D-DE-013) : page d'accueil
 * ('/') et les 14 listes par longueur (/woerter/{N}-buchstaben, 2 à 15 lettres). Appliqué via :
 *
 *     php scripts/apply_seo_batch.php scripts/seo-batches/home-and-length-2026-08-29.php --force --prune
 *
 * La famille word_admitted (590 856 lignes potentielles) N'EST PAS incluse dans ce lot --
 * bloquée par deux problèmes distincts, aucun des deux tranché par l'agent seo-registry
 * (rapport de tâche pour le détail complet des deux) :
 *   1. Le gabarit <title> de app/View/word.php dépasse 60 caractères pour 100% des 590 856
 *      mots admis (mesure directe, 379 079 dépassent même 70 caractères) -- hors périmètre de
 *      cet agent (app/View/), signalé pour routage vers l'agent frontend.
 *   2. Contrainte de rôle dure ("never propose indexing an entire word family at once without
 *      discussing batch size first") -- aucune décision de dimensionnement n'a encore été prise
 *      par le propriétaire du produit pour cette famille (contrairement à D-017 côté dépôt
 *      français, décision explicite documentée). scripts/apply_word_admitted_rollout.php existe,
 *      prêt et testé (--dry-run), mais refuse par construction d'écrire au-delà d'un plafond de
 *      sécurité sans --confirm-full-rollout.
 *
 * CORRECTION (2026-08-29, revue indépendante sur le lot équivalent du dépôt espagnol cousin,
 * NO GO) : la version initiale de ce lot (jamais appliquée) reprenait la prémisse "seules 2
 * longueurs sur 14 ont un lien interne réel" (home.php ne lie explicitement que 7 et 9
 * lettres) -- prémisse FAUSSE, vérifiée directement dans App\Search\RelationsFinder::
 * relatedSearches() : CHAQUE fiche de mot admis émet inconditionnellement un lien vers
 * /woerter/{sa-longueur}-buchstaben (première entrée ajoutée, donc jamais évincée par le
 * plafond MAX_RELATED_SEARCHES=12) -- vérifié en direct sur un vrai serveur PHP (php -S) avec
 * un mot réel de chaque longueur 2 à 15 (voir le rapport de tâche pour le détail complet des 14
 * vérifications HTTP). Les 14 longueurs ont donc, dès aujourd'hui, un maillage interne réel et
 * démontré -- au minimum depuis le mot alphabétiquement premier de chaque longueur, en pratique
 * depuis TOUS les mots admis de cette longueur (78 au minimum pour 2 lettres, jusqu'à 83 825
 * pour 9 lettres). Ce lot ouvre donc les 14 longueurs, pas seulement 2.
 *
 * '/woerter' (hub de navigation, App\Search\ExploreHub) N'EST PAS incluse dans ce lot,
 * contrairement au lot équivalent du dépôt espagnol cousin (qui l'avait incluse malgré le même
 * défaut) : vérifié en direct sur un vrai serveur PHP -- `list_counts` est vide sur ce dépôt
 * (schema.sql, même décision D-DE-équivalente que côté espagnol), les trois sections de grille
 * ("Nach Länge"/"Beginnend Mit"/"Endend Mit") rendent donc <div class="related-links"></div>
 * strictement VIDE (confirmé par curl direct, pas supposé) -- aucun garde d'état vide
 * équivalent à celui de app/View/word-list.php. Seuls les deux formulaires (recherche
 * "Enthält", vérification d'un mot) et le texte d'introduction constituent un contenu réel ;
 * jugé insuffisant pour indexer une page dont les trois quarts du contenu annoncé (3 sections
 * sur 4) sont vides. Reste noindex,follow jusqu'à ce que list_counts soit peuplée (signalé à
 * l'agent data-engine) ou qu'un autre maillage réel soit construit.
 *
 * result_count pour word_list_length = nombre RÉEL de lignes de storage/dictionary_de.sqlite
 * pour cette longueur -- TOUS les mots de la table sont is_admitted=1 par construction dans ce
 * premier palier de données (CLAUDE.md, "Modèle À Statuts", deux statuts peuplés), vérifié par
 * requête directe le 2026-08-29 (EXPLAIN QUERY PLAN : SEARCH terms USING COVERING INDEX
 * idx_terms_length_normalized, jamais un SCAN ; 14/14 requêtes sous 9 ms, largement sous le
 * budget TTFB p95 < 250 ms).
 */
return [
    'batch_id' => 'home-and-length-tier1-2026-08-29',
    'added_at' => '2026-08-29',
    'rows' => [
        [
            'route_path' => '/',
            'family' => 'home',
            'robots' => 'index,follow',
            'canonical_path' => '/',
            'sitemap_fragment' => 'core-0001',
            'result_count' => null,
            'notes' => 'Startseite, Ziel jedes "WORD CHECKR"-Logolinks im Header und jedes Breadcrumbs auf jeder Wort- und Listenseite (Maillage = 100% der Seiten der Website). Verlinkt zum Hub /woerter ("Alle Wörter Durchsuchen"), zu den Formularen Prüfen/Wortsuche, sowie direkt zu /woerter/7-buchstaben, /woerter/beginnend-mit/a, /woerter/endend-mit/s (Kontext-Links) und /woerter/9-buchstaben (Hilfetext).',
        ],
        [
            'route_path' => '/woerter/2-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/2-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 78,
            'notes' => 'Verlinkt von JEDER der 78 admittierten 2-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() (erster Eintrag, immer vorhanden) -- live verifiziert (Beispiel: /wort/aa, /wort/qi). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/3-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/3-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 745,
            'notes' => 'Verlinkt von JEDER der 745 admittierten 3-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aak). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/4-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/4-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 3462,
            'notes' => 'Verlinkt von JEDER der 3462 admittierten 4-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aake). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/5-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/5-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 9558,
            'notes' => 'Verlinkt von JEDER der 9558 admittierten 5-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aaden). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/6-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/6-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 20720,
            'notes' => 'Verlinkt von JEDER der 20720 admittierten 6-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aalend). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/7-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/7-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 36404,
            'notes' => 'Verlinkt von der Startseite (app/View/home.php, $contextLinkSpecs, statischer Link "Wörter Mit 7 Buchstaben") UND von JEDER der 36404 admittierten 7-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/8-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/8-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 56985,
            'notes' => 'Verlinkt von JEDER der 56985 admittierten 8-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aachener). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/9-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/9-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 83825,
            'notes' => 'Verlinkt von der Startseite (app/View/home.php, Hilfetext, "$phraseLink(\'9-buchstaben\', ...)") UND von JEDER der 83825 admittierten 9-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/10-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/10-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 83477,
            'notes' => 'Verlinkt von JEDER der 83477 admittierten 10-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aachenerin). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/11-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/11-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 80639,
            'notes' => 'Verlinkt von JEDER der 80639 admittierten 11-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aakerbeeren). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/12-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/12-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 71226,
            'notes' => 'Verlinkt von JEDER der 71226 admittierten 12-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aalbestandes). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/13-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/13-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 58926,
            'notes' => 'Verlinkt von JEDER der 58926 admittierten 13-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aachenerinnen). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/14-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/14-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 48208,
            'notes' => 'Verlinkt von JEDER der 48208 admittierten 14-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aalbeerstrauch). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
        [
            'route_path' => '/woerter/15-buchstaben',
            'family' => 'word_list_length',
            'robots' => 'index,follow',
            'canonical_path' => '/woerter/15-buchstaben',
            'sitemap_fragment' => 'letters-0001',
            'result_count' => 36603,
            'notes' => 'Verlinkt von JEDER der 36603 admittierten 15-Buchstaben-Wortseiten via App\\Search\\RelationsFinder::relatedSearches() -- live verifiziert (Beispiel: /wort/aalbeerstrauche). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
        ],
    ],
];
