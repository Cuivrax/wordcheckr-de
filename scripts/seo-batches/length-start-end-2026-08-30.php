<?php

declare(strict_types=1);

/**
 * Palier "longueur+beginnend-mit"/"longueur+endend-mit" combine (docs/DECISIONS.md D-DE-019) :
 * les 754 combinaisons reelles length_start/length_end de `list_counts` (D-DE-018, peuplee ce
 * meme jour), MOINS 10 exclusions explicites (1 doublon de contenu + 9 <title> >= 60 caracteres,
 * voir ci-dessous pour le detail des deux raisons distinctes) = 744 pages 'index,follow' + 10
 * pages 'noindex,follow' explicites. Applique via :
 *
 *     php scripts/apply_seo_batch.php scripts/seo-batches/length-start-end-2026-08-30.php --force
 *
 * Debloque par D-DE-018 (list_counts peuplee, 0 -> 826 lignes) : App\Search\LengthLinksBuilder
 * construit desormais un maillage entrant REEL depuis les 14 pages /woerter/{N}-buchstaben deja
 * indexees (D-DE-013) vers chacune de ces 754 pages (byStart/byEnd), verifie en direct sur un
 * vrai serveur PHP (php -S) -- pas seulement suppose depuis le code. CHAQUE page beneficie EN
 * PLUS d'un second lien entrant independant : les mots admis de cette longueur+lettre relient
 * eux-memes leur page de longueur (App\Search\RelationsFinder::relatedSearches(), categorie
 * "length", toujours presente) -- le maillage n'est jamais a sens unique.
 *
 * DOUBLON DE CONTENU TROUVE ET EXCLU (2026-08-30, balayage systematique des 754 combinaisons
 * contre les familles deja indexees `start`/`end`, comparaison d'ensembles de mots EXACTS, pas
 * seulement de compteurs) : /woerter/7-buchstaben/endend-mit/q listerait EXACTEMENT le meme mot
 * (INUPIAQ, seul mot admis se terminant par "Q" dans toute la base) que /woerter/endend-mit/q,
 * deja indexee et gagnante -- reste noindex,follow, canonical_path pointe vers la page gagnante
 * (R3 n'interdit un canonical_path different de route_path QUE pour 'index,follow', jamais pour
 * 'noindex,follow' -- ce mecanisme sert exactement ce cas). 0 AUTRE doublon trouve : verifie a la
 * fois contre `start`/`end` (meme lettre, meme compte total) ET contre `length` (meme longueur,
 * meme compte total) -- 0 correspondance dans les deux autres directions.
 *
 * <title> >= 60 CARACTERES TROUVE ET EXCLU, 9 PAGES (2026-08-30, verification HTTP reelle sur
 * les 25 pages a 1 resultat de ce lot -- PAS suppose) : app/View/word-list.php enrichit <title>
 * pour toute page a 1 seul resultat en prefixant le mot lui-meme (audit D-031 herite, gabarit
 * existant, PAS une regression introduite ici, app/View/ hors perimetre seo-registry -- signale
 * pour un futur ajustement de gabarit plutot que modifie silencieusement). Pour 9 des 16 pages
 * endend-mit (length_end) a 1 resultat, le mot unique est lui-meme long (jusqu'a 14 lettres) ET
 * la longueur+lettre pousse le total au-dela de 60 caracteres (mesure exacte : 60 a 69 caracteres,
 * ex. "SILBERMETALLIC - Wörter Mit 14 Buchstaben Mit C Am Ende | WORD CHECKR" = 69). Ces 9 pages
 * restent noindex,follow, canonical_path = elles-memes (PAS un doublon de contenu comme
 * l'exclusion ci-dessus -- une page qui existe, dont le contenu est correct, juste pas prete pour
 * l'indexation avec ce gabarit de <title>). AUCUNE page length_start (beginnend-mit) n'est
 * concernee (titres sans mention de longueur a 2 chiffres, prefixe toujours 1 lettre courte,
 * jamais au-dela de 54 caracteres meme a 1 resultat).
 *
 * result_count = compte REEL de `list_counts` (verite terrain, memes requetes que
 * App\Search\LengthLinksBuilder au runtime), PAS le compte tronque affiche a l'ecran quand il
 * depasse WordListSolver::ROW_EXAMINATION_CEILING = 10 000 -- meme convention deja utilisee pour
 * endend-mit/en (D-DE-017, result_count=108008 alors que la page affiche "au moins 10000"). 20
 * des 353 pages endend-mit (length_end) restantes depassent ce plafond et sont donc tronquees a
 * l'affichage (meme precedent deja accepte, D-DE-017 : 12/455 pour la famille endend-mit a 2
 * lettres) -- AUCUNE page length_start (beginnend-mit) n'est jamais tronquee (regime EXACT,
 * App\Search\WordListSolver::solveExact(), aucun plafond).
 *
 * Performance mesuree (pas supposee), balayage COMPLET des 754 combinaisons (pas un echantillon),
 * y compris les longueurs extremes 2 et 15 :
 *   0/754 au-dessus du budget TTFB de 250 ms -- min 1,143 ms, p50 2,440 ms, p95 106,450 ms,
 *   p99 178,050 ms, max 207,579 ms (10-buchstaben/endend-mit, panier le plus large plafonne).
 *   length_start (beginnend-mit) : EXPLAIN QUERY PLAN toujours "SEARCH terms USING COVERING
 *     INDEX idx_terms_length_normalized (length=? AND normalized>? AND normalized<?)", jamais
 *     un SCAN -- regime EXACT, requete identique a celle deja utilisee pour
 *     /woerter/{N}-buchstaben (D-DE-013).
 *   length_end (endend-mit) : EXPLAIN QUERY PLAN toujours "SEARCH terms USING COVERING INDEX
 *     idx_terms_length_reversed (length=? AND reversed>? AND reversed<?)", jamais un SCAN --
 *     regime BORNE (sous-requete plafonnee, meme construction deja acceptee pour endend-mit a 2
 *     lettres, D-DE-017).
 *
 * 0 page a 0 resultat (R5, impossible par construction -- chaque ligne de list_counts vient d'au
 * moins un mot reel). 25 pages a exactement 1 resultat au total, dont 10 exclues ci-dessus (1
 * doublon + 9 <title> trop long) et 15 GARDEES 'index,follow' -- 1 resultat n'est jamais a lui
 * seul un critere d'exclusion (docs/05), seules les DEUX raisons explicites ci-dessus le sont ici.
 *
 * Sitemaps : starts-0002.xml (401 URL, extension de word_list_commencant, meme famille que
 * starts-0001.xml, aucune nouvelle classification -- meme precedent que le depot francais cousin
 * pour ses propres extensions starts-0002/ends-0002 multi-lettres) et ends-0002.xml (343 URL,
 * extension de word_list_terminant, 353 combinaisons length_end reelles moins 10 exclusions).
 * App\Seo\Family inchangee (aucune nouvelle constante). scripts/apply_seo_batch.php (R4b etendu
 * pour accepter un prefixe de longueur optionnel devant beginnend-mit/endend-mit) -- voir le
 * rapport de tache pour le diff complet.
 */
return
array (
  'batch_id' => 'length-start-end-2026-08-30',
  'added_at' => '2026-08-30',
  'rows' => 
  array (
    0 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7 admittierten 2-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    1 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 2-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    2 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5 admittierten 2-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    3 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 2-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    4 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5 admittierten 2-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    5 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 11,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 11 admittierten 2-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    6 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    7 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 2-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    8 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 2-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    9 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6 admittierten 2-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    10 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 2-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    11 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 2-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    12 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    13 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7 admittierten 2-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    14 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    15 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 2-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    16 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    17 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    18 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/ö',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 2-Buchstaben-Wortseiten, die auf "Ö" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    19 => 
    array (
      'route_path' => '/woerter/2-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/endend-mit/ü',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die auf "Ü" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    20 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 32,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 32 admittierten 3-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    21 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 31,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 31 admittierten 3-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    22 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6 admittierten 3-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    23 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 35,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 35 admittierten 3-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    24 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 43,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 43 admittierten 3-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    25 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 16,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 16 admittierten 3-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    26 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 49,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 49 admittierten 3-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    27 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 33,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 33 admittierten 3-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    28 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 38,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 38 admittierten 3-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    29 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/j',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/j',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1 admittierten 3-Buchstaben-Wortseiten, die auf "J" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    30 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 26,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 26 admittierten 3-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    31 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 23,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 23 admittierten 3-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    32 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 33,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 33 admittierten 3-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    33 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 52,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 52 admittierten 3-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    34 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 24,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 24 admittierten 3-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    35 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 24,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 24 admittierten 3-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    36 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 57,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 57 admittierten 3-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    37 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 79,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 79 admittierten 3-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    38 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 71,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 71 admittierten 3-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    39 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 27,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 27 admittierten 3-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    40 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 3-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    41 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 3-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    42 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 23,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 23 admittierten 3-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    43 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5 admittierten 3-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    44 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 8,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 8 admittierten 3-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    45 => 
    array (
      'route_path' => '/woerter/3-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1 admittierten 3-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    46 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 141,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 141 admittierten 4-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    47 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 63,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 63 admittierten 4-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    48 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6 admittierten 4-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    49 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 118,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 118 admittierten 4-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    50 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 572,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 572 admittierten 4-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    51 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 96,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 96 admittierten 4-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    52 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 118,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 118 admittierten 4-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    53 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 80,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 80 admittierten 4-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    54 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 127,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 127 admittierten 4-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    55 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 157,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 157 admittierten 4-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    56 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 164,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 164 admittierten 4-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    57 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 122,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 122 admittierten 4-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    58 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 238,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 238 admittierten 4-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    59 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 101,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 101 admittierten 4-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    60 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 87,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 87 admittierten 4-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    61 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 140,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 140 admittierten 4-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    62 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 472,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 472 admittierten 4-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    63 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 452,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 452 admittierten 4-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    64 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 63,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 63 admittierten 4-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    65 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7 admittierten 4-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    66 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6 admittierten 4-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    67 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 12,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 12 admittierten 4-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    68 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 14,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 14 admittierten 4-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    69 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 96,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 96 admittierten 4-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    70 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5 admittierten 4-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    71 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/ö',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1 admittierten 4-Buchstaben-Wortseiten, die auf "Ö" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    72 => 
    array (
      'route_path' => '/woerter/4-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/endend-mit/ü',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 4-Buchstaben-Wortseiten, die auf "Ü" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    73 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 273,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 273 admittierten 5-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    74 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 106,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 106 admittierten 5-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    75 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 9,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 9 admittierten 5-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    76 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 105,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 105 admittierten 5-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    77 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2456,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2456 admittierten 5-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    78 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 76,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 76 admittierten 5-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    79 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 213,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 213 admittierten 5-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    80 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 184,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 184 admittierten 5-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    81 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 214,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 214 admittierten 5-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    82 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 156,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 156 admittierten 5-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    83 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 375,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 375 admittierten 5-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    84 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 196,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 196 admittierten 5-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    85 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 944,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 944 admittierten 5-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    86 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 170,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 170 admittierten 5-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    87 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 52,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 52 admittierten 5-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    88 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 462,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 462 admittierten 5-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    89 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1640,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1640 admittierten 5-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    90 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1674,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1674 admittierten 5-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    91 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 67,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 67 admittierten 5-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    92 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 9,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 9 admittierten 5-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    93 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 5-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    94 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 32,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 32 admittierten 5-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    95 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 64,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 64 admittierten 5-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    96 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 74,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 74 admittierten 5-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    97 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 5-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    98 => 
    array (
      'route_path' => '/woerter/5-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/endend-mit/ö',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1 admittierten 5-Buchstaben-Wortseiten, die auf "Ö" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    99 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 281,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 281 admittierten 6-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    100 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 175,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 175 admittierten 6-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    101 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 9,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 9 admittierten 6-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    102 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 427,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 427 admittierten 6-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    103 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3969,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3969 admittierten 6-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    104 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 146,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 146 admittierten 6-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    105 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 748,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 748 admittierten 6-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    106 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 295,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 295 admittierten 6-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    107 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 213,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 213 admittierten 6-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    108 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/j',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/j',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    109 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 214,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 214 admittierten 6-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    110 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 874,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 874 admittierten 6-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    111 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 498,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 498 admittierten 6-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    112 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3391,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3391 admittierten 6-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    113 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 130,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 130 admittierten 6-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    114 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 47,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 47 admittierten 6-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    115 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1492,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1492 admittierten 6-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    116 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3407,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3407 admittierten 6-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    117 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4080,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4080 admittierten 6-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    118 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 83,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 83 admittierten 6-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    119 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 16,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 16 admittierten 6-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    120 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6 admittierten 6-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    121 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 45,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 45 admittierten 6-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    122 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 46,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 46 admittierten 6-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    123 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 118,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 118 admittierten 6-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    124 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 8,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 8 admittierten 6-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    125 => 
    array (
      'route_path' => '/woerter/6-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/6-buchstaben/endend-mit/ö',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    126 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 319,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 319 admittierten 7-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    127 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 210,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 210 admittierten 7-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    128 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7 admittierten 7-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    129 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1418,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1418 admittierten 7-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    130 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7018,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7018 admittierten 7-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    131 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 299,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 299 admittierten 7-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    132 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1164,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1164 admittierten 7-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    133 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 551,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 551 admittierten 7-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    134 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 241,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 241 admittierten 7-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    135 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 405,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 405 admittierten 7-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    136 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 921,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 921 admittierten 7-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    137 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 982,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 982 admittierten 7-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    138 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5855,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5855 admittierten 7-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    139 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 123,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 123 admittierten 7-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    140 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 98,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 98 admittierten 7-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    141 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/q',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/endend-mit/q',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'DOUBLON DE CONTENU CONFIRME : INUPIAQ est le SEUL mot admis se terminant par "Q" (base entiere, toutes longueurs), et il fait 7 lettres -- cette page listerait donc EXACTEMENT le meme contenu (1 mot) que /woerter/endend-mit/q, deja indexee et gagnante. canonical_path pointe vers cette page gagnante ; jamais deux pages \'index,follow\' pour un contenu identique (verifie par comparaison directe des ensembles de mots, pas seulement des compteurs). Trouve par balayage systematique des 754 combinaisons length_start/length_end contre les familles deja indexees (start/end), 2026-08-30.',
    ),
    142 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1994,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1994 admittierten 7-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    143 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5864,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5864 admittierten 7-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    144 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 8448,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 8448 admittierten 7-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    145 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 114,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 114 admittierten 7-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    146 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 54,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 54 admittierten 7-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    147 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 10 admittierten 7-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    148 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 39,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 39 admittierten 7-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    149 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 36,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 36 admittierten 7-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    150 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 224,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 224 admittierten 7-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    151 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 7-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    152 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/ö',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 7-Buchstaben-Wortseiten, die auf "Ö" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    153 => 
    array (
      'route_path' => '/woerter/7-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/endend-mit/ü',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 7-Buchstaben-Wortseiten, die auf "Ü" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    154 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 284,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 284 admittierten 8-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    155 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 229,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 229 admittierten 8-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    156 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5 admittierten 8-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    157 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1878,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1878 admittierten 8-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    158 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 12175,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 12175 admittierten 8-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    159 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 315,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 315 admittierten 8-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    160 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1280,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1280 admittierten 8-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    161 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 966,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 966 admittierten 8-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    162 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 405,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 405 admittierten 8-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    163 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 464,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 464 admittierten 8-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    164 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1036,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1036 admittierten 8-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    165 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2156,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2156 admittierten 8-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    166 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 9921,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 9921 admittierten 8-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    167 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 124,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 124 admittierten 8-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    168 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 110,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 110 admittierten 8-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    169 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3603,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3603 admittierten 8-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    170 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 8559,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 8559 admittierten 8-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    171 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 12892,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 12892 admittierten 8-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    172 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 125,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 125 admittierten 8-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    173 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 90,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 90 admittierten 8-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    174 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 17,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 17 admittierten 8-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    175 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 35,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 35 admittierten 8-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    176 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 25,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 25 admittierten 8-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    177 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 280,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 280 admittierten 8-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    178 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5 admittierten 8-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    179 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/ö',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 8-Buchstaben-Wortseiten, die auf "Ö" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    180 => 
    array (
      'route_path' => '/woerter/8-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/endend-mit/ü',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 8-Buchstaben-Wortseiten, die auf "Ü" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    181 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 269,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 269 admittierten 9-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    182 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 226,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 226 admittierten 9-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    183 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 9-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    184 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2454,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2454 admittierten 9-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    185 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 16823,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 16823 admittierten 9-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    186 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 369,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 369 admittierten 9-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    187 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1818,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1818 admittierten 9-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    188 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1196,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1196 admittierten 9-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    189 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 296,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 296 admittierten 9-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    190 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 543,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 543 admittierten 9-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    191 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1190,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1190 admittierten 9-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    192 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4498,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4498 admittierten 9-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    193 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 15887,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 15887 admittierten 9-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    194 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 123,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 123 admittierten 9-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    195 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 84,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 84 admittierten 9-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    196 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6760,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6760 admittierten 9-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    197 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 13905,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 13905 admittierten 9-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    198 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 16677,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 16677 admittierten 9-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    199 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 152,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 152 admittierten 9-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    200 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 112,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 112 admittierten 9-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    201 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6 admittierten 9-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    202 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 16,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 16 admittierten 9-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    203 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 25,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 25 admittierten 9-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    204 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 385,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 385 admittierten 9-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    205 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/ä',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 9-Buchstaben-Wortseiten, die auf "Ä" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    206 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/ö',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 9-Buchstaben-Wortseiten, die auf "Ö" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    207 => 
    array (
      'route_path' => '/woerter/9-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/endend-mit/ü',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 9-Buchstaben-Wortseiten, die auf "Ü" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    208 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 142,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 142 admittierten 10-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    209 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 120,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 120 admittierten 10-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    210 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/c',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    211 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2443,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2443 admittierten 10-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    212 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 15149,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 15149 admittierten 10-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    213 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 244,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 244 admittierten 10-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    214 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1363,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1363 admittierten 10-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    215 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 863,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 863 admittierten 10-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    216 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 126,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 126 admittierten 10-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    217 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 328,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 328 admittierten 10-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    218 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 796,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 796 admittierten 10-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    219 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5732,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5732 admittierten 10-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    220 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 18840,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 18840 admittierten 10-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    221 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 66,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 66 admittierten 10-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    222 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 34,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 34 admittierten 10-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    223 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7710,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7710 admittierten 10-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    224 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 16655,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 16655 admittierten 10-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    225 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 12443,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 12443 admittierten 10-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    226 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 76,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 76 admittierten 10-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    227 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 76,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 76 admittierten 10-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    228 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 19,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 19 admittierten 10-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    229 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 14,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 14 admittierten 10-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    230 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 236,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 236 admittierten 10-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    231 => 
    array (
      'route_path' => '/woerter/10-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/10-buchstaben/endend-mit/ü',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    232 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 105,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 105 admittierten 11-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    233 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 110,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 110 admittierten 11-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    234 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1832,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1832 admittierten 11-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    235 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 13657,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 13657 admittierten 11-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    236 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 203,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 203 admittierten 11-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    237 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1361,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1361 admittierten 11-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    238 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 861,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 861 admittierten 11-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    239 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 117,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 117 admittierten 11-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    240 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 289,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 289 admittierten 11-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    241 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 796,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 796 admittierten 11-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    242 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6431,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6431 admittierten 11-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    243 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 20321,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 20321 admittierten 11-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    244 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 49,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 49 admittierten 11-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    245 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 19,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 19 admittierten 11-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    246 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 8461,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 8461 admittierten 11-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    247 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 15331,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 15331 admittierten 11-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    248 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 10373,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 10373 admittierten 11-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    249 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 64,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 64 admittierten 11-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    250 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 36,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 36 admittierten 11-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    251 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 11-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    252 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 14,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 14 admittierten 11-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    253 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 13,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 13 admittierten 11-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    254 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 189,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 189 admittierten 11-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    255 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/ö',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    256 => 
    array (
      'route_path' => '/woerter/11-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/endend-mit/ü',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 11-Buchstaben-Wortseiten, die auf "Ü" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    257 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 76,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 76 admittierten 12-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    258 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 73,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 73 admittierten 12-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    259 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/c',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 12-Buchstaben-Wortseiten, die auf "C" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    260 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1420,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1420 admittierten 12-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    261 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 10993,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 10993 admittierten 12-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    262 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 171,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 171 admittierten 12-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    263 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1329,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1329 admittierten 12-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    264 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 755,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 755 admittierten 12-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    265 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 90,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 90 admittierten 12-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    266 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 234,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 234 admittierten 12-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    267 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 706,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 706 admittierten 12-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    268 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6774,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6774 admittierten 12-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    269 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 18107,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 18107 admittierten 12-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    270 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 50,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 50 admittierten 12-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    271 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 17,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 17 admittierten 12-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    272 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 8490,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 8490 admittierten 12-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    273 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 13961,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 13961 admittierten 12-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    274 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7701,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7701 admittierten 12-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    275 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 42,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 42 admittierten 12-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    276 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 32,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 32 admittierten 12-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    277 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 12-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    278 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7 admittierten 12-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    279 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 10 admittierten 12-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    280 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 181,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 181 admittierten 12-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    281 => 
    array (
      'route_path' => '/woerter/12-buchstaben/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/endend-mit/ü',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 12-Buchstaben-Wortseiten, die auf "Ü" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    282 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 57,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 57 admittierten 13-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    283 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 60,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 60 admittierten 13-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    284 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1083,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1083 admittierten 13-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    285 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 9245,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 9245 admittierten 13-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    286 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 120,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 120 admittierten 13-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    287 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1265,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1265 admittierten 13-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    288 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 636,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 636 admittierten 13-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    289 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 64,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 64 admittierten 13-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    290 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 217,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 217 admittierten 13-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    291 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 518,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 518 admittierten 13-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    292 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5755,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5755 admittierten 13-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    293 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 15146,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 15146 admittierten 13-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    294 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 29,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 29 admittierten 13-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    295 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 25,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 25 admittierten 13-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    296 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7143,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7143 admittierten 13-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    297 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 11729,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 11729 admittierten 13-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    298 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5626,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5626 admittierten 13-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    299 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 31,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 31 admittierten 13-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    300 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 11,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 11 admittierten 13-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    301 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 13,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 13 admittierten 13-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    302 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 10 admittierten 13-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    303 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 142,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 142 admittierten 13-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    304 => 
    array (
      'route_path' => '/woerter/13-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/13-buchstaben/endend-mit/ä',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    305 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 51,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 51 admittierten 14-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    306 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 40,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 40 admittierten 14-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    307 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/c',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    308 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 709,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 709 admittierten 14-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    309 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7160,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7160 admittierten 14-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    310 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 108,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 108 admittierten 14-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    311 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1128,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1128 admittierten 14-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    312 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 538,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 538 admittierten 14-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    313 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 63,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 63 admittierten 14-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    314 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 171,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 171 admittierten 14-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    315 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 376,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 376 admittierten 14-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    316 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4983,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4983 admittierten 14-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    317 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 13118,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 13118 admittierten 14-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. HINWEIS: Ergebnis real > WordListSolver::ROW_EXAMINATION_CEILING=10000, angezeigt als "mindestens 10000, nicht garantiert vollstaendig" -- selbes bereits akzeptierte Verhalten wie die 2-Buchstaben-endend-mit-Familie (D-DE-017, 12/455 Faelle). Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    318 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 19,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 19 admittierten 14-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    319 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 16,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 16 admittierten 14-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    320 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 6002,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 6002 admittierten 14-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    321 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 9730,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 9730 admittierten 14-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    322 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3823,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3823 admittierten 14-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    323 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 23,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 23 admittierten 14-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    324 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 23,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 23 admittierten 14-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    325 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/w',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    326 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7 admittierten 14-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    327 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3 admittierten 14-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    328 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 114,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 114 admittierten 14-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    329 => 
    array (
      'route_path' => '/woerter/14-buchstaben/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'noindex,follow',
      'canonical_path' => '/woerter/14-buchstaben/endend-mit/ä',
      'sitemap_fragment' => NULL,
      'result_count' => 1,
      'notes' => 'TITRE TROP LONG (>= 60 caracteres), verifie en direct sur un vrai serveur PHP (pas suppose) : app/View/word-list.php enrichit <title> pour $page->total===1 en prefixant le mot unique lui-meme (audit D-031 herite, comportement de gabarit existant, hors perimetre seo-registry -- app/View/ non modifiable ici, signale pour un futur ajustement de gabarit plutot que corrige silencieusement). PAS un doublon de contenu -- canonical_path pointe vers soi-meme, cette page reste noindex uniquement pour une raison de qualite de <title>, pas de contenu. 1 seul resultat, GARDE et signale separement (pas un critere d\'exclusion a lui seul, docs/05).',
    ),
    330 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/a',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 29,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 29 admittierten 15-Buchstaben-Wortseiten, die auf "A" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    331 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/b',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 42,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 42 admittierten 15-Buchstaben-Wortseiten, die auf "B" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    332 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/d',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 528,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 528 admittierten 15-Buchstaben-Wortseiten, die auf "D" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    333 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/e',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 5297,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 5297 admittierten 15-Buchstaben-Wortseiten, die auf "E" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    334 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/f',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 74,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 74 admittierten 15-Buchstaben-Wortseiten, die auf "F" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    335 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/g',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 1128,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 1128 admittierten 15-Buchstaben-Wortseiten, die auf "G" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    336 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/h',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 426,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 426 admittierten 15-Buchstaben-Wortseiten, die auf "H" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    337 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/i',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 43,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 43 admittierten 15-Buchstaben-Wortseiten, die auf "I" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    338 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/k',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 158,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 158 admittierten 15-Buchstaben-Wortseiten, die auf "K" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    339 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/l',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 321,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 321 admittierten 15-Buchstaben-Wortseiten, die auf "L" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    340 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/m',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 3797,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 3797 admittierten 15-Buchstaben-Wortseiten, die auf "M" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    341 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/n',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 9961,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 9961 admittierten 15-Buchstaben-Wortseiten, die auf "N" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    342 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/o',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 17,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 17 admittierten 15-Buchstaben-Wortseiten, die auf "O" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    343 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/p',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 10 admittierten 15-Buchstaben-Wortseiten, die auf "P" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    344 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/r',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4548,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4548 admittierten 15-Buchstaben-Wortseiten, die auf "R" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    345 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/s',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 7503,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 7503 admittierten 15-Buchstaben-Wortseiten, die auf "S" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    346 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/t',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2594,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2594 admittierten 15-Buchstaben-Wortseiten, die auf "T" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    347 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/u',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 21,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 21 admittierten 15-Buchstaben-Wortseiten, die auf "U" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    348 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/v',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 12,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 12 admittierten 15-Buchstaben-Wortseiten, die auf "V" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    349 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/w',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 15-Buchstaben-Wortseiten, die auf "W" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    350 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/x',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 4 admittierten 15-Buchstaben-Wortseiten, die auf "X" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    351 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/y',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 2 admittierten 15-Buchstaben-Wortseiten, die auf "Y" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    352 => 
    array (
      'route_path' => '/woerter/15-buchstaben/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/endend-mit/z',
      'sitemap_fragment' => 'ends-0002',
      'result_count' => 86,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byEnd (list_counts, list_type=length_end, D-DE-018) UND von JEDER der 86 admittierten 15-Buchstaben-Wortseiten, die auf "Z" enden, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_reversed, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    353 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 10 admittierten 2-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    354 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    355 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3 admittierten 2-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    356 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 7 admittierten 2-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    357 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    358 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    359 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 8,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 8 admittierten 2-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    360 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4 admittierten 2-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    361 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    362 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    363 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    364 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5 admittierten 2-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    365 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5 admittierten 2-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    366 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    367 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    368 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    369 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4 admittierten 2-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    370 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3 admittierten 2-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    371 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 7 admittierten 2-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    372 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    373 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    374 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    375 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    376 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 2-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    377 => 
    array (
      'route_path' => '/woerter/2-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/2-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/2-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 2-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    378 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 61,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 61 admittierten 3-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    379 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 53,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 53 admittierten 3-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    380 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 14,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 14 admittierten 3-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    381 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 36,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 36 admittierten 3-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    382 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 34,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 34 admittierten 3-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    383 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 26,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 26 admittierten 3-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    384 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 34,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 34 admittierten 3-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    385 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 45,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 45 admittierten 3-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    386 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 21 admittierten 3-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    387 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 17,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 17 admittierten 3-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    388 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 33,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 33 admittierten 3-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    389 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 39,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 39 admittierten 3-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    390 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 30,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 30 admittierten 3-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    391 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 17,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 17 admittierten 3-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    392 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 32,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 32 admittierten 3-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    393 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 37,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 37 admittierten 3-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    394 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 3-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    395 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 41,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 41 admittierten 3-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    396 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 32,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 32 admittierten 3-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    397 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 31,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 31 admittierten 3-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    398 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 21 admittierten 3-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    399 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 11,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 11 admittierten 3-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    400 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 30,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 30 admittierten 3-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    401 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1 admittierten 3-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    402 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5 admittierten 3-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    403 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 16,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 16 admittierten 3-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    404 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 10 admittierten 3-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    405 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 12,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 12 admittierten 3-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    406 => 
    array (
      'route_path' => '/woerter/3-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/3-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/3-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4 admittierten 3-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    407 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 180,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 180 admittierten 4-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    408 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 236,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 236 admittierten 4-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    409 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 56,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 56 admittierten 4-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    410 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 152,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 152 admittierten 4-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    411 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 116,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 116 admittierten 4-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    412 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 175,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 175 admittierten 4-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    413 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 166,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 166 admittierten 4-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    414 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 216,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 216 admittierten 4-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    415 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 63,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 63 admittierten 4-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    416 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 71,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 71 admittierten 4-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    417 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 231,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 231 admittierten 4-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    418 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 189,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 189 admittierten 4-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    419 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 194,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 194 admittierten 4-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    420 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 89,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 89 admittierten 4-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    421 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 76,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 76 admittierten 4-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    422 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 173,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 173 admittierten 4-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    423 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 13,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 13 admittierten 4-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    424 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 178,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 178 admittierten 4-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    425 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 268,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 268 admittierten 4-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    426 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 177,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 177 admittierten 4-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    427 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 46,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 46 admittierten 4-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    428 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 50,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 50 admittierten 4-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    429 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 175,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 175 admittierten 4-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    430 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 18,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 18 admittierten 4-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    431 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 94,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 94 admittierten 4-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    432 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 25,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 25 admittierten 4-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    433 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 27,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 27 admittierten 4-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    434 => 
    array (
      'route_path' => '/woerter/4-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/4-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 8,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/4-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 8 admittierten 4-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    435 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 580,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 580 admittierten 5-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    436 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 707,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 707 admittierten 5-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    437 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 158,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 158 admittierten 5-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    438 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 383,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 383 admittierten 5-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    439 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 322,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 322 admittierten 5-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    440 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 520,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 520 admittierten 5-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    441 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 466,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 466 admittierten 5-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    442 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 510,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 510 admittierten 5-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    443 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 125,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 125 admittierten 5-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    444 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 144,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 144 admittierten 5-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    445 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 677,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 677 admittierten 5-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    446 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 481,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 481 admittierten 5-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    447 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 527,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 527 admittierten 5-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    448 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 223,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 223 admittierten 5-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    449 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 155,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 155 admittierten 5-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    450 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 500,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 500 admittierten 5-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    451 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 51,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 51 admittierten 5-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    452 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 486,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 486 admittierten 5-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    453 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 871,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 871 admittierten 5-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    454 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 543,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 543 admittierten 5-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    455 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 137,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 137 admittierten 5-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    456 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 140,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 140 admittierten 5-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    457 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 383,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 383 admittierten 5-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    458 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 12,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 12 admittierten 5-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    459 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 25,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 25 admittierten 5-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    460 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 308,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 308 admittierten 5-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    461 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 72,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 72 admittierten 5-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    462 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 32,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 32 admittierten 5-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    463 => 
    array (
      'route_path' => '/woerter/5-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/5-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 20,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/5-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 20 admittierten 5-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    464 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1623,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1623 admittierten 6-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    465 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1636,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1636 admittierten 6-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    466 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 302,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 302 admittierten 6-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    467 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 781,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 781 admittierten 6-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    468 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 828,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 828 admittierten 6-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    469 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1038,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1038 admittierten 6-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    470 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1194,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1194 admittierten 6-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    471 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 998,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 998 admittierten 6-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    472 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 186,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 186 admittierten 6-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    473 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 235,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 235 admittierten 6-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    474 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1543,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1543 admittierten 6-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    475 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 889,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 889 admittierten 6-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    476 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1066,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1066 admittierten 6-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    477 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 436,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 436 admittierten 6-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    478 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 269,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 269 admittierten 6-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    479 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1122,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1122 admittierten 6-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    480 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 110,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 110 admittierten 6-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    481 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 965,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 965 admittierten 6-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    482 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1908,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1908 admittierten 6-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    483 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1064,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1064 admittierten 6-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    484 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 427,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 427 admittierten 6-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    485 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 362,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 362 admittierten 6-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    486 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 797,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 797 admittierten 6-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    487 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 17,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 17 admittierten 6-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    488 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 23,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 23 admittierten 6-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    489 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 728,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 728 admittierten 6-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    490 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 105,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 105 admittierten 6-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    491 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 58,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 58 admittierten 6-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    492 => 
    array (
      'route_path' => '/woerter/6-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/6-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/6-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 10 admittierten 6-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    493 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3658,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3658 admittierten 7-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    494 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2854,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2854 admittierten 7-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    495 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 486,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 486 admittierten 7-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    496 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1207,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1207 admittierten 7-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    497 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1899,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1899 admittierten 7-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    498 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1616,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1616 admittierten 7-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    499 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2626,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2626 admittierten 7-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    500 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1478,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1478 admittierten 7-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    501 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 351,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 351 admittierten 7-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    502 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 320,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 320 admittierten 7-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    503 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2454,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2454 admittierten 7-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    504 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1288,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1288 admittierten 7-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    505 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1606,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1606 admittierten 7-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    506 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 716,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 716 admittierten 7-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    507 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 391,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 391 admittierten 7-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    508 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1875,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1875 admittierten 7-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    509 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 192,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 192 admittierten 7-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    510 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1540,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1540 admittierten 7-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    511 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3430,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3430 admittierten 7-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    512 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1553,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1553 admittierten 7-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    513 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 799,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 799 admittierten 7-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    514 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1180,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1180 admittierten 7-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    515 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1238,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1238 admittierten 7-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    516 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 20,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 20 admittierten 7-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    517 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 14,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 14 admittierten 7-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    518 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1286,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1286 admittierten 7-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    519 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 152,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 152 admittierten 7-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    520 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 108,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 108 admittierten 7-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    521 => 
    array (
      'route_path' => '/woerter/7-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/7-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 67,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/7-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 67 admittierten 7-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    522 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6641,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 6641 admittierten 8-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    523 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4518,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4518 admittierten 8-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    524 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 605,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 605 admittierten 8-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    525 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1776,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1776 admittierten 8-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    526 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3638,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3638 admittierten 8-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    527 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2494,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2494 admittierten 8-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    528 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4387,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4387 admittierten 8-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    529 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2337,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2337 admittierten 8-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    530 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 547,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 547 admittierten 8-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    531 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 372,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 372 admittierten 8-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    532 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3373,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3373 admittierten 8-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    533 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1873,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1873 admittierten 8-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    534 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2322,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2322 admittierten 8-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    535 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1118,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1118 admittierten 8-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    536 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 544,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 544 admittierten 8-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    537 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2508,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2508 admittierten 8-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    538 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 233,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 233 admittierten 8-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    539 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2242,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2242 admittierten 8-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    540 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5131,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5131 admittierten 8-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    541 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2218,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2218 admittierten 8-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    542 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1447,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1447 admittierten 8-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    543 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2360,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2360 admittierten 8-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    544 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1872,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1872 admittierten 8-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    545 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 35,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 35 admittierten 8-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    546 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 10,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 10 admittierten 8-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    547 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1821,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1821 admittierten 8-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    548 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 154,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 154 admittierten 8-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    549 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 145,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 145 admittierten 8-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    550 => 
    array (
      'route_path' => '/woerter/8-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/8-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 264,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/8-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 264 admittierten 8-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    551 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9890,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 9890 admittierten 9-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    552 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6481,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 6481 admittierten 9-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    553 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 628,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 628 admittierten 9-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    554 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2735,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2735 admittierten 9-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    555 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5653,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5653 admittierten 9-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    556 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3580,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3580 admittierten 9-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    557 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7075,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 7075 admittierten 9-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    558 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3799,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3799 admittierten 9-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    559 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 877,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 877 admittierten 9-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    560 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 519,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 519 admittierten 9-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    561 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4518,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4518 admittierten 9-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    562 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2580,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2580 admittierten 9-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    563 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3144,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3144 admittierten 9-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    564 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1784,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1784 admittierten 9-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    565 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 720,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 720 admittierten 9-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    566 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3341,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3341 admittierten 9-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    567 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 321,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 321 admittierten 9-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    568 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3151,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3151 admittierten 9-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    569 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7450,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 7450 admittierten 9-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    570 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2934,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2934 admittierten 9-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    571 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2080,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2080 admittierten 9-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    572 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4284,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4284 admittierten 9-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    573 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2722,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2722 admittierten 9-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    574 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 35,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 35 admittierten 9-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    575 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 16,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 16 admittierten 9-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    576 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2640,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2640 admittierten 9-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    577 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 173,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 173 admittierten 9-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    578 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 137,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 137 admittierten 9-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    579 => 
    array (
      'route_path' => '/woerter/9-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/9-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 558,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/9-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 558 admittierten 9-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    580 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9498,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 9498 admittierten 10-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    581 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6212,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 6212 admittierten 10-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    582 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 588,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 588 admittierten 10-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    583 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2671,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2671 admittierten 10-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    584 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5497,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5497 admittierten 10-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    585 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3695,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3695 admittierten 10-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    586 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5669,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5669 admittierten 10-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    587 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3853,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3853 admittierten 10-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    588 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1023,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1023 admittierten 10-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    589 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 468,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 468 admittierten 10-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    590 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4649,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4649 admittierten 10-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    591 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2412,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2412 admittierten 10-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    592 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3382,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3382 admittierten 10-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    593 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1836,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1836 admittierten 10-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    594 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 815,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 815 admittierten 10-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    595 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3443,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3443 admittierten 10-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    596 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 285,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 285 admittierten 10-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    597 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3075,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3075 admittierten 10-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    598 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7840,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 7840 admittierten 10-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    599 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2941,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2941 admittierten 10-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    600 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2284,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2284 admittierten 10-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    601 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4954,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4954 admittierten 10-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    602 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2885,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2885 admittierten 10-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    603 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 46,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 46 admittierten 10-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    604 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 19,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 19 admittierten 10-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    605 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2553,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2553 admittierten 10-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    606 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 130,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 130 admittierten 10-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    607 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 100,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 100 admittierten 10-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    608 => 
    array (
      'route_path' => '/woerter/10-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/10-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 654,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/10-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 654 admittierten 10-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    609 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 10366,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 10366 admittierten 11-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    610 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5874,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5874 admittierten 11-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    611 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 458,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 458 admittierten 11-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    612 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3071,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3071 admittierten 11-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    613 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5169,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5169 admittierten 11-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    614 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3293,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3293 admittierten 11-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    615 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4081,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4081 admittierten 11-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    616 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3769,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3769 admittierten 11-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    617 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1151,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1151 admittierten 11-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    618 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 432,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 432 admittierten 11-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    619 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4558,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4558 admittierten 11-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    620 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2051,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2051 admittierten 11-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    621 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2977,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2977 admittierten 11-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    622 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1803,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1803 admittierten 11-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    623 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 774,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 774 admittierten 11-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    624 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3268,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3268 admittierten 11-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    625 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 244,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 244 admittierten 11-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    626 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2726,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2726 admittierten 11-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    627 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7995,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 7995 admittierten 11-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    628 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2658,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2658 admittierten 11-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    629 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2820,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2820 admittierten 11-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    630 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4711,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4711 admittierten 11-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    631 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2760,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2760 admittierten 11-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    632 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 47,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 47 admittierten 11-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    633 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 14,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 14 admittierten 11-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    634 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2558,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2558 admittierten 11-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    635 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 131,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 131 admittierten 11-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    636 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 69,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 69 admittierten 11-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    637 => 
    array (
      'route_path' => '/woerter/11-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/11-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 811,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/11-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 811 admittierten 11-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    638 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9298,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 9298 admittierten 12-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    639 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4487,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4487 admittierten 12-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    640 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 400,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 400 admittierten 12-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    641 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2685,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2685 admittierten 12-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    642 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4681,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4681 admittierten 12-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    643 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2870,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2870 admittierten 12-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    644 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3484,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3484 admittierten 12-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    645 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3600,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3600 admittierten 12-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    646 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1149,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1149 admittierten 12-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    647 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 316,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 316 admittierten 12-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    648 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3898,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3898 admittierten 12-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    649 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1703,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1703 admittierten 12-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    650 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2749,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2749 admittierten 12-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    651 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1694,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1694 admittierten 12-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    652 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 702,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 702 admittierten 12-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    653 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2769,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2769 admittierten 12-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    654 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 168,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 168 admittierten 12-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    655 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2151,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2151 admittierten 12-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    656 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6768,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 6768 admittierten 12-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    657 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2020,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2020 admittierten 12-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    658 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2672,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2672 admittierten 12-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    659 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5040,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5040 admittierten 12-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    660 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2525,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2525 admittierten 12-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    661 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 58,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 58 admittierten 12-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    662 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 9 admittierten 12-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    663 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2391,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2391 admittierten 12-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    664 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 79,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 79 admittierten 12-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    665 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 46,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 46 admittierten 12-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    666 => 
    array (
      'route_path' => '/woerter/12-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/12-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 814,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/12-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 814 admittierten 12-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    667 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7187,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 7187 admittierten 13-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    668 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3221,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3221 admittierten 13-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    669 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 298,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 298 admittierten 13-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    670 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2436,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2436 admittierten 13-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    671 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3508,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3508 admittierten 13-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    672 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2537,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2537 admittierten 13-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    673 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2666,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2666 admittierten 13-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    674 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3169,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3169 admittierten 13-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    675 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1083,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1083 admittierten 13-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    676 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 284,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 284 admittierten 13-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    677 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3413,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3413 admittierten 13-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    678 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1481,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1481 admittierten 13-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    679 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2360,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2360 admittierten 13-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    680 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1464,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1464 admittierten 13-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    681 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 510,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 510 admittierten 13-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    682 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2408,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2408 admittierten 13-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    683 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 148,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 148 admittierten 13-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    684 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1778,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1778 admittierten 13-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    685 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5751,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5751 admittierten 13-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    686 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1591,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1591 admittierten 13-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    687 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2618,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2618 admittierten 13-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    688 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3775,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3775 admittierten 13-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    689 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2140,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2140 admittierten 13-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    690 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 45,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 45 admittierten 13-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    691 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5 admittierten 13-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    692 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2118,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2118 admittierten 13-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    693 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 76,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 76 admittierten 13-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    694 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 38,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 38 admittierten 13-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    695 => 
    array (
      'route_path' => '/woerter/13-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/13-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 818,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/13-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 818 admittierten 13-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    696 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5386,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5386 admittierten 14-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    697 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2471,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2471 admittierten 14-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    698 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 271,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 271 admittierten 14-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    699 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2098,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2098 admittierten 14-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    700 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2676,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2676 admittierten 14-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    701 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2157,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2157 admittierten 14-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    702 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2181,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2181 admittierten 14-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    703 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2996,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2996 admittierten 14-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    704 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 946,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 946 admittierten 14-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    705 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 220,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 220 admittierten 14-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    706 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2810,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2810 admittierten 14-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    707 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1210,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1210 admittierten 14-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    708 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1895,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1895 admittierten 14-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    709 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1322,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1322 admittierten 14-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    710 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 436,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 436 admittierten 14-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    711 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2041,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2041 admittierten 14-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    712 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 94,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 94 admittierten 14-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    713 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1503,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1503 admittierten 14-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    714 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4684,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 4684 admittierten 14-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    715 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1244,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1244 admittierten 14-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    716 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2498,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2498 admittierten 14-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    717 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2655,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2655 admittierten 14-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    718 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1898,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1898 admittierten 14-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    719 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 32,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 32 admittierten 14-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    720 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2 admittierten 14-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    721 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1805,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1805 admittierten 14-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    722 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 33,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 33 admittierten 14-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    723 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 45,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 45 admittierten 14-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    724 => 
    array (
      'route_path' => '/woerter/14-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/14-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 599,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/14-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 599 admittierten 14-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    725 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/a',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/a',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3349,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3349 admittierten 15-Buchstaben-Wortseiten, die mit "A" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    726 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/b',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/b',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1678,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1678 admittierten 15-Buchstaben-Wortseiten, die mit "B" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    727 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/c',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/c',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 206,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 206 admittierten 15-Buchstaben-Wortseiten, die mit "C" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    728 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/d',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/d',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1611,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1611 admittierten 15-Buchstaben-Wortseiten, die mit "D" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    729 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/e',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/e',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1886,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1886 admittierten 15-Buchstaben-Wortseiten, die mit "E" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    730 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/f',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/f',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1653,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1653 admittierten 15-Buchstaben-Wortseiten, die mit "F" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    731 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/g',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/g',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1760,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1760 admittierten 15-Buchstaben-Wortseiten, die mit "G" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    732 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/h',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/h',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2435,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2435 admittierten 15-Buchstaben-Wortseiten, die mit "H" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    733 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/i',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/i',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 749,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 749 admittierten 15-Buchstaben-Wortseiten, die mit "I" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    734 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/j',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/j',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 146,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 146 admittierten 15-Buchstaben-Wortseiten, die mit "J" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    735 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/k',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/k',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2309,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 2309 admittierten 15-Buchstaben-Wortseiten, die mit "K" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    736 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/l',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/l',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 998,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 998 admittierten 15-Buchstaben-Wortseiten, die mit "L" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    737 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/m',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/m',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1534,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1534 admittierten 15-Buchstaben-Wortseiten, die mit "M" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    738 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/n',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/n',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 934,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 934 admittierten 15-Buchstaben-Wortseiten, die mit "N" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    739 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/o',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/o',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 339,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 339 admittierten 15-Buchstaben-Wortseiten, die mit "O" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    740 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/p',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/p',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1591,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1591 admittierten 15-Buchstaben-Wortseiten, die mit "P" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    741 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/q',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/q',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 81,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 81 admittierten 15-Buchstaben-Wortseiten, die mit "Q" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    742 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/r',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/r',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1159,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1159 admittierten 15-Buchstaben-Wortseiten, die mit "R" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    743 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/s',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/s',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3702,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 3702 admittierten 15-Buchstaben-Wortseiten, die mit "S" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    744 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/t',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/t',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 933,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 933 admittierten 15-Buchstaben-Wortseiten, die mit "T" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    745 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/u',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/u',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1907,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1907 admittierten 15-Buchstaben-Wortseiten, die mit "U" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    746 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/v',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/v',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1919,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1919 admittierten 15-Buchstaben-Wortseiten, die mit "V" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    747 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/w',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/w',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1584,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1584 admittierten 15-Buchstaben-Wortseiten, die mit "W" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    748 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/x',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/x',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 28,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 28 admittierten 15-Buchstaben-Wortseiten, die mit "X" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    749 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/y',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/y',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 5 admittierten 15-Buchstaben-Wortseiten, die mit "Y" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    750 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/z',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/z',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1634,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 1634 admittierten 15-Buchstaben-Wortseiten, die mit "Z" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    751 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/ä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/ä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 29,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 29 admittierten 15-Buchstaben-Wortseiten, die mit "Ä" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    752 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/ö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/ö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 17,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 17 admittierten 15-Buchstaben-Wortseiten, die mit "Ö" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
    753 => 
    array (
      'route_path' => '/woerter/15-buchstaben/beginnend-mit/ü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/15-buchstaben/beginnend-mit/ü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 427,
      'notes' => 'Verlinkt von der bereits indexierten Laengenseite /woerter/15-buchstaben via App\\Search\\LengthLinksBuilder::build()->byStart (list_counts, list_type=length_start, D-DE-018) UND von JEDER der 427 admittierten 15-Buchstaben-Wortseiten, die mit "Ü" beginnen, deren eigene Wortseite ebenfalls auf ihre Laengenseite zurueckverlinkt (App\\Search\\RelationsFinder::relatedSearches()) -- vollstaendiger Sweep (754/754 Kombinationen), 0 Seite ueber dem TTFB-Budget von 250 ms, EXPLAIN QUERY PLAN SEARCH ueber COVERING INDEX idx_terms_length_normalized, nie ein SCAN. Verlinkt zu jedem einzelnen Wort via App\\Search\\WordListSolver/word-list.php.',
    ),
  ),
);
