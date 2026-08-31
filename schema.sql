-- Scrabble Light — schéma de production allemand.
-- Fichier canonique, sous contrôle de la session principale.
-- Produit par scripts/import_de.py dans storage/dictionary_de.sqlite.
-- Ouvert en lecture seule au runtime. Aucune définition n'y est copiée.
--
-- Hérité de schema.sql (site français) puis SIMPLIFIÉ pour l'allemand -- voir
-- docs/DECISIONS.md pour la décision complète. Différences volontaires par rapport à la
-- version française :
--   - is_ods8/is_ods9/is_french (double lexique + dictionnaire général français) remplacées
--     par is_enz/is_hippler (D-DE-006) : deux SOURCES de la même famille de listes Scrabble
--     allemandes (enz/german-wordlist, fork continué, CC0-1.0 ; hippler/german-wordlist,
--     instantané figé ~janvier 2023 de l'ancêtre direct, même licence), pas deux ÉDITIONS
--     officielles distinctes comme ODS8/ODS9 -- la provenance par mot reste néanmoins
--     interrogeable exactement de la même façon (voir data/raw/PROVENANCE.md). is_admitted
--     reste DÉRIVÉE (is_enz OR is_hippler), précalculée au build, jamais une source de vérité
--     indépendante -- même rôle et même raisonnement que côté français (D-022) : permet un
--     filtre "admis" indexable sans recalculer un OR à chaque requête. Un OR RÉEL, pas une
--     constante codée en dur -- la troisième source anticipée ici est arrivée (D-DE-029,
--     is_german, kaikki.org/dewiktionary) : is_admitted vaut désormais réellement 0 pour toute
--     ligne is_german=1 seule (forme allemande réelle, non retenue par enz/hippler).
--   - pos/pos_secondary/gender (nature grammaticale, D-018 français) absentes : aucune
--     source de nature grammaticale allemande retenue dans cette passe (hors périmètre
--     explicite de la tâche). app/Search/TermLookup.php ne les sélectionne donc jamais et
--     construit toujours des valeurs nulles pour les champs correspondants de TermPage --
--     compatible sans modification avec app/View/word.php, qui gère déjà nativement
--     l'absence de ces données (~12,3 % des termes français concernés par le même cas).
--   - verb_forms et word_senses absentes : aucun pipeline de conjugaison/définitions
--     construit pour l'allemand dans cette passe (hors périmètre explicite de la tâche,
--     voir CLAUDE.md). app/Search/ConjugationLookup.php et App\Search\SenseLookup.php
--     renvoient directement un résultat vide sans jamais interroger ces tables (voir leurs
--     docblocks) -- aucune table absente n'est donc jamais requêtée au runtime,
--     public/index.php (fichier partagé) continue de fonctionner sans modification.
--   - list_counts CONSERVÉE (contrairement aux tables ci-dessus) et désormais PEUPLÉE, mais
--     PAS par scripts/import_de.py : par scripts/build_explore_hub_counts_de.php, un second
--     temps de build séparé (équivalent allemand direct de scripts/build_explore_hub_counts.php
--     côté français), à exécuter après import_de.py (voir CLAUDE.md, « État Des Données »).
--     Reconstruire la base avec le seul import_de.py laisse la table VIDE (0 ligne) -- même
--     effet dégradé que documenté ci-dessous, pas une erreur mais un hub /woerter et un
--     maillage silencieusement amputés. Contrairement à pos/gender/word_senses/verb_forms,
--     plusieurs classes App\Search\*LinksBuilder et App\Search\ExploreHubBuilder l'interrogent
--     SANS garde de disponibilité depuis public/index.php (contrairement à App\Seo\Registry,
--     qui vérifie is_file() avant toute requête) -- l'absence de la TABLE ferait échouer toute
--     page /woerter/... avec une erreur SQL, alors que 0 LIGNE se dégrade nativement en
--     sections vides (comportement attendu et testé côté français pour toute combinaison sans
--     résultat). État réel (20/20 list_type peuplés) : voir docs/PHASE_STATUS.md, section
--     allemande.

CREATE TABLE terms (
    id           INTEGER PRIMARY KEY,
    display_term TEXT    NOT NULL,
    normalized   TEXT    NOT NULL UNIQUE,

    -- Provenance par mot (D-DE-006) : quelle(s) source(s) retiennent ce terme, après
    -- normalisation -- une forme brute différente par source qui se rejoint après
    -- normalisation (ex. "Abschiedsgruss" côté hippler, "Abschiedsgruß" côté enz, toutes
    -- deux normalisées en ABSCHIEDSGRUSS via ß -> SS) compte comme présente dans LES DEUX
    -- sources, pas une collision à choisir. Mesuré sur l'import réel : 590 850 termes enz,
    -- 293 166 termes hippler, 293 160 dans les deux, 6 UNIQUEMENT dans hippler (ALF, ALFE,
    -- BÄT, ELAK, ETH, KÄM) -- confirme empiriquement l'hypothèse de la recherche de
    -- faisabilité ("la quasi-totalité des ~6 400 formes brutes hippler-seules sont des
    -- doublons de graphie ß/ss, pas une perte de couverture réelle") : seules 6 formes sur
    -- 6 398 sont de VRAIES formes absentes d'enz une fois normalisées.
    is_enz       INTEGER NOT NULL DEFAULT 0 CHECK (is_enz     IN (0, 1)),
    is_hippler   INTEGER NOT NULL DEFAULT 0 CHECK (is_hippler IN (0, 1)),

    -- D-DE-029 : troisième source, ACTIVÉE (la remarque ci-dessus l'anticipait depuis
    -- D-DE-006 : "prêt pour une future troisième source qui, elle, pourrait légitimement
    -- valoir 0"). Forme allemande réelle retenue par kaikki.org/dewiktionary (extraction du
    -- Wiktionnaire ALLEMAND natif, de.wiktionary.org -- pas l'édition anglaise
    -- "kaikki.org/dictionary/German/", qui glose en anglais), filtrée par pos (voir
    -- scripts/import_de.py, KAIKKI_POS_EXCLUDED). Équivalent allemand direct de is_spanish
    -- (site espagnol) / is_french (site français, colonne renommée ici). N'affecte JAMAIS
    -- is_admitted ci-dessous : une ligne is_german=1 SEULE (is_enz=0 ET is_hippler=0) reste
    -- is_admitted=0 -- modèle à trois statuts (CLAUDE.md).
    is_german    INTEGER NOT NULL DEFAULT 0 CHECK (is_german   IN (0, 1)),

    -- Admis (liste Scrabble allemande, l'une ou l'autre source ENZ/HIPPLER -- JAMAIS
    -- is_german ci-dessus). Colonne DÉRIVÉE (is_enz OR is_hippler), précalculée au build --
    -- jamais une source de vérité indépendante, même rôle que côté français (D-022). Passe de
    -- DEFAULT 1 (toujours vrai avant D-DE-029, aucune troisième source) à DEFAULT 0 (D-DE-029,
    -- même convention que is_admitted côté FR/ES) : le DEFAULT n'est jamais utilisé en
    -- pratique (scripts/import_de.py fixe explicitement chaque ligne), changé pour cohérence
    -- sémantique désormais que 0 est un cas réel. Le badge affiche "Wortliste"
    -- (config/sites/de.php), jamais "officiel" (aucune liste officielle allemande n'est
    -- librement accessible en masse, voir data/raw/PROVENANCE.md).
    is_admitted  INTEGER NOT NULL DEFAULT 0 CHECK (is_admitted IN (0, 1)),

    score        INTEGER NOT NULL,
    length       INTEGER NOT NULL CHECK (length >= 2),
    signature    TEXT    NOT NULL,
    reversed     TEXT    NOT NULL
);

-- La contrainte UNIQUE sur normalized crée déjà son propre index.
-- Un CREATE INDEX supplémentaire sur cette seule colonne serait redondant :
-- il est délibérément absent.

-- Longueur puis ordre alphabétique : /mots/7-lettres et ses paginations.
CREATE INDEX idx_terms_length_normalized ON terms(length, normalized);

-- Anagrammes exactes, et point de départ des anagrammes ±1 lettre.
CREATE INDEX idx_terms_signature ON terms(signature);

-- Suffixes : /mots/terminant/tion interroge reversed par PLAGE, jamais par LIKE.
--
--   correct   WHERE reversed >= 'NOIT' AND reversed < 'NOIU'   -> index, rapide
--   interdit  WHERE reversed LIKE 'NOIT%'                      -> SCAN complet
--
-- LIKE est insensible à la casse par défaut dans SQLite : l'optimiseur ne peut
-- pas l'adosser à un index BINARY, et la requête dégénère en balayage complet.
-- La même règle vaut pour les préfixes sur normalized. Comparaison BINARY par
-- défaut (pas de COLLATE) : pour Ä/Ö/Ü (encodées sur deux octets UTF-8), l'ordre
-- par octet coïncide avec l'ordre par codepoint (propriété de l'UTF-8) -- les
-- plages restent correctes, mais Ä/Ö/Ü trient après Z, pas à côté de A comme
-- dans un dictionnaire papier allemand (limite assumée, voir
-- app/Search/Normalizer.php::signature()).
CREATE INDEX idx_terms_reversed ON terms(reversed);

-- Suffixe COMBINÉ à une longueur (ex. /mots/7-lettres/terminant/s) : sans cet index
-- composé, un filtre "longueur + suffixe" ancre sur reversed (plage globale, TOUTES
-- longueurs confondues) et applique `length = ?` comme prédicat résiduel non couvert par
-- idx_terms_reversed -- coûteux dès que le suffixe est fréquent (comportement mesuré et
-- documenté côté français, reports/query-plans/terminant-length-index-fix.md). Repris ici
-- par précaution avant toute mesure allemande dédiée -- le même risque structurel existe,
-- la table étant construite par le même schéma de requêtes (App\Search\WordListSolver,
-- code partagé, inchangé).
CREATE INDEX idx_terms_length_reversed ON terms(length, reversed);

-- Filtre "admis seulement"/"non admis seulement" sur les listes /mots/... -- index
-- couvrant, sert aussi bien le filtre que le tri par normalized. idx_terms_length_admitted
-- _normalized sert le régime ancré sur une longueur (seule ou combinée à un préfixe) ;
-- idx_terms_admitted_normalized sert le même filtre SANS longueur. Repris du schéma
-- français (D-022) par précaution structurelle -- toute ligne vaut actuellement
-- is_admitted = 1 (source unique), donc ce filtre n'a pas d'effet discriminant réel tant
-- qu'aucune seconde source n'est ajoutée, mais l'index reste bon marché à construire et
-- évite une migration de schéma le jour où une source non admise serait ajoutée.
CREATE INDEX idx_terms_length_admitted_normalized ON terms(length, is_admitted, normalized);
CREATE INDEX idx_terms_admitted_normalized ON terms(is_admitted, normalized);

-- Tri "par points" sur les listes /mots/... -- nécessaire pour le régime ancré sur une
-- SEULE longueur (le cas le plus large) : sans cet index, ORDER BY score force un TEMP
-- B-TREE sur tout le panier avant LIMIT (comportement mesuré et documenté côté français,
-- ~760-870 ms sans index contre quelques ms avec -- reports/query-plans/ côté dépôt
-- français). Repris par précaution structurelle, code partagé inchangé.
CREATE INDEX idx_terms_length_score_normalized ON terms(length, score, normalized);

-- /mots/commencant/{X}/terminant/{Y} (une seule lettre de chaque côté) : sans cet index sur
-- les deux expressions substr() à la fois, App\Search\WordListSolver doit choisir un côté
-- comme ancrage et appliquer l'autre comme prédicat résiduel sur tout le panier ancré --
-- catastrophique dès que les deux lettres sont fréquentes (comportement mesuré et
-- documenté côté français, jusqu'à plusieurs secondes sans cet index -- reports/
-- query-plans/prefix-suffix-anchor-fix.md). Repris ici par précaution structurelle, code
-- partagé inchangé (App\Search\WordListSolver::anchorClause()). SQLite compte substr() par
-- CARACTÈRE (pas par octet) sur les colonnes TEXT -- fonctionne correctement pour Ä/Ö/Ü
-- sans adaptation.
CREATE INDEX idx_terms_startletter_endletter_normalized
    ON terms(substr(normalized, 1, 1), substr(reversed, 1, 1), normalized);

-- Comptes précalculés du maillage interne (hub /woerter, pages "beginnend-mit"/"endend-mit"/
-- "mit-buchstaben"/longueur combinées...), produits hors ligne par
-- scripts/build_explore_hub_counts_de.php -- désormais PEUPLÉE (20/20 list_type, voir
-- docs/PHASE_STATUS.md section allemande pour le compte exact), en un second temps de build
-- séparé de scripts/import_de.py (qui ne la touche jamais, voir note en tête de fichier).
-- JAMAIS peuplée par scripts/import_de.py seul -- table conservée vide dans ce cas, pas
-- supprimée, pour que les classes App\Search\ExploreHubBuilder et App\Search\*LinksBuilder
-- continuent de fonctionner sans modification -- comportement natif "aucun résultat" plutôt
-- qu'une erreur SQL "no such table". Structure identique au schéma français : voir
-- schema.sql du dépôt français pour le détail complet de chaque list_type, inchangé ici,
-- code partagé.
CREATE TABLE list_counts (
    list_type TEXT    NOT NULL CHECK (list_type IN ('length', 'start', 'end', 'length_start', 'length_end', 'length_with', 'start_end', 'length_with_position', 'length_avec_sans', 'length_start_end', 'length_with_pair', 'length_with_triple', 'start_end_with', 'start_with', 'prefix2', 'prefix3', 'prefix4', 'suffix2', 'suffix3', 'suffix4')),
    list_key  TEXT    NOT NULL,
    count     INTEGER NOT NULL,

    PRIMARY KEY (list_type, list_key)
);

-- Empreintes des sources et paramètres du build. Aucune date d'exécution :
-- l'import doit rester déterministe et rejouable à l'identique.
-- "key" et "value" sont entre guillemets : ils ne sont pas réservés en SQLite,
-- mais le sont dans d'autres dialectes, ce qui fait crier les linters SQL sur
-- un fichier que plusieurs agents vont relire.
CREATE TABLE build_metadata (
    "key"   TEXT PRIMARY KEY,
    "value" TEXT NOT NULL
);
