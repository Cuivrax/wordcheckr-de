<?php

declare(strict_types=1);

/**
 * Palier "beginnend-mit a 2 lettres" (D-DE-024, docs/DECISIONS.md), symetrique au palier 1
 * lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes, dernier palier manquant du cote
 * "commencant" (le funnel etait 1+3 lettres, jamais 2). Lien interne reel depuis CHAQUE mot
 * admis de longueur > 2 (App\Search\RelationsFinder::relatedSearches(), categorie startsWith)
 * -- extension redirigee vers ce palier via App\Search\PrefixExtensionLinksBuilder, deja en
 * production.
 *
 * 69 DOUBLONS DE CONTENU EXACT trouves contre le palier 3 lettres deja indexe (balayage
 * PROGRAMMATIQUE, pas suppose) : quand un prefixe de 2 lettres n'a qu'UN SEUL enfant de 3
 * lettres non vide ET que leur compte est identique, les deux pages montrent exactement le meme
 * ensemble de mots. Regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php, deja
 * etablie cote francais) : la forme la plus COURTE gagne -- ces 69 nouvelles pages a 2
 * lettres deviennent donc les gagnantes, et les 69 pages a 3 lettres correspondantes
 * (deja indexees D-DE-019) sont corrigees separement (scripts/seo-batches/
 * prefix3-2026-08-30.php) vers noindex,follow avec canonical pointant ici.
 *
 * 420 lignes au total (351 pages normales + 69 gagnantes de doublon).
 *
 * TTFB verifie en direct (php -S) sur un echantillon (CD/GF/MN) : 5-25 ms, mode EXACT comme les
 * paliers 1 et 3 lettres deja indexes, tres sous le budget 250 ms.
 *
 * Applique via :
 *     php scripts/apply_seo_batch.php scripts/seo-batches/beginnend-mit-two-letters-2026-08-30.php
 */
return
array (
  'batch_id' => 'beginnend-mit-two-letters-2026-08-30',
  'added_at' => '2026-08-30',
  'rows' => 
  array (
    0 => 
    array (
      'route_path' => '/woerter/beginnend-mit/aa',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/aa',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 344,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 344 resultat(s) reels (result_count stocke le compte reel).',
    ),
    1 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ab',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ab',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 17746,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 17746 resultat(s) reels (result_count stocke le compte reel).',
    ),
    2 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ac',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ac',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 923,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 923 resultat(s) reels (result_count stocke le compte reel).',
    ),
    3 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ad',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ad',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1033,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1033 resultat(s) reels (result_count stocke le compte reel).',
    ),
    4 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ae',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ae',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 109,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 109 resultat(s) reels (result_count stocke le compte reel).',
    ),
    5 => 
    array (
      'route_path' => '/woerter/beginnend-mit/af',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/af',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 462,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 462 resultat(s) reels (result_count stocke le compte reel).',
    ),
    6 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ag',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ag',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 467,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 467 resultat(s) reels (result_count stocke le compte reel).',
    ),
    7 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ah',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ah',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 184,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 184 resultat(s) reels (result_count stocke le compte reel).',
    ),
    8 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ai',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ai',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 86,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 86 resultat(s) reels (result_count stocke le compte reel).',
    ),
    9 => 
    array (
      'route_path' => '/woerter/beginnend-mit/aj',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/aj',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 11,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 11 resultat(s) reels (result_count stocke le compte reel).',
    ),
    10 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ak',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ak',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1022,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1022 resultat(s) reels (result_count stocke le compte reel).',
    ),
    11 => 
    array (
      'route_path' => '/woerter/beginnend-mit/al',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/al',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2809,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2809 resultat(s) reels (result_count stocke le compte reel).',
    ),
    12 => 
    array (
      'route_path' => '/woerter/beginnend-mit/am',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/am',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1218,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1218 resultat(s) reels (result_count stocke le compte reel).',
    ),
    13 => 
    array (
      'route_path' => '/woerter/beginnend-mit/an',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/an',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 14004,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 14004 resultat(s) reels (result_count stocke le compte reel).',
    ),
    14 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ao',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ao',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 19,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 19 resultat(s) reels (result_count stocke le compte reel).',
    ),
    15 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ap',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ap',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1059,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1059 resultat(s) reels (result_count stocke le compte reel).',
    ),
    16 => 
    array (
      'route_path' => '/woerter/beginnend-mit/aq',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/aq',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 109,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 109 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/aqu (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/aqu corrigee separement (canonical -> /woerter/beginnend-mit/aq, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    17 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ar',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ar',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2195,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2195 resultat(s) reels (result_count stocke le compte reel).',
    ),
    18 => 
    array (
      'route_path' => '/woerter/beginnend-mit/as',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/as',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1056,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1056 resultat(s) reels (result_count stocke le compte reel).',
    ),
    19 => 
    array (
      'route_path' => '/woerter/beginnend-mit/at',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/at',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 835,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 835 resultat(s) reels (result_count stocke le compte reel).',
    ),
    20 => 
    array (
      'route_path' => '/woerter/beginnend-mit/au',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/au',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21396,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 21396 resultat(s) reels (result_count stocke le compte reel).',
    ),
    21 => 
    array (
      'route_path' => '/woerter/beginnend-mit/av',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/av',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 208,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 208 resultat(s) reels (result_count stocke le compte reel).',
    ),
    22 => 
    array (
      'route_path' => '/woerter/beginnend-mit/aw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/aw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 25,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 25 resultat(s) reels (result_count stocke le compte reel).',
    ),
    23 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ax',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ax',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 98,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 98 resultat(s) reels (result_count stocke le compte reel).',
    ),
    24 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ay',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ay',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 21 resultat(s) reels (result_count stocke le compte reel).',
    ),
    25 => 
    array (
      'route_path' => '/woerter/beginnend-mit/az',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/az',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 286,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 286 resultat(s) reels (result_count stocke le compte reel).',
    ),
    26 => 
    array (
      'route_path' => '/woerter/beginnend-mit/aö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/aö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/aöd (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/aöd corrigee separement (canonical -> /woerter/beginnend-mit/aö, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    27 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ba',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ba',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4994,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4994 resultat(s) reels (result_count stocke le compte reel).',
    ),
    28 => 
    array (
      'route_path' => '/woerter/beginnend-mit/be',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/be',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 19221,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 19221 resultat(s) reels (result_count stocke le compte reel).',
    ),
    29 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 21 resultat(s) reels (result_count stocke le compte reel).',
    ),
    30 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3184,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3184 resultat(s) reels (result_count stocke le compte reel).',
    ),
    31 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3207,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3207 resultat(s) reels (result_count stocke le compte reel).',
    ),
    32 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2185,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2185 resultat(s) reels (result_count stocke le compte reel).',
    ),
    33 => 
    array (
      'route_path' => '/woerter/beginnend-mit/br',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/br',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3570,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3570 resultat(s) reels (result_count stocke le compte reel).',
    ),
    34 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bs',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bs',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/bst (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/bst corrigee separement (canonical -> /woerter/beginnend-mit/bs, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    35 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2258,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2258 resultat(s) reels (result_count stocke le compte reel).',
    ),
    36 => 
    array (
      'route_path' => '/woerter/beginnend-mit/by',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/by',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 34,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 34 resultat(s) reels (result_count stocke le compte reel).',
    ),
    37 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 460,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 460 resultat(s) reels (result_count stocke le compte reel).',
    ),
    38 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 380,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 380 resultat(s) reels (result_count stocke le compte reel).',
    ),
    39 => 
    array (
      'route_path' => '/woerter/beginnend-mit/bü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/bü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 915,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 915 resultat(s) reels (result_count stocke le compte reel).',
    ),
    40 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ca',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ca',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 682,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 682 resultat(s) reels (result_count stocke le compte reel).',
    ),
    41 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cd',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cd',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/cds (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/cds corrigee separement (canonical -> /woerter/beginnend-mit/cd, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    42 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ce',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ce',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 156,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 156 resultat(s) reels (result_count stocke le compte reel).',
    ),
    43 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ch',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ch',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1764,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1764 resultat(s) reels (result_count stocke le compte reel).',
    ),
    44 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ci',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ci',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 119,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 119 resultat(s) reels (result_count stocke le compte reel).',
    ),
    45 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 304,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 304 resultat(s) reels (result_count stocke le compte reel).',
    ),
    46 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cm',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cm',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/cmo (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/cmo corrigee separement (canonical -> /woerter/beginnend-mit/cm, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    47 => 
    array (
      'route_path' => '/woerter/beginnend-mit/co',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/co',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 892,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 892 resultat(s) reels (result_count stocke le compte reel).',
    ),
    48 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cp',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cp',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/cpu (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/cpu corrigee separement (canonical -> /woerter/beginnend-mit/cp, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    49 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 274,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 274 resultat(s) reels (result_count stocke le compte reel).',
    ),
    50 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cs',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cs',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel).',
    ),
    51 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 166,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 166 resultat(s) reels (result_count stocke le compte reel).',
    ),
    52 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 93,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 93 resultat(s) reels (result_count stocke le compte reel).',
    ),
    53 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/cäs (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/cäs corrigee separement (canonical -> /woerter/beginnend-mit/cä, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    54 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/cöl (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/cöl corrigee separement (canonical -> /woerter/beginnend-mit/cö, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    55 => 
    array (
      'route_path' => '/woerter/beginnend-mit/cü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/cü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/cüp (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/cüp corrigee separement (canonical -> /woerter/beginnend-mit/cü, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    56 => 
    array (
      'route_path' => '/woerter/beginnend-mit/da',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/da',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3418,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3418 resultat(s) reels (result_count stocke le compte reel).',
    ),
    57 => 
    array (
      'route_path' => '/woerter/beginnend-mit/de',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/de',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4825,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4825 resultat(s) reels (result_count stocke le compte reel).',
    ),
    58 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/dha (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/dha corrigee separement (canonical -> /woerter/beginnend-mit/dh, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    59 => 
    array (
      'route_path' => '/woerter/beginnend-mit/di',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/di',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3496,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3496 resultat(s) reels (result_count stocke le compte reel).',
    ),
    60 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dj',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dj',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 8,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 8 resultat(s) reels (result_count stocke le compte reel).',
    ),
    61 => 
    array (
      'route_path' => '/woerter/beginnend-mit/do',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/do',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1757,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1757 resultat(s) reels (result_count stocke le compte reel).',
    ),
    62 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3088,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3088 resultat(s) reels (result_count stocke le compte reel).',
    ),
    63 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ds',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ds',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 59,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 59 resultat(s) reels (result_count stocke le compte reel).',
    ),
    64 => 
    array (
      'route_path' => '/woerter/beginnend-mit/du',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/du',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3950,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3950 resultat(s) reels (result_count stocke le compte reel).',
    ),
    65 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dv',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dv',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/dvd (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/dvd corrigee separement (canonical -> /woerter/beginnend-mit/dv, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    66 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 9 resultat(s) reels (result_count stocke le compte reel).',
    ),
    67 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 146,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 146 resultat(s) reels (result_count stocke le compte reel).',
    ),
    68 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dz',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dz',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/dzo (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/dzo corrigee separement (canonical -> /woerter/beginnend-mit/dz, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    69 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 238,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 238 resultat(s) reels (result_count stocke le compte reel).',
    ),
    70 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 167,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 167 resultat(s) reels (result_count stocke le compte reel).',
    ),
    71 => 
    array (
      'route_path' => '/woerter/beginnend-mit/dü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/dü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 479,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 479 resultat(s) reels (result_count stocke le compte reel).',
    ),
    72 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ea',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ea',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel).',
    ),
    73 => 
    array (
      'route_path' => '/woerter/beginnend-mit/eb',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/eb',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 176,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 176 resultat(s) reels (result_count stocke le compte reel).',
    ),
    74 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ec',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ec',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 299,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 299 resultat(s) reels (result_count stocke le compte reel).',
    ),
    75 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ed',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ed',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 266,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 266 resultat(s) reels (result_count stocke le compte reel).',
    ),
    76 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ef',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ef',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 145,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 145 resultat(s) reels (result_count stocke le compte reel).',
    ),
    77 => 
    array (
      'route_path' => '/woerter/beginnend-mit/eg',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/eg',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 163,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 163 resultat(s) reels (result_count stocke le compte reel).',
    ),
    78 => 
    array (
      'route_path' => '/woerter/beginnend-mit/eh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/eh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 741,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 741 resultat(s) reels (result_count stocke le compte reel).',
    ),
    79 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ei',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ei',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 12207,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 12207 resultat(s) reels (result_count stocke le compte reel).',
    ),
    80 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ej',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ej',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 37,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 37 resultat(s) reels (result_count stocke le compte reel).',
    ),
    81 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ek',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ek',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 257,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 257 resultat(s) reels (result_count stocke le compte reel).',
    ),
    82 => 
    array (
      'route_path' => '/woerter/beginnend-mit/el',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/el',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1001,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1001 resultat(s) reels (result_count stocke le compte reel).',
    ),
    83 => 
    array (
      'route_path' => '/woerter/beginnend-mit/em',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/em',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1127,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1127 resultat(s) reels (result_count stocke le compte reel).',
    ),
    84 => 
    array (
      'route_path' => '/woerter/beginnend-mit/en',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/en',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6139,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6139 resultat(s) reels (result_count stocke le compte reel).',
    ),
    85 => 
    array (
      'route_path' => '/woerter/beginnend-mit/eo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/eo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 34,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 34 resultat(s) reels (result_count stocke le compte reel).',
    ),
    86 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ep',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ep',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 422,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 422 resultat(s) reels (result_count stocke le compte reel).',
    ),
    87 => 
    array (
      'route_path' => '/woerter/beginnend-mit/eq',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/eq',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 17,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 17 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/equ (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/equ corrigee separement (canonical -> /woerter/beginnend-mit/eq, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    88 => 
    array (
      'route_path' => '/woerter/beginnend-mit/er',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/er',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9286,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 9286 resultat(s) reels (result_count stocke le compte reel).',
    ),
    89 => 
    array (
      'route_path' => '/woerter/beginnend-mit/es',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/es',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 531,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 531 resultat(s) reels (result_count stocke le compte reel).',
    ),
    90 => 
    array (
      'route_path' => '/woerter/beginnend-mit/et',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/et',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 377,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 377 resultat(s) reels (result_count stocke le compte reel).',
    ),
    91 => 
    array (
      'route_path' => '/woerter/beginnend-mit/eu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/eu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 540,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 540 resultat(s) reels (result_count stocke le compte reel).',
    ),
    92 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ev',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ev',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 242,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 242 resultat(s) reels (result_count stocke le compte reel).',
    ),
    93 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ew',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ew',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 36,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 36 resultat(s) reels (result_count stocke le compte reel).',
    ),
    94 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ex',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ex',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1857,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1857 resultat(s) reels (result_count stocke le compte reel).',
    ),
    95 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ey',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ey',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6 resultat(s) reels (result_count stocke le compte reel).',
    ),
    96 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ez',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ez',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/ezz (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/ezz corrigee separement (canonical -> /woerter/beginnend-mit/ez, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    97 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fa',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fa',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3652,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3652 resultat(s) reels (result_count stocke le compte reel).',
    ),
    98 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fe',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fe',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4872,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4872 resultat(s) reels (result_count stocke le compte reel).',
    ),
    99 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2622,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2622 resultat(s) reels (result_count stocke le compte reel).',
    ),
    100 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fj',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fj',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 13,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 13 resultat(s) reels (result_count stocke le compte reel).',
    ),
    101 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4038,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4038 resultat(s) reels (result_count stocke le compte reel).',
    ),
    102 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2576,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2576 resultat(s) reels (result_count stocke le compte reel).',
    ),
    103 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4318,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4318 resultat(s) reels (result_count stocke le compte reel).',
    ),
    104 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1871,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1871 resultat(s) reels (result_count stocke le compte reel).',
    ),
    105 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 432,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 432 resultat(s) reels (result_count stocke le compte reel).',
    ),
    106 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 285,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 285 resultat(s) reels (result_count stocke le compte reel).',
    ),
    107 => 
    array (
      'route_path' => '/woerter/beginnend-mit/fü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/fü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 976,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 976 resultat(s) reels (result_count stocke le compte reel).',
    ),
    108 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ga',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ga',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2315,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2315 resultat(s) reels (result_count stocke le compte reel).',
    ),
    109 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ge',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ge',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21849,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 21849 resultat(s) reels (result_count stocke le compte reel).',
    ),
    110 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gf',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gf',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 12,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 12 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/gfr (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/gfr corrigee separement (canonical -> /woerter/beginnend-mit/gf, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    111 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 31,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 31 resultat(s) reels (result_count stocke le compte reel).',
    ),
    112 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 959,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 959 resultat(s) reels (result_count stocke le compte reel).',
    ),
    113 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2220,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2220 resultat(s) reels (result_count stocke le compte reel).',
    ),
    114 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gn',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gn',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 170,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 170 resultat(s) reels (result_count stocke le compte reel).',
    ),
    115 => 
    array (
      'route_path' => '/woerter/beginnend-mit/go',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/go',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1126,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1126 resultat(s) reels (result_count stocke le compte reel).',
    ),
    116 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4896,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4896 resultat(s) reels (result_count stocke le compte reel).',
    ),
    117 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gs',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gs',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 98,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 98 resultat(s) reels (result_count stocke le compte reel).',
    ),
    118 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1148,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1148 resultat(s) reels (result_count stocke le compte reel).',
    ),
    119 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/gwi (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/gwi corrigee separement (canonical -> /woerter/beginnend-mit/gw, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    120 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 171,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 171 resultat(s) reels (result_count stocke le compte reel).',
    ),
    121 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 379,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 379 resultat(s) reels (result_count stocke le compte reel).',
    ),
    122 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 179,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 179 resultat(s) reels (result_count stocke le compte reel).',
    ),
    123 => 
    array (
      'route_path' => '/woerter/beginnend-mit/gü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/gü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 235,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 235 resultat(s) reels (result_count stocke le compte reel).',
    ),
    124 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ha',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ha',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5862,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5862 resultat(s) reels (result_count stocke le compte reel).',
    ),
    125 => 
    array (
      'route_path' => '/woerter/beginnend-mit/he',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/he',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9638,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 9638 resultat(s) reels (result_count stocke le compte reel).',
    ),
    126 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5330,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5330 resultat(s) reels (result_count stocke le compte reel).',
    ),
    127 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hm',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hm',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel).',
    ),
    128 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ho',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ho',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4118,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4118 resultat(s) reels (result_count stocke le compte reel).',
    ),
    129 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel).',
    ),
    130 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1553,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1553 resultat(s) reels (result_count stocke le compte reel).',
    ),
    131 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 666,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 666 resultat(s) reels (result_count stocke le compte reel).',
    ),
    132 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 828,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 828 resultat(s) reels (result_count stocke le compte reel).',
    ),
    133 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 666,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 666 resultat(s) reels (result_count stocke le compte reel).',
    ),
    134 => 
    array (
      'route_path' => '/woerter/beginnend-mit/hü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/hü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 546,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 546 resultat(s) reels (result_count stocke le compte reel).',
    ),
    135 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ia',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ia',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 34,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 34 resultat(s) reels (result_count stocke le compte reel).',
    ),
    136 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ib',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ib',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 27,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 27 resultat(s) reels (result_count stocke le compte reel).',
    ),
    137 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ic',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ic',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 56,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 56 resultat(s) reels (result_count stocke le compte reel).',
    ),
    138 => 
    array (
      'route_path' => '/woerter/beginnend-mit/id',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/id',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 427,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 427 resultat(s) reels (result_count stocke le compte reel).',
    ),
    139 => 
    array (
      'route_path' => '/woerter/beginnend-mit/if',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/if',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/ift (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/ift corrigee separement (canonical -> /woerter/beginnend-mit/if, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    140 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ig',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ig',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 80,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 80 resultat(s) reels (result_count stocke le compte reel).',
    ),
    141 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ih',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ih',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 41,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 41 resultat(s) reels (result_count stocke le compte reel).',
    ),
    142 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ij',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ij',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/ijj (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/ijj corrigee separement (canonical -> /woerter/beginnend-mit/ij, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    143 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ik',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ik',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 60,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 60 resultat(s) reels (result_count stocke le compte reel).',
    ),
    144 => 
    array (
      'route_path' => '/woerter/beginnend-mit/il',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/il',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 266,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 266 resultat(s) reels (result_count stocke le compte reel).',
    ),
    145 => 
    array (
      'route_path' => '/woerter/beginnend-mit/im',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/im',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 933,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 933 resultat(s) reels (result_count stocke le compte reel).',
    ),
    146 => 
    array (
      'route_path' => '/woerter/beginnend-mit/in',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/in',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4868,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4868 resultat(s) reels (result_count stocke le compte reel).',
    ),
    147 => 
    array (
      'route_path' => '/woerter/beginnend-mit/io',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/io',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 119,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 119 resultat(s) reels (result_count stocke le compte reel).',
    ),
    148 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ip',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ip',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 15,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 15 resultat(s) reels (result_count stocke le compte reel).',
    ),
    149 => 
    array (
      'route_path' => '/woerter/beginnend-mit/iq',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/iq',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3 resultat(s) reels (result_count stocke le compte reel).',
    ),
    150 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ir',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ir',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 705,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 705 resultat(s) reels (result_count stocke le compte reel).',
    ),
    151 => 
    array (
      'route_path' => '/woerter/beginnend-mit/is',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/is',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 482,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 482 resultat(s) reels (result_count stocke le compte reel).',
    ),
    152 => 
    array (
      'route_path' => '/woerter/beginnend-mit/it',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/it',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 123,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 123 resultat(s) reels (result_count stocke le compte reel).',
    ),
    153 => 
    array (
      'route_path' => '/woerter/beginnend-mit/iv',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/iv',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 13,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 13 resultat(s) reels (result_count stocke le compte reel).',
    ),
    154 => 
    array (
      'route_path' => '/woerter/beginnend-mit/iw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/iw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/iwr (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/iwr corrigee separement (canonical -> /woerter/beginnend-mit/iw, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    155 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ix',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ix',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 14,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 14 resultat(s) reels (result_count stocke le compte reel).',
    ),
    156 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ja',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ja',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1215,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1215 resultat(s) reels (result_count stocke le compte reel).',
    ),
    157 => 
    array (
      'route_path' => '/woerter/beginnend-mit/je',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/je',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 313,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 313 resultat(s) reels (result_count stocke le compte reel).',
    ),
    158 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ji',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ji',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 87,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 87 resultat(s) reels (result_count stocke le compte reel).',
    ),
    159 => 
    array (
      'route_path' => '/woerter/beginnend-mit/jo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/jo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 432,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 432 resultat(s) reels (result_count stocke le compte reel).',
    ),
    160 => 
    array (
      'route_path' => '/woerter/beginnend-mit/js',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/js',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/jsc (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/jsc corrigee separement (canonical -> /woerter/beginnend-mit/js, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    161 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ju',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ju',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1230,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1230 resultat(s) reels (result_count stocke le compte reel).',
    ),
    162 => 
    array (
      'route_path' => '/woerter/beginnend-mit/jä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/jä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 182,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 182 resultat(s) reels (result_count stocke le compte reel).',
    ),
    163 => 
    array (
      'route_path' => '/woerter/beginnend-mit/jö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/jö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/jöd (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/jöd corrigee separement (canonical -> /woerter/beginnend-mit/jö, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    164 => 
    array (
      'route_path' => '/woerter/beginnend-mit/jü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/jü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 83,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 83 resultat(s) reels (result_count stocke le compte reel).',
    ),
    165 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ka',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ka',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7118,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7118 resultat(s) reels (result_count stocke le compte reel).',
    ),
    166 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ke',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ke',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1977,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1977 resultat(s) reels (result_count stocke le compte reel).',
    ),
    167 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 38,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 38 resultat(s) reels (result_count stocke le compte reel).',
    ),
    168 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ki',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ki',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2129,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2129 resultat(s) reels (result_count stocke le compte reel).',
    ),
    169 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3617,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3617 resultat(s) reels (result_count stocke le compte reel).',
    ),
    170 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kn',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kn',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2267,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2267 resultat(s) reels (result_count stocke le compte reel).',
    ),
    171 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ko',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ko',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7569,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7569 resultat(s) reels (result_count stocke le compte reel).',
    ),
    172 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4762,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4762 resultat(s) reels (result_count stocke le compte reel).',
    ),
    173 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ks',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ks',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3 resultat(s) reels (result_count stocke le compte reel).',
    ),
    174 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ku',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ku',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2950,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2950 resultat(s) reels (result_count stocke le compte reel).',
    ),
    175 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/kwa (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/kwa corrigee separement (canonical -> /woerter/beginnend-mit/kw, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    176 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ky',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ky',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 87,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 87 resultat(s) reels (result_count stocke le compte reel).',
    ),
    177 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kz',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kz',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/kzs (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/kzs corrigee separement (canonical -> /woerter/beginnend-mit/kz, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    178 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 605,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 605 resultat(s) reels (result_count stocke le compte reel).',
    ),
    179 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 575,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 575 resultat(s) reels (result_count stocke le compte reel).',
    ),
    180 => 
    array (
      'route_path' => '/woerter/beginnend-mit/kü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/kü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 762,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 762 resultat(s) reels (result_count stocke le compte reel).',
    ),
    181 => 
    array (
      'route_path' => '/woerter/beginnend-mit/la',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/la',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4487,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4487 resultat(s) reels (result_count stocke le compte reel).',
    ),
    182 => 
    array (
      'route_path' => '/woerter/beginnend-mit/lc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/lc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/lcd (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/lcd corrigee separement (canonical -> /woerter/beginnend-mit/lc, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    183 => 
    array (
      'route_path' => '/woerter/beginnend-mit/le',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/le',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4198,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4198 resultat(s) reels (result_count stocke le compte reel).',
    ),
    184 => 
    array (
      'route_path' => '/woerter/beginnend-mit/li',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/li',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2977,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2977 resultat(s) reels (result_count stocke le compte reel).',
    ),
    185 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ll',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ll',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/lla (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/lla corrigee separement (canonical -> /woerter/beginnend-mit/ll, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    186 => 
    array (
      'route_path' => '/woerter/beginnend-mit/lo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/lo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2294,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2294 resultat(s) reels (result_count stocke le compte reel).',
    ),
    187 => 
    array (
      'route_path' => '/woerter/beginnend-mit/lu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/lu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1349,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1349 resultat(s) reels (result_count stocke le compte reel).',
    ),
    188 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ly',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ly',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 192,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 192 resultat(s) reels (result_count stocke le compte reel).',
    ),
    189 => 
    array (
      'route_path' => '/woerter/beginnend-mit/lä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/lä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 679,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 679 resultat(s) reels (result_count stocke le compte reel).',
    ),
    190 => 
    array (
      'route_path' => '/woerter/beginnend-mit/lö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/lö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 472,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 472 resultat(s) reels (result_count stocke le compte reel).',
    ),
    191 => 
    array (
      'route_path' => '/woerter/beginnend-mit/lü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/lü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 539,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 539 resultat(s) reels (result_count stocke le compte reel).',
    ),
    192 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ma',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ma',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6015,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6015 resultat(s) reels (result_count stocke le compte reel).',
    ),
    193 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/mcc (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/mcc corrigee separement (canonical -> /woerter/beginnend-mit/mc, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    194 => 
    array (
      'route_path' => '/woerter/beginnend-mit/me',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/me',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3976,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3976 resultat(s) reels (result_count stocke le compte reel).',
    ),
    195 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5766,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5766 resultat(s) reels (result_count stocke le compte reel).',
    ),
    196 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mm',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mm',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/mmh (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/mmh corrigee separement (canonical -> /woerter/beginnend-mit/mm, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    197 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mn',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mn',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 36,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 36 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/mne (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/mne corrigee separement (canonical -> /woerter/beginnend-mit/mn, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    198 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3555,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3555 resultat(s) reels (result_count stocke le compte reel).',
    ),
    199 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2046,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2046 resultat(s) reels (result_count stocke le compte reel).',
    ),
    200 => 
    array (
      'route_path' => '/woerter/beginnend-mit/my',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/my',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 520,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 520 resultat(s) reels (result_count stocke le compte reel).',
    ),
    201 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 827,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 827 resultat(s) reels (result_count stocke le compte reel).',
    ),
    202 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 279,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 279 resultat(s) reels (result_count stocke le compte reel).',
    ),
    203 => 
    array (
      'route_path' => '/woerter/beginnend-mit/mü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/mü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 766,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 766 resultat(s) reels (result_count stocke le compte reel).',
    ),
    204 => 
    array (
      'route_path' => '/woerter/beginnend-mit/na',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/na',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5644,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5644 resultat(s) reels (result_count stocke le compte reel).',
    ),
    205 => 
    array (
      'route_path' => '/woerter/beginnend-mit/nd',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/nd',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel).',
    ),
    206 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ne',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ne',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2719,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2719 resultat(s) reels (result_count stocke le compte reel).',
    ),
    207 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ng',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ng',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3 resultat(s) reels (result_count stocke le compte reel).',
    ),
    208 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ni',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ni',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1659,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1659 resultat(s) reels (result_count stocke le compte reel).',
    ),
    209 => 
    array (
      'route_path' => '/woerter/beginnend-mit/no',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/no',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1635,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1635 resultat(s) reels (result_count stocke le compte reel).',
    ),
    210 => 
    array (
      'route_path' => '/woerter/beginnend-mit/nu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/nu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 915,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 915 resultat(s) reels (result_count stocke le compte reel).',
    ),
    211 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ny',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ny',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 103,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 103 resultat(s) reels (result_count stocke le compte reel).',
    ),
    212 => 
    array (
      'route_path' => '/woerter/beginnend-mit/nä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/nä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 484,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 484 resultat(s) reels (result_count stocke le compte reel).',
    ),
    213 => 
    array (
      'route_path' => '/woerter/beginnend-mit/nö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/nö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 179,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 179 resultat(s) reels (result_count stocke le compte reel).',
    ),
    214 => 
    array (
      'route_path' => '/woerter/beginnend-mit/nü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/nü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 98,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 98 resultat(s) reels (result_count stocke le compte reel).',
    ),
    215 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oa',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oa',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 9 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/oas (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/oas corrigee separement (canonical -> /woerter/beginnend-mit/oa, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    216 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ob',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ob',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1085,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1085 resultat(s) reels (result_count stocke le compte reel).',
    ),
    217 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 134,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 134 resultat(s) reels (result_count stocke le compte reel).',
    ),
    218 => 
    array (
      'route_path' => '/woerter/beginnend-mit/od',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/od',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 92,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 92 resultat(s) reels (result_count stocke le compte reel).',
    ),
    219 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oe',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oe',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5 resultat(s) reels (result_count stocke le compte reel).',
    ),
    220 => 
    array (
      'route_path' => '/woerter/beginnend-mit/of',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/of',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 400,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 400 resultat(s) reels (result_count stocke le compte reel).',
    ),
    221 => 
    array (
      'route_path' => '/woerter/beginnend-mit/og',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/og',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 24,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 24 resultat(s) reels (result_count stocke le compte reel).',
    ),
    222 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 156,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 156 resultat(s) reels (result_count stocke le compte reel).',
    ),
    223 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 10,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 10 resultat(s) reels (result_count stocke le compte reel).',
    ),
    224 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oj',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oj',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5 resultat(s) reels (result_count stocke le compte reel).',
    ),
    225 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ok',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ok',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 321,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 321 resultat(s) reels (result_count stocke le compte reel).',
    ),
    226 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ol',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ol',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 282,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 282 resultat(s) reels (result_count stocke le compte reel).',
    ),
    227 => 
    array (
      'route_path' => '/woerter/beginnend-mit/om',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/om',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 124,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 124 resultat(s) reels (result_count stocke le compte reel).',
    ),
    228 => 
    array (
      'route_path' => '/woerter/beginnend-mit/on',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/on',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 198,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 198 resultat(s) reels (result_count stocke le compte reel).',
    ),
    229 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 9,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 9 resultat(s) reels (result_count stocke le compte reel).',
    ),
    230 => 
    array (
      'route_path' => '/woerter/beginnend-mit/op',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/op',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 632,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 632 resultat(s) reels (result_count stocke le compte reel).',
    ),
    231 => 
    array (
      'route_path' => '/woerter/beginnend-mit/or',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/or',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1160,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1160 resultat(s) reels (result_count stocke le compte reel).',
    ),
    232 => 
    array (
      'route_path' => '/woerter/beginnend-mit/os',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/os',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 648,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 648 resultat(s) reels (result_count stocke le compte reel).',
    ),
    233 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ot',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ot',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 56,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 56 resultat(s) reels (result_count stocke le compte reel).',
    ),
    234 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ou',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ou',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 83,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 83 resultat(s) reels (result_count stocke le compte reel).',
    ),
    235 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ov',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ov',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 94,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 94 resultat(s) reels (result_count stocke le compte reel).',
    ),
    236 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ow',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ow',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel).',
    ),
    237 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ox',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ox',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 116,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 116 resultat(s) reels (result_count stocke le compte reel).',
    ),
    238 => 
    array (
      'route_path' => '/woerter/beginnend-mit/oz',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/oz',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 118,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 118 resultat(s) reels (result_count stocke le compte reel).',
    ),
    239 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pa',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pa',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4759,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4759 resultat(s) reels (result_count stocke le compte reel).',
    ),
    240 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/pcs (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/pcs corrigee separement (canonical -> /woerter/beginnend-mit/pc, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    241 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pe',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pe',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2633,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2633 resultat(s) reels (result_count stocke le compte reel).',
    ),
    242 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pf',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pf',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1624,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1624 resultat(s) reels (result_count stocke le compte reel).',
    ),
    243 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ph',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ph',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 981,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 981 resultat(s) reels (result_count stocke le compte reel).',
    ),
    244 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1718,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1718 resultat(s) reels (result_count stocke le compte reel).',
    ),
    245 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pk',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pk',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/pkw (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/pkw corrigee separement (canonical -> /woerter/beginnend-mit/pk, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    246 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2068,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2068 resultat(s) reels (result_count stocke le compte reel).',
    ),
    247 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pn',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pn',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 24,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 24 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/pne (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/pne corrigee separement (canonical -> /woerter/beginnend-mit/pn, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    248 => 
    array (
      'route_path' => '/woerter/beginnend-mit/po',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/po',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3153,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3153 resultat(s) reels (result_count stocke le compte reel).',
    ),
    249 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5646,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5646 resultat(s) reels (result_count stocke le compte reel).',
    ),
    250 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ps',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ps',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 201,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 201 resultat(s) reels (result_count stocke le compte reel).',
    ),
    251 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pt',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pt',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 35,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 35 resultat(s) reels (result_count stocke le compte reel).',
    ),
    252 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1635,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1635 resultat(s) reels (result_count stocke le compte reel).',
    ),
    253 => 
    array (
      'route_path' => '/woerter/beginnend-mit/py',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/py',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 215,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 215 resultat(s) reels (result_count stocke le compte reel).',
    ),
    254 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 141,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 141 resultat(s) reels (result_count stocke le compte reel).',
    ),
    255 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 153,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 153 resultat(s) reels (result_count stocke le compte reel).',
    ),
    256 => 
    array (
      'route_path' => '/woerter/beginnend-mit/pü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/pü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 90,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 90 resultat(s) reels (result_count stocke le compte reel).',
    ),
    257 => 
    array (
      'route_path' => '/woerter/beginnend-mit/qb',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/qb',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/qbi (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/qbi corrigee separement (canonical -> /woerter/beginnend-mit/qb, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    258 => 
    array (
      'route_path' => '/woerter/beginnend-mit/qi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/qi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel).',
    ),
    259 => 
    array (
      'route_path' => '/woerter/beginnend-mit/qo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/qo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/qop (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/qop corrigee separement (canonical -> /woerter/beginnend-mit/qo, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    260 => 
    array (
      'route_path' => '/woerter/beginnend-mit/qu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/qu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1930,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1930 resultat(s) reels (result_count stocke le compte reel).',
    ),
    261 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ra',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ra',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4367,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4367 resultat(s) reels (result_count stocke le compte reel).',
    ),
    262 => 
    array (
      'route_path' => '/woerter/beginnend-mit/re',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/re',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 8095,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 8095 resultat(s) reels (result_count stocke le compte reel).',
    ),
    263 => 
    array (
      'route_path' => '/woerter/beginnend-mit/rh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/rh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 304,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 304 resultat(s) reels (result_count stocke le compte reel).',
    ),
    264 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ri',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ri',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1727,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1727 resultat(s) reels (result_count stocke le compte reel).',
    ),
    265 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ro',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ro',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2027,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2027 resultat(s) reels (result_count stocke le compte reel).',
    ),
    266 => 
    array (
      'route_path' => '/woerter/beginnend-mit/rp',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/rp',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/rpg (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/rpg corrigee separement (canonical -> /woerter/beginnend-mit/rp, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    267 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ru',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ru',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2383,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2383 resultat(s) reels (result_count stocke le compte reel).',
    ),
    268 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ry',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ry',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/rye (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/rye corrigee separement (canonical -> /woerter/beginnend-mit/ry, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    269 => 
    array (
      'route_path' => '/woerter/beginnend-mit/rä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/rä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 606,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 606 resultat(s) reels (result_count stocke le compte reel).',
    ),
    270 => 
    array (
      'route_path' => '/woerter/beginnend-mit/rö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/rö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 349,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 349 resultat(s) reels (result_count stocke le compte reel).',
    ),
    271 => 
    array (
      'route_path' => '/woerter/beginnend-mit/rü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/rü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1135,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1135 resultat(s) reels (result_count stocke le compte reel).',
    ),
    272 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sa',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sa',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4057,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4057 resultat(s) reels (result_count stocke le compte reel).',
    ),
    273 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sb',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sb',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel).',
    ),
    274 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 17278,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 17278 resultat(s) reels (result_count stocke le compte reel).',
    ),
    275 => 
    array (
      'route_path' => '/woerter/beginnend-mit/se',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/se',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4688,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4688 resultat(s) reels (result_count stocke le compte reel).',
    ),
    276 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sf',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sf',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel).',
    ),
    277 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sg',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sg',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/sgr (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/sgr corrigee separement (canonical -> /woerter/beginnend-mit/sg, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    278 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 221,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 221 resultat(s) reels (result_count stocke le compte reel).',
    ),
    279 => 
    array (
      'route_path' => '/woerter/beginnend-mit/si',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/si',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2793,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2793 resultat(s) reels (result_count stocke le compte reel).',
    ),
    280 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sk',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sk',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 927,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 927 resultat(s) reels (result_count stocke le compte reel).',
    ),
    281 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 197,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 197 resultat(s) reels (result_count stocke le compte reel).',
    ),
    282 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sm',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sm',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 103,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 103 resultat(s) reels (result_count stocke le compte reel).',
    ),
    283 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sn',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sn',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 91,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 91 resultat(s) reels (result_count stocke le compte reel).',
    ),
    284 => 
    array (
      'route_path' => '/woerter/beginnend-mit/so',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/so',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2538,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2538 resultat(s) reels (result_count stocke le compte reel).',
    ),
    285 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sp',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sp',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5764,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5764 resultat(s) reels (result_count stocke le compte reel).',
    ),
    286 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sq',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sq',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 13,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 13 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/squ (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/squ corrigee separement (canonical -> /woerter/beginnend-mit/sq, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    287 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/sra (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/sra corrigee separement (canonical -> /woerter/beginnend-mit/sr, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    288 => 
    array (
      'route_path' => '/woerter/beginnend-mit/st',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/st',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 12373,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 12373 resultat(s) reels (result_count stocke le compte reel).',
    ),
    289 => 
    array (
      'route_path' => '/woerter/beginnend-mit/su',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/su',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2152,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2152 resultat(s) reels (result_count stocke le compte reel).',
    ),
    290 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sv',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sv',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/sve (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/sve corrigee separement (canonical -> /woerter/beginnend-mit/sv, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    291 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 119,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 119 resultat(s) reels (result_count stocke le compte reel).',
    ),
    292 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 768,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 768 resultat(s) reels (result_count stocke le compte reel).',
    ),
    293 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sz',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sz',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 111,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 111 resultat(s) reels (result_count stocke le compte reel).',
    ),
    294 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 792,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 792 resultat(s) reels (result_count stocke le compte reel).',
    ),
    295 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 118,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 118 resultat(s) reels (result_count stocke le compte reel).',
    ),
    296 => 
    array (
      'route_path' => '/woerter/beginnend-mit/sü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/sü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 713,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 713 resultat(s) reels (result_count stocke le compte reel).',
    ),
    297 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ta',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ta',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3878,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3878 resultat(s) reels (result_count stocke le compte reel).',
    ),
    298 => 
    array (
      'route_path' => '/woerter/beginnend-mit/te',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/te',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2891,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2891 resultat(s) reels (result_count stocke le compte reel).',
    ),
    299 => 
    array (
      'route_path' => '/woerter/beginnend-mit/th',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/th',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 751,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 751 resultat(s) reels (result_count stocke le compte reel).',
    ),
    300 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ti',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ti',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1611,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1611 resultat(s) reels (result_count stocke le compte reel).',
    ),
    301 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tj',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tj',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 8,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 8 resultat(s) reels (result_count stocke le compte reel).',
    ),
    302 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tm',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tm',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/tme (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/tme corrigee separement (canonical -> /woerter/beginnend-mit/tm, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    303 => 
    array (
      'route_path' => '/woerter/beginnend-mit/to',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/to',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2672,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2672 resultat(s) reels (result_count stocke le compte reel).',
    ),
    304 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5255,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5255 resultat(s) reels (result_count stocke le compte reel).',
    ),
    305 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ts',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ts',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 177,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 177 resultat(s) reels (result_count stocke le compte reel).',
    ),
    306 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1144,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1144 resultat(s) reels (result_count stocke le compte reel).',
    ),
    307 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 75,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 75 resultat(s) reels (result_count stocke le compte reel).',
    ),
    308 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ty',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ty',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 295,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 295 resultat(s) reels (result_count stocke le compte reel).',
    ),
    309 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 335,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 335 resultat(s) reels (result_count stocke le compte reel).',
    ),
    310 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 299,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 299 resultat(s) reels (result_count stocke le compte reel).',
    ),
    311 => 
    array (
      'route_path' => '/woerter/beginnend-mit/tü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/tü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 517,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 517 resultat(s) reels (result_count stocke le compte reel).',
    ),
    312 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ub',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ub',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 25,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 25 resultat(s) reels (result_count stocke le compte reel).',
    ),
    313 => 
    array (
      'route_path' => '/woerter/beginnend-mit/uc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/uc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/uch (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/uch corrigee separement (canonical -> /woerter/beginnend-mit/uc, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    314 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ud',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ud',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel).',
    ),
    315 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ue',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ue',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 11,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 11 resultat(s) reels (result_count stocke le compte reel).',
    ),
    316 => 
    array (
      'route_path' => '/woerter/beginnend-mit/uf',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/uf',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 56,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 56 resultat(s) reels (result_count stocke le compte reel).',
    ),
    317 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ug',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ug',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 19,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 19 resultat(s) reels (result_count stocke le compte reel).',
    ),
    318 => 
    array (
      'route_path' => '/woerter/beginnend-mit/uh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/uh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 50,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 50 resultat(s) reels (result_count stocke le compte reel).',
    ),
    319 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ui',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ui',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6 resultat(s) reels (result_count stocke le compte reel).',
    ),
    320 => 
    array (
      'route_path' => '/woerter/beginnend-mit/uk',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/uk',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 36,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 36 resultat(s) reels (result_count stocke le compte reel).',
    ),
    321 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ul',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ul',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 189,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 189 resultat(s) reels (result_count stocke le compte reel).',
    ),
    322 => 
    array (
      'route_path' => '/woerter/beginnend-mit/um',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/um',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5931,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5931 resultat(s) reels (result_count stocke le compte reel).',
    ),
    323 => 
    array (
      'route_path' => '/woerter/beginnend-mit/un',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/un',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 12108,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 12108 resultat(s) reels (result_count stocke le compte reel).',
    ),
    324 => 
    array (
      'route_path' => '/woerter/beginnend-mit/up',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/up',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 69,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 69 resultat(s) reels (result_count stocke le compte reel).',
    ),
    325 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ur',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ur',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1076,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1076 resultat(s) reels (result_count stocke le compte reel).',
    ),
    326 => 
    array (
      'route_path' => '/woerter/beginnend-mit/us',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/us',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 62,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 62 resultat(s) reels (result_count stocke le compte reel).',
    ),
    327 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ut',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ut',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 69,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 69 resultat(s) reels (result_count stocke le compte reel).',
    ),
    328 => 
    array (
      'route_path' => '/woerter/beginnend-mit/uv',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/uv',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 13,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 13 resultat(s) reels (result_count stocke le compte reel).',
    ),
    329 => 
    array (
      'route_path' => '/woerter/beginnend-mit/uw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/uw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/uwa (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/uwa corrigee separement (canonical -> /woerter/beginnend-mit/uw, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    330 => 
    array (
      'route_path' => '/woerter/beginnend-mit/uz',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/uz',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 29,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 29 resultat(s) reels (result_count stocke le compte reel).',
    ),
    331 => 
    array (
      'route_path' => '/woerter/beginnend-mit/va',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/va',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 646,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 646 resultat(s) reels (result_count stocke le compte reel).',
    ),
    332 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ve',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ve',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21903,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 21903 resultat(s) reels (result_count stocke le compte reel).',
    ),
    333 => 
    array (
      'route_path' => '/woerter/beginnend-mit/vi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/vi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1692,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1692 resultat(s) reels (result_count stocke le compte reel).',
    ),
    334 => 
    array (
      'route_path' => '/woerter/beginnend-mit/vl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/vl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 19,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 19 resultat(s) reels (result_count stocke le compte reel).',
    ),
    335 => 
    array (
      'route_path' => '/woerter/beginnend-mit/vo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/vo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6842,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6842 resultat(s) reels (result_count stocke le compte reel).',
    ),
    336 => 
    array (
      'route_path' => '/woerter/beginnend-mit/vr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/vr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel).',
    ),
    337 => 
    array (
      'route_path' => '/woerter/beginnend-mit/vu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/vu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 174,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 174 resultat(s) reels (result_count stocke le compte reel).',
    ),
    338 => 
    array (
      'route_path' => '/woerter/beginnend-mit/vä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/vä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 32,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 32 resultat(s) reels (result_count stocke le compte reel).',
    ),
    339 => 
    array (
      'route_path' => '/woerter/beginnend-mit/vö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/vö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 129,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 129 resultat(s) reels (result_count stocke le compte reel).',
    ),
    340 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wa',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wa',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4449,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4449 resultat(s) reels (result_count stocke le compte reel).',
    ),
    341 => 
    array (
      'route_path' => '/woerter/beginnend-mit/we',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/we',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 8211,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 8211 resultat(s) reels (result_count stocke le compte reel).',
    ),
    342 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 41,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 41 resultat(s) reels (result_count stocke le compte reel).',
    ),
    343 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3834,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3834 resultat(s) reels (result_count stocke le compte reel).',
    ),
    344 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/wla (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/wla corrigee separement (canonical -> /woerter/beginnend-mit/wl, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    345 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1806,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1806 resultat(s) reels (result_count stocke le compte reel).',
    ),
    346 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wr',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wr',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 119,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 119 resultat(s) reels (result_count stocke le compte reel).',
    ),
    347 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1282,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1282 resultat(s) reels (result_count stocke le compte reel).',
    ),
    348 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ww',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ww',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/www (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/www corrigee separement (canonical -> /woerter/beginnend-mit/ww, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    349 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/wya (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/wya corrigee separement (canonical -> /woerter/beginnend-mit/wy, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    350 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 559,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 559 resultat(s) reels (result_count stocke le compte reel).',
    ),
    351 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 175,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 175 resultat(s) reels (result_count stocke le compte reel).',
    ),
    352 => 
    array (
      'route_path' => '/woerter/beginnend-mit/wü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/wü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 524,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 524 resultat(s) reels (result_count stocke le compte reel).',
    ),
    353 => 
    array (
      'route_path' => '/woerter/beginnend-mit/xa',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/xa',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 77,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 77 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/xan (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/xan corrigee separement (canonical -> /woerter/beginnend-mit/xa, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    354 => 
    array (
      'route_path' => '/woerter/beginnend-mit/xe',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/xe',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 213,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 213 resultat(s) reels (result_count stocke le compte reel).',
    ),
    355 => 
    array (
      'route_path' => '/woerter/beginnend-mit/xh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/xh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/xho (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/xho corrigee separement (canonical -> /woerter/beginnend-mit/xh, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    356 => 
    array (
      'route_path' => '/woerter/beginnend-mit/xi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/xi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel).',
    ),
    357 => 
    array (
      'route_path' => '/woerter/beginnend-mit/xo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/xo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 3,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 3 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/xoa (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/xoa corrigee separement (canonical -> /woerter/beginnend-mit/xo, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    358 => 
    array (
      'route_path' => '/woerter/beginnend-mit/xy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/xy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 76,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 76 resultat(s) reels (result_count stocke le compte reel).',
    ),
    359 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ya',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ya',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 50,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 50 resultat(s) reels (result_count stocke le compte reel).',
    ),
    360 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ye',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ye',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 12,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 12 resultat(s) reels (result_count stocke le compte reel).',
    ),
    361 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5 resultat(s) reels (result_count stocke le compte reel).',
    ),
    362 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/yli (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/yli corrigee separement (canonical -> /woerter/beginnend-mit/yl, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    363 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yn',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yn',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/yng (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/yng corrigee separement (canonical -> /woerter/beginnend-mit/yn, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    364 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 35,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 35 resultat(s) reels (result_count stocke le compte reel).',
    ),
    365 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yp',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yp',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 11,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 11 resultat(s) reels (result_count stocke le compte reel).',
    ),
    366 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ys',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ys',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/yso (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/yso corrigee separement (canonical -> /woerter/beginnend-mit/ys, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    367 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yt',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yt',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 20,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 20 resultat(s) reels (result_count stocke le compte reel).',
    ),
    368 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 16,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 16 resultat(s) reels (result_count stocke le compte reel).',
    ),
    369 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/yär (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/yär corrigee separement (canonical -> /woerter/beginnend-mit/yä, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    370 => 
    array (
      'route_path' => '/woerter/beginnend-mit/yü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/yü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/yür (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/yür corrigee separement (canonical -> /woerter/beginnend-mit/yü, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    371 => 
    array (
      'route_path' => '/woerter/beginnend-mit/za',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/za',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1306,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1306 resultat(s) reels (result_count stocke le compte reel).',
    ),
    372 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ze',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ze',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4632,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4632 resultat(s) reels (result_count stocke le compte reel).',
    ),
    373 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/zhu (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/zhu corrigee separement (canonical -> /woerter/beginnend-mit/zh, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    374 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zi',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zi',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2114,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2114 resultat(s) reels (result_count stocke le compte reel).',
    ),
    375 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/zlo (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/zlo corrigee separement (canonical -> /woerter/beginnend-mit/zl, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    376 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zm',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zm',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 6,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 6 resultat(s) reels (result_count stocke le compte reel).',
    ),
    377 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zn',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zn',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel).',
    ),
    378 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 557,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 557 resultat(s) reels (result_count stocke le compte reel).',
    ),
    379 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 8175,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 8175 resultat(s) reels (result_count stocke le compte reel).',
    ),
    380 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zv',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zv',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/zvi (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/zvi corrigee separement (canonical -> /woerter/beginnend-mit/zv, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    381 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zw',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zw',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1989,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1989 resultat(s) reels (result_count stocke le compte reel).',
    ),
    382 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zy',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zy',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 379,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 379 resultat(s) reels (result_count stocke le compte reel).',
    ),
    383 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zä',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zä',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 332,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 332 resultat(s) reels (result_count stocke le compte reel).',
    ),
    384 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 120,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 120 resultat(s) reels (result_count stocke le compte reel).',
    ),
    385 => 
    array (
      'route_path' => '/woerter/beginnend-mit/zü',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/zü',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 334,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 334 resultat(s) reels (result_count stocke le compte reel).',
    ),
    386 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äb',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äb',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 69,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 69 resultat(s) reels (result_count stocke le compte reel).',
    ),
    387 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 42,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 42 resultat(s) reels (result_count stocke le compte reel).',
    ),
    388 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äd',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äd',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 72,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 72 resultat(s) reels (result_count stocke le compte reel).',
    ),
    389 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äf',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äf',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 39,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 39 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/äff (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/äff corrigee separement (canonical -> /woerter/beginnend-mit/äf, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    390 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äg',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äg',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 43,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 43 resultat(s) reels (result_count stocke le compte reel).',
    ),
    391 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 89,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 89 resultat(s) reels (result_count stocke le compte reel).',
    ),
    392 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äk',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äk',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/äks (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/äks corrigee separement (canonical -> /woerter/beginnend-mit/äk, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    393 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 41,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 41 resultat(s) reels (result_count stocke le compte reel).',
    ),
    394 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äm',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äm',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 11,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 11 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/ämt (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/ämt corrigee separement (canonical -> /woerter/beginnend-mit/äm, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    395 => 
    array (
      'route_path' => '/woerter/beginnend-mit/än',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/än',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 109,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 109 resultat(s) reels (result_count stocke le compte reel).',
    ),
    396 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äo',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äo',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 29,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 29 resultat(s) reels (result_count stocke le compte reel).',
    ),
    397 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äp',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äp',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 21,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 21 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/äpf (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/äpf corrigee separement (canonical -> /woerter/beginnend-mit/äp, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    398 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äq',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äq',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 72,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 72 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/äqu (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/äqu corrigee separement (canonical -> /woerter/beginnend-mit/äq, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    399 => 
    array (
      'route_path' => '/woerter/beginnend-mit/är',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/är',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 188,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 188 resultat(s) reels (result_count stocke le compte reel).',
    ),
    400 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äs',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äs',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 118,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 118 resultat(s) reels (result_count stocke le compte reel).',
    ),
    401 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ät',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ät',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 117,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 117 resultat(s) reels (result_count stocke le compte reel).',
    ),
    402 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äu',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äu',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 106,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 106 resultat(s) reels (result_count stocke le compte reel).',
    ),
    403 => 
    array (
      'route_path' => '/woerter/beginnend-mit/äx',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/äx',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/äxt (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/äxt corrigee separement (canonical -> /woerter/beginnend-mit/äx, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    404 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öb',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öb',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/öbs (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/öbs corrigee separement (canonical -> /woerter/beginnend-mit/öb, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    405 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öc',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öc',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 4,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 4 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/öch (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/öch corrigee separement (canonical -> /woerter/beginnend-mit/öc, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    406 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öd',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öd',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 71,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 71 resultat(s) reels (result_count stocke le compte reel).',
    ),
    407 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öf',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öf',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 65,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 65 resultat(s) reels (result_count stocke le compte reel).',
    ),
    408 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 38,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 38 resultat(s) reels (result_count stocke le compte reel).',
    ),
    409 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ök',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ök',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 117,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 117 resultat(s) reels (result_count stocke le compte reel).',
    ),
    410 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öl',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öl',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 382,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 382 resultat(s) reels (result_count stocke le compte reel).',
    ),
    411 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ön',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ön',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 30,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 30 resultat(s) reels (result_count stocke le compte reel).',
    ),
    412 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ör',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ör',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 42,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 42 resultat(s) reels (result_count stocke le compte reel).',
    ),
    413 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ös',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ös',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 73,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 73 resultat(s) reels (result_count stocke le compte reel).',
    ),
    414 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öt',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öt',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/ötz (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/ötz corrigee separement (canonical -> /woerter/beginnend-mit/öt, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    415 => 
    array (
      'route_path' => '/woerter/beginnend-mit/öö',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/öö',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 2,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 2 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/ööm (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/ööm corrigee separement (canonical -> /woerter/beginnend-mit/öö, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    416 => 
    array (
      'route_path' => '/woerter/beginnend-mit/üb',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/üb',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 5029,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 5029 resultat(s) reels (result_count stocke le compte reel).',
    ),
    417 => 
    array (
      'route_path' => '/woerter/beginnend-mit/üh',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/üh',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 1,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 1 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/ühr (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/ühr corrigee separement (canonical -> /woerter/beginnend-mit/üh, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
    418 => 
    array (
      'route_path' => '/woerter/beginnend-mit/ül',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/ül',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 7,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 7 resultat(s) reels (result_count stocke le compte reel).',
    ),
    419 => 
    array (
      'route_path' => '/woerter/beginnend-mit/üp',
      'family' => 'word_list_commencant',
      'robots' => 'index,follow',
      'canonical_path' => '/woerter/beginnend-mit/üp',
      'sitemap_fragment' => 'starts-0002',
      'result_count' => 18,
      'notes' => 'Palier 2 lettres de word_list_commencant (D-DE-024), symetrique au palier 1 lettre (D-DE-017) et 3 lettres (D-DE-019) deja indexes. Lien interne reel depuis CHAQUE mot admis de longueur > 2 commencant par ces 2 lettres (App\\Search\\RelationsFinder::relatedSearches(), categorie startsWith a 3 lettres redirige vers ce palier via PrefixExtensionLinksBuilder). 18 resultat(s) reels (result_count stocke le compte reel). GAGNANTE d\'un doublon de contenu exact avec /woerter/beginnend-mit/üpp (3 lettres, deja indexee D-DE-019) -- regle de priorite D-041 (scripts/lib/seo_duplicate_priority.php) : la forme la plus courte gagne. /woerter/beginnend-mit/üpp corrigee separement (canonical -> /woerter/beginnend-mit/üp, scripts/seo-batches/prefix3-2026-08-30.php).',
    ),
  ),
);
