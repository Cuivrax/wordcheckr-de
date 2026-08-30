<?php

declare(strict_types=1);

/**
 * Precalcule un SOUS-ENSEMBLE des comptes de maillage interne dans list_counts
 * (storage/dictionary_de.sqlite) -- adaptation allemande de scripts/build_explore_hub_counts.php
 * (depot francais cousin, D-022 a D-041 pour l'historique complet de chaque list_type).
 *
 * CE PREMIER LOT CONSTRUIT SEULEMENT 5 DES 19 list_type DU SCHEMA (voir schema.sql, CHECK sur
 * list_counts.list_type -- les 19 noms sont herites tels quels du schema francais, D-DE-003
 * n'a rien change a cette table) :
 *
 *   'length'       (14 lignes)  /woerter (hub) -- section "Nach Laenge", DEJA consommee par
 *                                ExploreHubBuilder (App\Search\), qui rendait une section VIDE
 *                                depuis le debut (0 ligne en base) malgre les 14 pages
 *                                /woerter/{N}-buchstaben deja indexees (D-DE-013).
 *   'start'        (29 lignes)  /woerter -- section "Beginnend Mit". Alimente aussi les liens
 *                                internes DEPUIS le hub VERS la famille word_list_commencant
 *                                (29 URL, deja indexee, D-DE-017).
 *   'end'          (29 lignes)  /woerter -- section "Endend Mit". Alimente les liens internes
 *                                depuis le hub vers /woerter/endend-mit/{1 lettre} -- voir la
 *                                LIMITE DOCUMENTEE ci-dessous (asymetrie D-DE-017), pas la meme
 *                                situation que 'start'.
 *   'length_start' (jusqu'a 14 x 29 = 406 lignes)  alimente App\Search\LengthLinksBuilder
 *                                (byStart) : depuis une page /woerter/{N}-buchstaben (deja
 *                                indexee), lien vers /woerter/{N}-buchstaben/beginnend-mit/{X}
 *                                (famille COMBINEE longueur+beginnend-mit, PAS ENCORE ouverte a
 *                                l'indexation -- D-DE-017 l'avait explicitement mesuree et
 *                                fermee faute de maillage entrant reel : "list_counts vide -- 0
 *                                ligne"). Ce lot fournit exactement le maillage qui manquait ;
 *                                la decision d'ouvrir cette famille a l'indexation reste un
 *                                choix SEO distinct, non pris ici (hors perimetre de cet agent).
 *   'length_end'   (jusqu'a 14 x 29 = 406 lignes)  meme principe que 'length_start', byEnd,
 *                                vers /woerter/{N}-buchstaben/endend-mit/{X} -- MEME LIMITE
 *                                d'asymetrie qu'expliquee ci-dessous pour 'end' seul.
 *
 * LIMITE DOCUMENTEE, ASSUMEE, PAS UN BUG (asymetrie beginnend-mit/endend-mit, D-DE-017) :
 * App\Search\ExploreHubBuilder et App\Search\LengthLinksBuilder (herites tels quels du depot
 * francais, ou "commencant"/"terminant" sont TOUS LES DEUX a une seule lettre par construction)
 * generent des liens 'end'/'length_end' a UNE SEULE lettre ("endend-mit/{1 lettre}"). Or D-DE-017
 * a mesure et documente que seule "endend-mit/s" (parmi les 29 variantes a 1 lettre) a un lien
 * entrant reel independant de ce lot -- la famille REELLEMENT indexee cote allemand est
 * word_list_terminant a DEUX lettres (455 URL, /woerter/endend-mit/{2 lettres}). Consequence
 * pour CE lot precis :
 *   - 'end' : les 29 liens rendent la section "Endend Mit" du hub non vide (l'objectif direct de
 *     cette tache), mais 28 de ces 29 URL cibles ne sont actuellement dans AUCUNE famille SEO
 *     indexee -- routables (WordListFilters::fromPath() les resout, la page rend du contenu
 *     reel), mais noindex par defaut (D-005 : "aucune route n'est indexable par defaut"). Un
 *     lien interne vers une page noindex n'est jamais nuisible en soi (pas un signal SEO
 *     negatif), mais ce n'est PAS la meme chose que "debloquer la famille endend-mit deja
 *     indexee" -- cette famille-la est a 2 lettres, un list_type/bigramme distinct, hors
 *     perimetre de ce lot (ajouter un nouveau list_type exigerait de modifier schema.sql,
 *     fichier partage, hors autorite de cet agent sans validation explicite de la session
 *     principale).
 *   - 'length_end' : meme limite, mais SANS consequence pratique ici -- la famille combinee
 *     longueur+endend-mit (a 1 lettre OU a 2) n'est de toute facon PAS ENCORE indexee, quelle
 *     que soit la granularite. La decision de granularite (1 ou 2 lettres) pour CETTE famille
 *     combinee reste entierement ouverte pour la passe SEO future, ce lot ne la prejuge pas.
 * Aucune correction de ExploreHubBuilder/LengthLinksBuilder n'est faite ici : ces classes
 * restent le porte-parole exact de ce qui EST dans list_counts, pas de ce qui DEVRAIT y etre --
 * signale explicitement plutot que corrige a la volee, pour que la decision de granularite reste
 * une decision SEO explicite et tracee, pas un choix implicite d'un script de precalcul.
 *
 * NON CONSTRUITS DANS CE LOT, list_type par list_type, avec la raison PRECISE (pas "par
 * prudence") -- 0 ligne inseree, table CHECK de schema.sql deja prete pour les recevoir plus tard
 * sans migration :
 *   'length_with'         6 861 combinaisons mesurees (D-DE-017) mais maillage EPARPILLE (pas un
 *                          entonnoir propre palier par palier comme le francais D-029/030/031) --
 *                          la tache recue exclut explicitement ce type ("pas un entonnoir propre
 *                          par palier ... explicitement exclu par la tache recue").
 *   'start_end'            combinaison beginnend-mit+endend-mit (1+1 lettre) mesuree a 690
 *                          combinaisons realisees mais UNE SEULE page reellement liee (home.php,
 *                          D-DE-017) -- 689 orphelines, aucun maillage reel a debloquer avec les
 *                          donnees actuelles tant que ni le palier 3-lettres commencant ni un
 *                          maillage dedie n'existent.
 *   'length_with_position' famille "position" : AUCUN lien entrant reel emis par
 *                          RelationsFinder::relatedSearches() cote allemand (D-DE-017,
 *                          "position : 0 lien entrant reel") -- construire ce type maintenant
 *                          produirait des comptes exacts mais BRANCHES SUR RIEN, la meme erreur
 *                          que D-028bis a deja identifiee et corrigee cote francais (ne jamais
 *                          ouvrir/preparer une famille sans verifier le maillage entrant REEL
 *                          d'abord). PositionLinksBuilder existe deja (porte du depot francais)
 *                          mais rien ne l'appelle encore depuis une page allemande reelle.
 *   'length_avec_sans'     jamais mesure cote allemand, combinatoire lourde (26 x 25 x 14 = 9 100
 *                          lignes max cote francais) -- aucune demande, aucun maillage mesure.
 *   'length_start_end'     famille commencant+terminant AVEC longueur (D-027 cote francais) --
 *                          jamais mesuree cote allemand, depend de 'start_end' ci-dessus (non
 *                          construit non plus), et LengthLinksBuilder applique deja des
 *                          exclusions de doublons SPECIFIQUES AU FRANCAIS pour ce type
 *                          (DUPLICATE_START_END_KEYS, 52 paires calculees sur storage/
 *                          dictionary_fr.sqlite, jamais revalidees sur les donnees allemandes) --
 *                          les construire sans d'abord recalculer ces listes pour l'allemand
 *                          produirait des FAUX POSITIFS/NEGATIFS de deduplication silencieux.
 *                          Voir la note "RISQUE TROUVE EN COURS DE ROUTE" plus bas.
 *   'length_with_pair'/    paliers 2/3 de l'entonnoir "avec" (D-030/D-031 cote francais) --
 *   'length_with_triple'   aucune famille "avec" a 1 lettre meme n'est encore ouverte cote
 *                          allemand (voir 'length_with' ci-dessus), construire les paliers 2/3
 *                          avant le palier 1 n'aurait aucun consommateur reel.
 *   'start_end_with',      maillage commencant+terminant+avec, commencant+avec (D-033/D-034 cote
 *   'start_with'           francais) -- meme dependance non satisfaite que 'length_with_pair'
 *                          ci-dessus (aucune famille "avec" ouverte cote allemand).
 *   'prefix2'/'prefix3'/   entonnoir prefixe/suffixe multi-lettres (dimensionnement francais du
 *   'prefix4'/'suffix2'/   2026-08-18) -- jamais mesure cote allemand, aucune classe consommatrice
 *   'suffix3'/'suffix4'    (*LinksBuilder) portee cote allemand a ce jour.
 *
 * RISQUE TROUVE EN COURS DE ROUTE, SIGNALE PLUTOT QUE CORRIGE (hors perimetre de cette tache,
 * app/Search/ reste dans le perimetre data-engine mais les constantes ci-dessous exigent un vrai
 * recalcul sur les donnees allemandes, pas une simple lecture) :
 * App\Search\LengthLinksBuilder::DUPLICATE_START_END_KEYS, ::EXTERNAL_DUPLICATE_WITH_KEYS,
 * App\Search\LetterCombinedLinksBuilder::EXTERNAL_DUPLICATE_KEYS et
 * App\Search\PositionLinksBuilder::EXTERNAL_DUPLICATE_KEYS contiennent des listes de paires
 * FIGEES, calculees sur storage/dictionary_fr.sqlite (838 180 termes francais), copiees telles
 * quelles lors du portage du depot (git archive, D-DE note de portee). Ces listes ne
 * s'appliquent PAS aux donnees allemandes (mots differents, paires start:end differentes).
 * SANS CONSEQUENCE pour ce lot precis : aucune des deux constantes concernees
 * (DUPLICATE_START_END_KEYS/length_start_end, EXTERNAL_DUPLICATE_WITH_KEYS/length_with) n'est
 * lue par le chemin 'length_start'/'length_end' que ce script peuple (verifie directement dans
 * LengthLinksBuilder::build() : ces deux constantes ne sont referencees que dans les branches
 * 'length_start_end' et 'length_with' du switch, jamais 'length_start'/'length_end'). A
 * RECALCULER pour l'allemand AVANT toute construction future de 'length_with', 'length_start_end'
 * ou 'start_end' -- sans quoi LengthLinksBuilder/LetterCombinedLinksBuilder/PositionLinksBuilder
 * dedupliqueraient silencieusement les mauvaises pages (ou aucune).
 *
 * Alphabet NON borne a A-Z : contrairement au francais (accents retires a la normalisation,
 * D-009), Ä/Ö/Ü sont des lettres allemandes a part entiere (D-DE-002) et apparaissent dans
 * `normalized`/`reversed` telles quelles. Aucune des 5 requetes ci-dessous ne suppose un alphabet
 * A-Z : toutes utilisent GROUP BY sur substr()/length() directement en SQL (jamais un tableau PHP
 * indexe A-Z, jamais str_split()/strrev() -- ces deux fonctions sont BYTE-orientees et
 * couperaient Ä/Ö/Ü, codees sur deux octets UTF-8 chacune, voir app/Search/Normalizer.php).
 * SQLite compte substr()/length() PAR CARACTERE sur les colonnes TEXT (documentation SQLite,
 * deja verifie et documente dans schema.sql pour idx_terms_startletter_endletter_normalized) :
 * aucun correctif necessaire ici, verifie en direct sur les 29 lettres reelles (26 + Ä/Ö/Ü),
 * voir le rapport de cette tache pour les comptes.
 *
 * Mesure (storage/dictionary_de.sqlite, 590 856 lignes, EXPLAIN QUERY PLAN + timing reels,
 * PHP 8.4.24 pdo_sqlite, machine de build locale -- offline uniquement, jamais au runtime) :
 *   length         SCAN ... USING COVERING INDEX idx_terms_length_reversed                 115 ms,  14 lignes
 *   start          SCAN ... USING COVERING INDEX idx_terms_startletter_endletter_normalized 133 ms,  29 lignes
 *   end            SCAN ... USING COVERING INDEX idx_terms_length_reversed + TEMP B-TREE    663 ms,  29 lignes
 *   length_start   SCAN ... USING COVERING INDEX idx_terms_length_normalized + TEMP B-TREE  951 ms, 401 lignes
 *   length_end     SCAN ... USING COVERING INDEX idx_terms_length_reversed + TEMP B-TREE    905 ms, 353 lignes
 * Toutes via un index couvrant (jamais un SCAN TABLE nu), le TEMP B-TREE porte sur le GROUP BY
 * (pas d'index sur l'expression substr() elle-meme, meme limite structurelle que le depot
 * francais -- voir l'entete de scripts/build_explore_hub_counts.php) -- hors ligne uniquement,
 * ~2,8 s cumulees pour les 5 requetes, sans aucun rapport avec le budget TTFB runtime (CLAUDE.md).
 *
 * Idempotent : peut etre relance apres chaque reconstruction de storage/dictionary_de.sqlite
 * (scripts/import_de.py) sans effet de bord -- DROP + CREATE (DDL identique a schema.sql, les 19
 * list_type restent tous acceptes par la CHECK constraint) + INSERT en une transaction, puis
 * ANALYZE (D-021, herite : toute modification de table/index doit etre suivie d'ANALYZE dans la
 * MEME operation).
 *
 * Usage : php scripts/build_explore_hub_counts_de.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "scripts/build_explore_hub_counts_de.php ne s'execute qu'en CLI, hors ligne.\n");
    exit(1);
}

$root = dirname(__DIR__);
$dbPath = getenv('SCRABBLE_DICTIONARY_DB_PATH') ?: $root . '/storage/dictionary_de.sqlite';

if (!is_file($dbPath)) {
    fwrite(STDERR, "dictionnaire introuvable : {$dbPath}\n");
    exit(1);
}

// Lecture-ecriture ASSUMEE ici (hors ligne uniquement) : le runtime PHP (app/Database/
// Connection.php) ouvre toujours ce meme fichier en SQLITE_OPEN_READONLY -- ce script ne
// s'execute jamais dans le flux d'une requete HTTP (D-001, D-007).
$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// DDL identique a schema.sql (source canonique, deja a jour avec les 19 list_type -- voir
// docs/DECISIONS.md D-DE-003 : schema.sql n'a jamais divergé pour cette table, contrairement a
// l'historique francais D-017/I7).
$pdo->exec('DROP TABLE IF EXISTS list_counts');
$pdo->exec(
    'CREATE TABLE list_counts ('
    . "list_type TEXT NOT NULL CHECK (list_type IN ('length', 'start', 'end', 'length_start', 'length_end', 'length_with', 'start_end', 'length_with_position', 'length_avec_sans', 'length_start_end', 'length_with_pair', 'length_with_triple', 'start_end_with', 'start_with', 'prefix2', 'prefix3', 'prefix4', 'suffix2', 'suffix3', 'suffix4')), "
    . 'list_key TEXT NOT NULL, '
    . 'count INTEGER NOT NULL, '
    . 'PRIMARY KEY (list_type, list_key)'
    . ')'
);

$insert = $pdo->prepare('INSERT INTO list_counts (list_type, list_key, count) VALUES (?, ?, ?)');

$pdo->beginTransaction();

$total = 0;

// 'length' : /woerter section "Nach Laenge" -- deja consommee par ExploreHubBuilder, deja
// indexee cote SEO (word_list_length, 14/14, D-DE-013). Simple GROUP BY sur `length`, colonne
// indexee directement (pas une expression) -- le plus rapide des cinq.
$lengthStatement = $pdo->query('SELECT length, COUNT(*) n FROM terms GROUP BY length ORDER BY length');
foreach ($lengthStatement as $row) {
    $insert->execute(['length', (string) $row['length'], (int) $row['n']]);
    $total++;
}

// 'start' : /woerter section "Beginnend Mit" -- deja indexee cote SEO (word_list_commencant,
// 29/29, D-DE-017). GROUP BY sur substr(normalized, 1, 1) : SQLite compte par CARACTERE sur TEXT,
// Ä/Ö/Ü ressortent comme des cles a part entiere (verifie : 29 lignes produites, A-Z + Ä/Ö/Ü).
$startStatement = $pdo->query('SELECT substr(normalized, 1, 1) c, COUNT(*) n FROM terms GROUP BY c ORDER BY c');
foreach ($startStatement as $row) {
    $insert->execute(['start', $row['c'], (int) $row['n']]);
    $total++;
}

// 'end' : /woerter section "Endend Mit" -- rend la section non vide (objectif direct de cette
// tache), mais voir la LIMITE DOCUMENTEE en entete de fichier : la famille REELLEMENT indexee
// cote allemand (word_list_terminant) est a 2 lettres, pas 1 -- seule "endend-mit/s" (parmi ces
// 29 lignes) correspond a un lien deja reconnu utile par D-DE-017.
$endStatement = $pdo->query('SELECT substr(reversed, 1, 1) c, COUNT(*) n FROM terms GROUP BY c ORDER BY c');
foreach ($endStatement as $row) {
    $insert->execute(['end', $row['c'], (int) $row['n']]);
    $total++;
}

// 'length_start' (D-022 cote francais, jamais construit cote allemand avant ce lot) : croise
// longueur et lettre de debut -- alimente App\Search\LengthLinksBuilder::build() (byStart),
// depuis /woerter/{N}-buchstaben (deja indexee) vers /woerter/{N}-buchstaben/beginnend-mit/{X}
// (famille combinee PAS ENCORE ouverte a l'indexation, decision SEO future). list_key =
// "{longueur}:{lettre}", ex. "9:F" -- format EXACT attendu par LengthLinksBuilder (verifie
// directement dans son code, pas suppose). Seules les combinaisons REELLEMENT non vides sont
// inserees (jamais une ligne a 0, meme regle que le depot francais, R5 du registre SEO : jamais
// de lien mort meme hors indexation).
$lengthStartStatement = $pdo->query(
    'SELECT length, substr(normalized, 1, 1) c, COUNT(*) n FROM terms GROUP BY length, c ORDER BY length, c'
);
foreach ($lengthStartStatement as $row) {
    $insert->execute(['length_start', $row['length'] . ':' . $row['c'], (int) $row['n']]);
    $total++;
}

// 'length_end' : meme principe que 'length_start', byEnd, vers
// /woerter/{N}-buchstaben/endend-mit/{X} -- meme limite d'asymetrie qu'expliquee pour 'end'
// ci-dessus (aucune consequence pratique ici : cette famille combinee n'est pas indexee du tout
// pour l'instant, a aucune granularite).
$lengthEndStatement = $pdo->query(
    'SELECT length, substr(reversed, 1, 1) c, COUNT(*) n FROM terms GROUP BY length, c ORDER BY length, c'
);
foreach ($lengthEndStatement as $row) {
    $insert->execute(['length_end', $row['length'] . ':' . $row['c'], (int) $row['n']]);
    $total++;
}

$pdo->commit();

// D-021 (herite) : toute modification de table/index doit etre suivie d'ANALYZE dans la MEME
// operation, jamais une etape facultative ou differee -- ce script peuple list_counts pour la
// premiere fois (0 -> ~1122 lignes attendues ce lot), laissant sinon les statistiques du
// planificateur perimees pour toute requete future touchant cette table.
$pdo->exec('ANALYZE');

printf(
    "list_counts : %d lignes inserees (length/start/end/length_start/length_end -- 14 des 19 "
    . "list_type restent a 0 ligne, voir l'entete de ce fichier pour le detail par type et la "
    . "raison de chaque report)\n",
    $total,
);
