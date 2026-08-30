<?php

declare(strict_types=1);

/**
 * Palier 1-lettre de word_list_terminant (D-DE-023, docs/DECISIONS.md), symetrique a
 * beginnend-mit 1 lettre (deja indexe, D-DE-017) : 28 des 29 buckets 'end' de list_counts
 * (D-DE-018, granularite revisee/confirmee D-DE-023). Lien interne reel depuis le hub /woerter
 * (App\Search\ExploreHubBuilder, section "Nach Endbuchstabe") -- cette source de lien N'EXISTAIT
 * PAS quand D-DE-017 a ferme cette famille a 1 lettre (list_counts etait vide a l'epoque,
 * chronologie confirmee : D-DE-017 precede D-DE-018) -- mesure perimee par la construction de
 * list_counts, pas une erreur initiale. Decision produit explicite de rouvrir (2026-08-30, en
 * direct dans la conversation).
 *
 * 1 EXCLUSION : Q (1 seul mot, INUPIAQ) -- DOUBLON DE CONTENU EXACT avec /woerter/endend-mit/aq
 * (deja indexee, D-DE-017, meme mot unique) -- verifie directement (pas suppose), reste
 * noindex,follow, canonical implicite vers la page gagnante deja en place.
 *
 * TTFB verifie en direct (php -S, rechauffement + mediane de 3 executions, lecon de
 * methodologie de ES-018 appliquee ici) sur les 6 buckets les plus lourds (N=131784,
 * S=108839, E=104561, T=86856, R=56865, M=41961) : 88-96 ms, tres sous le budget 250 ms malgre
 * la troncature ROW_EXAMINATION_CEILING=10000 sur les buckets les plus gros (la troncature CAP
 * le cout d'examen, elle ne l'aggrave pas -- meme constat que D-DE-017 sur la famille 2 lettres).
 *
 * Applique via :
 *     php scripts/apply_seo_batch.php scripts/seo-batches/endend-mit-single-letter-2026-08-30.php
 */
return
array (
  'batch_id' => 'endend-mit-single-letter-2026-08-30',
  'added_at' => '2026-08-30',
  'rows' => 
  array (
    0 => 
    array (
      'route_path' => '/woerter/endend-mit/a',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/a',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2066,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 2066 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    1 => 
    array (
      'route_path' => '/woerter/endend-mit/b',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/b',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1488,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 1488 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    2 => 
    array (
      'route_path' => '/woerter/endend-mit/c',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/c',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 48,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 48 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    3 => 
    array (
      'route_path' => '/woerter/endend-mit/d',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/d',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 14455 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    4 => 
    array (
      'route_path' => '/woerter/endend-mit/e',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/e',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 104561 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    5 => 
    array (
      'route_path' => '/woerter/endend-mit/f',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/f',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2237,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 2237 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    6 => 
    array (
      'route_path' => '/woerter/endend-mit/g',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/g',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 12964 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    7 => 
    array (
      'route_path' => '/woerter/endend-mit/h',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/h',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 7389,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 7389 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    8 => 
    array (
      'route_path' => '/woerter/endend-mit/i',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/i',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2048,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 2048 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    9 => 
    array (
      'route_path' => '/woerter/endend-mit/j',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/j',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 2 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    10 => 
    array (
      'route_path' => '/woerter/endend-mit/k',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/k',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 3362,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 3362 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    11 => 
    array (
      'route_path' => '/woerter/endend-mit/l',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/l',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 8098,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 8098 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    12 => 
    array (
      'route_path' => '/woerter/endend-mit/m',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/m',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 41961 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    13 => 
    array (
      'route_path' => '/woerter/endend-mit/n',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/n',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 131784 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    14 => 
    array (
      'route_path' => '/woerter/endend-mit/o',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/o',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 1031,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 1031 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    15 => 
    array (
      'route_path' => '/woerter/endend-mit/p',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/p',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 623,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 623 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    16 => 
    array (
      'route_path' => '/woerter/endend-mit/r',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/r',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 56865 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    17 => 
    array (
      'route_path' => '/woerter/endend-mit/s',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/s',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 108839 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    18 => 
    array (
      'route_path' => '/woerter/endend-mit/t',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/t',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 10000,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 86856 resultat(s) reels (TRONQUE a 10000, ROW_EXAMINATION_CEILING, meme precedent accepte D-DE-017 : affichage honnete "au moins 10000", jamais presente comme un compte definitif). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    19 => 
    array (
      'route_path' => '/woerter/endend-mit/u',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/u',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 895,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 895 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    20 => 
    array (
      'route_path' => '/woerter/endend-mit/v',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/v',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 482,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 482 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    21 => 
    array (
      'route_path' => '/woerter/endend-mit/w',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/w',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 58,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 58 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    22 => 
    array (
      'route_path' => '/woerter/endend-mit/x',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/x',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 268,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 268 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    23 => 
    array (
      'route_path' => '/woerter/endend-mit/y',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/y',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 270,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 270 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    24 => 
    array (
      'route_path' => '/woerter/endend-mit/z',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/z',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 2134,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 2134 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    25 => 
    array (
      'route_path' => '/woerter/endend-mit/ä',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ä',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 33,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 33 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    26 => 
    array (
      'route_path' => '/woerter/endend-mit/ö',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ö',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 17,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 17 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
    27 => 
    array (
      'route_path' => '/woerter/endend-mit/ü',
      'family' => 'word_list_terminant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/endend-mit/ü',
      'sitemap_fragment' => 'ends-0001',
      'result_count' => 21,
      'notes' => 'Palier 1 lettre de word_list_terminant (D-DE-023, symetrique a beginnend-mit 1 lettre deja indexe D-DE-017). Lien interne reel depuis /woerter (hub, noindex,follow -- App\\Search\\ExploreHubBuilder, section "Nach Endbuchstabe", list_counts \'end\') vers CHAQUE lettre, verifie en direct (php -S). 21 resultat(s) reels (sous le plafond de troncature). TTFB mesure en direct (rechauffement + mediane de 3 executions) sur les buckets les plus lourds (N/S/E/T/R/M) : 88-96 ms, tres en dessous du budget 250 ms malgre la troncature.',
    ),
  ),
);
