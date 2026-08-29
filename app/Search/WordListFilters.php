<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Analyse et canonicalise les contraintes de /woerter/... (Phase 3, docs/08).
 *
 * ADAPTATION ALLEMANDE (localisation d'URL, D-DE-009) : "commencant"/"terminant" (mots-cles
 * ET route prefixe "/mots") sont devenus "beginnend-mit"/"endend-mit" (et "/woerter") --
 * transformation confirmee par recherche concurrentielle (reports/de-serp-terminology-
 * research.md, section 2.3/2.4 : "beginnend mit X" chez buchstaben.com, "endend mit X" chez
 * wortlisten.com, paire symetrique attestee sur deux sources independantes).
 *
 * SECOND PALIER DE LOCALISATION (D-DE-015, cette passe) : les 7 mots-cles laisses en francais
 * par D-DE-009 sont traduits a leur tour. Correspondance et niveau de preuve, terme par terme
 * (voir docs/DECISIONS.md D-DE-015 pour le raisonnement complet) :
 *
 *   contenant -> enthalten        tournure allemande standard "Wörter, die CH enthalten" ;
 *                                 choix raisonne, PAS une capture SERP du rapport (le rapport
 *                                 ne couvre pas cette famille)
 *   avec      -> mit-buchstaben   atteste : "Liste der Wörter mit den Buchstaben X"
 *                                 (scrabble123.de, reports/de-serp-terminology-research.md
 *                                 sections 2.5 et 5). Forme composee plutot que "mit" seul :
 *                                 "mit" seul serait ambigu avec "{N}-buchstaben" pour un
 *                                 lecteur humain, et n'aurait aucune valeur descriptive
 *   sans      -> ohne             preposition allemande directe, aucune ambiguite possible ;
 *                                 choix raisonne, pas une capture SERP
 *   motif     -> muster           terme allemand standard pour "motif/pattern" ; choix
 *                                 raisonne, pas une capture SERP
 *   position  -> position         INCHANGE : "die Position" est un substantif allemand a part
 *                                 entiere, deja employe dans le texte visible allemand
 *                                 ("an einer bestimmten Position", app/View/home.php ;
 *                                 "Position Von X Im Wort", app/View/word-list.php). Moins
 *                                 polysemique que "Stelle" (qui signifie aussi "emploi" et
 *                                 "passage d'un texte") hors de la tournure "an 3. Stelle".
 *                                 Cognate exact, aucune churn d'URL gratuite
 *   statut    -> status           "der Status", cognate direct
 *   tri       -> sortierung       "Sortierung" est le terme d'interface standard pour l'ordre
 *                                 de tri d'une liste ; "Reihenfolge" designe une sequence/un
 *                                 enchainement, pas un critere de tri -- ecarte
 *
 * Les VALEURS d'enumeration ("admis"/"non-admis" pour statut, "points"/"points-desc" pour tri)
 * restent volontairement francaises cette passe : hors des 7 concepts listes, elles changent
 * les URL d'une famille supplementaire et sont signalees comme lot suivant, PAS un oubli.
 *
 * Ordre canonique impose partout -- URL, cles, canonicals (docs/05_URL_SEO_INDEXATION.md).
 * IDENTIQUE a D-DE-009, seul le vocabulaire change :
 *
 *   longueur -> beginnend-mit -> enthalten -> endend-mit -> position -> mit-buchstaben -> ohne
 *   -> muster -> status -> sortierung
 *
 * "position" (D-023, ajoutee a la place reservee dans l'ordre ci-dessus) : une lettre connue
 * a UNE position precise, ex. "9-buchstaben/position/3/a" = mots de 9 lettres avec A en 3e
 * position. Exige TOUJOURS une longueur explicite (comme "sortierung", meme raison : sans
 * longueur, "position 3" n'a pas de sens borne). Espace de combinaisons volontairement
 * restreint par rapport a "muster" general (une seule lettre connue, jamais plusieurs
 * simultanement) -- ~2 366 combinaisons reelles au total (26 lettres x positions 2 a
 * longueur-1 x 14 longueurs), largement borne, contrairement a "muster" (2^15 combinaisons par
 * longueur, jamais indexable -- D-012/NEVER_SITEMAP). position/1/{lettre} et
 * position/{longueur}/{lettre} (premiere et derniere lettre) sont des cas degeneres deja
 * couverts par "beginnend-mit"/"endend-mit" -- pour eviter le contenu duplique constate sur
 * "muster" (un "muster/a----" et un "beginnend-mit/a" produisant la meme liste sous deux URL
 * canoniques distinctes, jamais rapproche), fromPath() les COLLAPSE silencieusement vers
 * prefix/suffix (meme mecanisme que la correction de longueur derivee du motif ci-dessous) --
 * $position/$positionLetter ne portent jamais les positions 1 ou longueur, canonicalPath()
 * n'emet donc jamais "position/1/..." ni "position/{longueur}/...".
 *
 * "mit-buchstaben/X" redondant avec un "beginnend-mit/X"/"endend-mit/X" d'UNE SEULE LETTRE
 * (D-032) : meme mecanisme de collapse silencieux que "position" ci-dessus, applique cette
 * fois a "mit-buchstaben". "beginnend-mit/X/mit-buchstaben/X" (minCount = 1, l'occurrence
 * unique par defaut) est logiquement toujours vrai des que le mot commence deja par X --
 * garder cette entree
 * withLetters ferait basculer a tort needsUnindexedPredicates() en regime BORNE plafonne
 * (ROW_EXAMINATION_CEILING) pour une contrainte qui n'exclut jamais aucune ligne, produisant
 * un total tronque et trompeur au lieu du vrai total (regime EXACT, sans plafond) deja
 * disponible via "beginnend-mit/X" seul. fromPath() retire alors cette entree $withLetters
 * plutot que de traiter le cas dans WordListSolver -- canonicalPath() n'emet donc plus jamais
 * "mit-buchstaben/X" a cote de "beginnend-mit"/"endend-mit/X" pour la meme lettre X, le
 * routeur redirige en 301. Seule la forme mono-lettre est concernee (minCount strictement egal
 * a 1) : un "mit-buchstaben/X/X" (minCount = 2, un DEUXIEME X) reste un vrai predicat, jamais
 * garanti par le seul prefixe/suffixe. Mesure (avant localisation, sous les anciens noms
 * francais) : reports/query-plans/commencant-avec-no-length-full-sweep.md section 5 (17/26 cas
 * affectes, jusqu'a 224 205 pour R).
 *
 * "status" et "sortierung" (D-022) sont des RAFFINEMENTS d'affichage, pas des contraintes de
 * recherche a proprement parler -- places en derniere position de l'ordre canonique, apres
 * toutes les contraintes de contenu. "status/admis" ou "status/non-admis" filtre sur
 * is_admitted (colonne precalculee, voir schema.sql). "sortierung/points" ou
 * "sortierung/points-desc" trie par score plutot que par ordre alphabetique -- EXIGE une
 * longueur explicite (readSort() refuse sinon, 404) : seul ce sous-ensemble (longueur seule,
 * longueur+prefixe, longueur+suffixe) a ete mesure sur comme couvrant tout le budget TTFB
 * (reports/query-plans/status-filter-admitted.md) ; trier sans aucun ancrage de longueur
 * retomberait dans le meme cout qu'un parcours large non borne, jamais mesure, donc jamais
 * propose. Les VALEURS ("admis"/"non-admis", "points"/"points-desc") restent francaises, voir
 * l'entete de cette classe.
 *
 * Cette classe ne fait AUCUN acces base : parsing et validation syntaxique pures, meme
 * discipline que Rack::fromInput(). WordListSolver traduit ensuite ces filtres en requetes.
 *
 * Canonicalisation : quel que soit l'ordre des mots-cles dans l'URL recue, fromPath()
 * reconstruit une representation interne normalisee, puis canonicalPath() la re-serialise
 * TOUJOURS dans l'ordre impose. Le routeur compare le chemin recu a canonicalPath() -- meme
 * convention que TermPage::$slug et RackPage::$slug -- et redirige en 301 si différent
 * ("toute autre permutation redirige en 301", docs/05).
 */
final class WordListFilters
{
    /**
     * Mot-cle de chaque concept, source UNIQUE pour tout le depot.
     *
     * Publiques et referencees par tous les appelants TOUCHES PAR D-DE-015 (app/Search/
     * *LinksBuilder, app/View, public/index.php) plutot que recopiees en dur : la localisation
     * D-DE-009 avait justement laisse passer un repli formulaire GET de public/index.php qui
     * construisait encore "commencant/..."/"terminant/..." en dur alors que KEYWORDS avait deja
     * bascule -- un vrai bug, invisible aux tests unitaires de cette classe.
     *
     * Restent volontairement hors de ce passage aux constantes cette passe : les litteraux
     * "beginnend-mit"/"endend-mit" deja corrects depuis D-DE-009 (une douzaine d'occurrences
     * dans app/Search et app/View). Les convertir est un nettoyage purement mecanique, sans
     * aucun effet de bord, laisse a un commit dedie pour ne pas melanger deux intentions dans
     * la meme revue -- pas un oubli.
     */
    public const KEYWORD_PREFIX = 'beginnend-mit';
    public const KEYWORD_CONTAINS = 'enthalten';
    public const KEYWORD_SUFFIX = 'endend-mit';
    public const KEYWORD_POSITION = 'position';
    public const KEYWORD_WITH = 'mit-buchstaben';
    public const KEYWORD_WITHOUT = 'ohne';
    public const KEYWORD_PATTERN = 'muster';
    public const KEYWORD_STATUS = 'status';
    public const KEYWORD_SORT = 'sortierung';

    /** Mots-cles reconnus, dans l'ordre canonique (D-023 : "position" ajoutee ; D-DE-009 :
     * "commencant"/"terminant" localises en "beginnend-mit"/"endend-mit" ; D-DE-015 : les 7
     * mots-cles restants localises a leur tour). L'ORDRE de ce tableau EST l'ordre canonique
     * et n'a PAS change depuis D-DE-009 -- seul le vocabulaire a change. */
    private const KEYWORDS = [
        self::KEYWORD_PREFIX,
        self::KEYWORD_CONTAINS,
        self::KEYWORD_SUFFIX,
        self::KEYWORD_POSITION,
        self::KEYWORD_WITH,
        self::KEYWORD_WITHOUT,
        self::KEYWORD_PATTERN,
        self::KEYWORD_STATUS,
        self::KEYWORD_SORT,
    ];

    /** Valeurs acceptees pour le segment "status" (D-022) -- volontairement NON localisees
     * cette passe (hors des 7 mots-cles de D-DE-015, voir l'entete de classe). */
    private const STATUS_VALUES = ['admis', 'non-admis'];

    /** Valeurs acceptees pour le segment "sortierung" (D-022) -- meme remarque. */
    private const SORT_VALUES = ['points', 'points-desc'];

    /**
     * @param int|null $length longueur exacte demandee, 2 a 15
     * @param string|null $prefix forme normalisee (A-Z), beginnend-mit
     * @param string|null $suffix forme normalisee (A-Z), endend-mit
     * @param string|null $contains forme normalisee (A-Z), enthalten
     * @param array<string, int> $withLetters lettre normalisee => nombre minimum d'occurrences
     *        (mit-buchstaben, repetitions comptees), triees par cle
     * @param list<string> $withoutLetters lettres normalisees a exclure completement (ohne),
     *        triees, sans doublon
     * @param string|null $pattern motif de cases connues : A-Z pour une lettre connue, '-'
     *        pour une case inconnue ; longueur du motif = longueur du mot (2 a 15)
     * @param int|null $position position 1-based d'une lettre connue (D-023), jamais 1 ni
     *        $length (voir collapse vers prefix/suffix, docblock de classe) -- toujours
     *        accompagne d'une longueur explicite et de $positionLetter
     * @param string|null $positionLetter lettre normalisee (A-Z) a $position, null ssi
     *        $position est null
     * @param string|null $status 'admis'|'non-admis' (D-022), null = aucun filtre de statut
     * @param string|null $sort 'points'|'points-desc' (D-022), null = ordre alphabetique
     *        (par defaut). Toujours accompagne d'une longueur explicite -- voir readSort().
     * @param int $page page demandee, >= 1 (1 = premiere page, jamais reflete dans l'URL)
     */
    private function __construct(
        public readonly ?int $length,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
        public readonly ?string $contains,
        public readonly array $withLetters,
        public readonly array $withoutLetters,
        public readonly ?string $pattern,
        public readonly ?int $position,
        public readonly ?string $positionLetter,
        public readonly ?string $status,
        public readonly ?string $sort,
        public readonly int $page,
    ) {
    }

    /**
     * Construit les filtres a partir du chemin brut recu par le routeur (deja debarrasse du
     * prefixe "/woerter", ex. "/7-buchstaben/beginnend-mit/ch" ou "" pour /woerter seul).
     *
     * Renvoie null pour toute forme non exploitable : mot-cle inconnu, mot-cle duplique,
     * valeur manquante ou invalide, "mit-buchstaben"/"ohne" sans lettre, longueur hors bornes,
     * motif hors bornes ou incoherent, "position" sans longueur ou hors bornes (D-023). Aucune
     * exception ne remonte --
     * meme discipline que Normalizer::normalize() et Rack::fromInput() : une entree
     * utilisateur ne doit jamais faire planter le flux HTTP normal. C'est une erreur de
     * saisie/routage, pas un resultat de recherche -- au routeur de traduire null en 404.
     */
    public static function fromPath(string $rawPath): ?self
    {
        $segments = array_values(array_filter(explode('/', trim($rawPath, '/')), static fn (string $s): bool => $s !== ''));

        [$page, $segments] = self::extractTrailingPage($segments);

        if ($page === null) {
            return null;
        }

        $length = null;
        $prefix = null;
        $suffix = null;
        $contains = null;
        $withLetters = [];
        $withoutLetters = [];
        $pattern = null;
        $position = null;
        $positionLetter = null;
        $status = null;
        $sort = null;
        $seenKeywords = [];

        $i = 0;
        $count = count($segments);

        // La longueur, si presente, doit ouvrir la liste -- c'est un token positionnel
        // ("{N}-buchstaben", D-DE-009), pas un mot-cle suivi d'une valeur comme les autres.
        if ($count > 0 && preg_match('/^(\d{1,2})-buchstaben\z/', $segments[0], $m) === 1) {
            $length = (int) $m[1];

            if ($length < Normalizer::MIN_LENGTH || $length > Normalizer::MAX_LENGTH) {
                return null;
            }

            $i = 1;
        }

        while ($i < $count) {
            $keyword = $segments[$i];

            if (!in_array($keyword, self::KEYWORDS, true)) {
                // Inclut le cas "{N}-buchstaben" hors premiere position, et tout mot-cle
                // inconnu : 404, jamais 301.
                return null;
            }

            if (isset($seenKeywords[$keyword])) {
                return null;
            }
            $seenKeywords[$keyword] = true;

            $i++;

            switch ($keyword) {
                case self::KEYWORD_PREFIX:
                    [$prefix, $i] = self::readSingleLetterRun($segments, $i, $count);
                    if ($prefix === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_CONTAINS:
                    [$contains, $i] = self::readSingleLetterRun($segments, $i, $count);
                    if ($contains === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_SUFFIX:
                    [$suffix, $i] = self::readSingleLetterRun($segments, $i, $count);
                    if ($suffix === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_POSITION:
                    [$position, $positionLetter, $i] = self::readPosition($segments, $i, $count);
                    if ($position === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_WITH:
                    [$withLetters, $i] = self::readLetterMultiset($segments, $i, $count);
                    if ($withLetters === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_WITHOUT:
                    [$withoutLetters, $i] = self::readLetterSet($segments, $i, $count);
                    if ($withoutLetters === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_PATTERN:
                    [$pattern, $i] = self::readPattern($segments, $i, $count);
                    if ($pattern === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_STATUS:
                    [$status, $i] = self::readEnumValue($segments, $i, $count, self::STATUS_VALUES);
                    if ($status === null) {
                        return null;
                    }
                    break;

                case self::KEYWORD_SORT:
                    [$sort, $i] = self::readEnumValue($segments, $i, $count, self::SORT_VALUES);
                    if ($sort === null) {
                        return null;
                    }
                    break;
            }
        }

        // Le motif implique sa propre longueur (position de chaque case). Une longueur
        // explicite differente n'est pas une erreur 404 : canonicalPath() fait toujours
        // primer la longueur du motif, et le routeur redirige en 301 vers la forme corrigee
        // -- meme esprit que "toute autre permutation redirige en 301".
        if ($pattern !== null) {
            // mb_strlen (pas strlen) : $pattern peut contenir Ä/Ö/Ü.
            $length = mb_strlen($pattern);
        }

        // "position" (D-023) exige une longueur explicite, quel que soit l'ordre de saisie
        // des segments -- meme raison que "sortierung" ci-dessous : sans longueur, "position 3"
        // ne borne rien. Incompatible avec "muster" : deux vocabulaires distincts pour le meme
        // concept (une lettre connue a une position) ne doivent jamais coexister dans la
        // meme URL.
        if ($position !== null) {
            if ($length === null || $pattern !== null || $position > $length) {
                return null;
            }

            // Positions degenerees (premiere/derniere lettre) : collapse silencieux vers
            // prefix/suffix plutot que de servir une seconde URL canonique pour la meme
            // liste de mots -- evite le contenu duplique deja constate sur "motif" (voir
            // docblock de classe). Un conflit avec un "beginnend-mit"/"endend-mit" explicite
            // portant une lettre DIFFERENTE reste une contrainte contradictoire -> 404.
            if ($position === 1) {
                if ($prefix !== null && $prefix !== $positionLetter) {
                    return null;
                }
                $prefix = $positionLetter;
                $position = null;
                $positionLetter = null;
            } elseif ($position === $length) {
                if ($suffix !== null && $suffix !== $positionLetter) {
                    return null;
                }
                $suffix = $positionLetter;
                $position = null;
                $positionLetter = null;
            }
        }

        // "mit-buchstaben" redondant avec un prefixe/suffixe D'UNE SEULE LETTRE (D-032) :
        // "beginnend-mit/X/mit-buchstaben/X" (minCount === 1) est TOUJOURS vrai des que
        // "commence par X" l'est deja --
        // conserver cette entree withLetters ferait basculer a tort needsUnindexedPredicates()
        // en regime BORNE plafonne (ROW_EXAMINATION_CEILING) pour une contrainte qui n'exclut
        // jamais aucune ligne, produisant un total tronque et trompeur au lieu du vrai total
        // deja disponible sans plafond via le regime EXACT de "beginnend-mit/X" seul (mesure,
        // sous les anciens noms francais avant D-DE-009 : reports/query-plans/
        // commencant-avec-no-length-full-sweep.md section 5 -- 17 des 26 combinaisons
        // commencant/X/avec/X affichaient un total plafonne a 10 000 au lieu du vrai total,
        // jusqu'a 224 205 pour R). Retire silencieusement cette entree plutot que de traiter le
        // cas dans WordListSolver -- meme principe que le collapse "position" degeneree
        // ci-dessus (D-023) : canonicalPath() n'emet alors plus jamais "mit-buchstaben/X" a cote
        // de "beginnend-mit"/"endend-mit/X", le routeur redirige en 301 vers la forme
        // simplifiee. Ne retire QUE l'entree strictement redondante : minCount === 1 exactement
        // -- un minCount >= 2 (ex. mit-buchstaben/x/x, "beginnend-mit/x/mit-buchstaben/x/x")
        // exige un DEUXIEME X, jamais
        // garanti par le seul prefixe/suffixe d'une lettre, donc jamais retire ici. Un prefixe/
        // suffixe de PLUSIEURS lettres n'est volontairement pas traite (hors perimetre mesure de
        // cette correction, voir le rapport cite) : seule la forme mono-lettre l'est.
        // mb_strlen (pas strlen) : un prefixe/suffixe d'une seule lettre Ä/Ö/Ü (deux octets
        // UTF-8) resterait sinon non detecte comme "une seule lettre" (bytes = 2), et cette
        // simplification D-032 ne s'appliquerait pas -- pas un bug de resultat (juste une URL
        // moins canonique), mais corrige pour la coherence.
        if ($prefix !== null && mb_strlen($prefix) === 1 && isset($withLetters[$prefix]) && $withLetters[$prefix] === 1) {
            unset($withLetters[$prefix]);
        }

        if ($suffix !== null && mb_strlen($suffix) === 1 && isset($withLetters[$suffix]) && $withLetters[$suffix] === 1) {
            unset($withLetters[$suffix]);
        }

        // "sortierung" (D-022) exige une longueur explicite, quel que soit l'ordre de saisie des
        // segments -- verifie ici, apres la longueur derivee du motif ci-dessus, plutot que
        // dans le case KEYWORD_SORT du switch (une saisie non canonique pourrait sinon placer
        // "sortierung" avant "muster"/le token positionnel "{N}-buchstaben" dans les segments
        // recus). Mesure
        // (schema.sql, idx_terms_length_score_normalized) : seul le sous-ensemble ancre sur une
        // longueur reste dans le budget TTFB pour un tri par points.
        if ($sort !== null && $length === null) {
            return null;
        }

        // Aucune contrainte du tout ($length === null && ... && $pattern === null) reste un
        // etat valide : /woerter seul = parcours complet, pagine (voir isEmpty()). Ce n'est pas
        // une route annoncee par docs/05 -- le routeur decide s'il l'expose.

        ksort($withLetters, SORT_STRING);
        sort($withoutLetters, SORT_STRING);

        return new self($length, $prefix, $suffix, $contains, $withLetters, $withoutLetters, $pattern, $position, $positionLetter, $status, $sort, $page);
    }

    /**
     * Un seul segment lettres-uniquement (beginnend-mit / enthalten / endend-mit). Renvoie
     * [null, $i] si absent, vide ou invalide.
     *
     * @param list<string> $segments
     * @return array{0: string|null, 1: int}
     */
    private static function readSingleLetterRun(array $segments, int $i, int $count): array
    {
        if ($i >= $count) {
            return [null, $i];
        }

        // [A-ZÄÖÜ] + /u, mb_strlen (pas [A-Z]/strlen) : Ä/Ö/Ü sont des lettres allemandes
        // valides ici (ex. /woerter/beginnend-mit/ö), pas des variantes de A/O/U -- meme
        // correctif que Normalizer::VALID_PATTERN.
        $normalized = Normalizer::normalize($segments[$i]);

        if ($normalized === '' || preg_match('/^[A-ZÄÖÜ]+\z/u', $normalized) !== 1 || mb_strlen($normalized) > Normalizer::MAX_LENGTH) {
            return [null, $i];
        }

        return [$normalized, $i + 1];
    }

    /**
     * Deux segments consecutifs : une position 1-based (entier decimal, jamais 0 ni negatif)
     * puis une lettre unique (D-023). Renvoie [null, null, $i] si l'un des deux est absent,
     * vide ou invalide -- la borne superieure (position <= longueur) est verifiee par
     * l'appelant, une fois la longueur connue (voir fromPath()).
     *
     * @param list<string> $segments
     * @return array{0: int|null, 1: string|null, 2: int}
     */
    private static function readPosition(array $segments, int $i, int $count): array
    {
        if ($i >= $count || preg_match('/^[1-9]\d?\z/', $segments[$i]) !== 1) {
            return [null, null, $i];
        }

        $position = (int) $segments[$i];

        [$letter, $next] = self::readSingleLetterRun($segments, $i + 1, $count);

        // mb_strlen (pas strlen) : $letter peut etre Ä/Ö/Ü (deux octets UTF-8).
        if ($letter === null || mb_strlen($letter) !== 1) {
            return [null, null, $i];
        }

        return [$position, $letter, $next];
    }

    /**
     * Un seul segment dont la valeur doit appartenir a $allowed (status, sortierung -- D-022).
     * Renvoie [null, $i] si absent ou hors de la liste fermee -- jamais de valeur inventee.
     *
     * @param list<string> $segments
     * @param list<string> $allowed
     * @return array{0: string|null, 1: int}
     */
    private static function readEnumValue(array $segments, int $i, int $count, array $allowed): array
    {
        if ($i >= $count || !in_array($segments[$i], $allowed, true)) {
            return [null, $i];
        }

        return [$segments[$i], $i + 1];
    }

    /**
     * Une ou plusieurs cases-segments d'une seule lettre chacune (mit-buchstaben), consommees
     * jusqu'au prochain mot-cle connu ou la fin du chemin. Compte les repetitions.
     *
     * @param list<string> $segments
     * @return array{0: array<string, int>|null, 1: int}
     */
    private static function readLetterMultiset(array $segments, int $i, int $count): array
    {
        $letters = [];
        $start = $i;

        while ($i < $count && !in_array($segments[$i], self::KEYWORDS, true)) {
            $normalized = Normalizer::normalize($segments[$i]);

            // mb_strlen + [A-ZÄÖÜ]/u (pas strlen/[A-Z]) : meme raison que readSingleLetterRun.
            if (mb_strlen($normalized) !== 1 || preg_match('/^[A-ZÄÖÜ]\z/u', $normalized) !== 1) {
                return [null, $i];
            }

            $letters[$normalized] = ($letters[$normalized] ?? 0) + 1;
            $i++;
        }

        if ($i === $start) {
            // "mit-buchstaben" sans aucune lettre : segment vide ou immediatement suivi d'un
            // mot-cle.
            return [null, $i];
        }

        return [$letters, $i];
    }

    /**
     * Une ou plusieurs cases-segments d'une seule lettre chacune (ohne), dedupliquees.
     *
     * @param list<string> $segments
     * @return array{0: list<string>|null, 1: int}
     */
    private static function readLetterSet(array $segments, int $i, int $count): array
    {
        [$multiset, $next] = self::readLetterMultiset($segments, $i, $count);

        if ($multiset === null) {
            return [null, $next];
        }

        return [array_keys($multiset), $next];
    }

    /**
     * Le motif : un seul segment, lettres A-Z ou '-' (case inconnue), longueur 2 a 15,
     * au moins une lettre connue (un motif entierement fait de '-' n'apporte aucune
     * information au-dela de la longueur -- refuse pour rester un mot-cle utile).
     *
     * @param list<string> $segments
     * @return array{0: string|null, 1: int}
     */
    private static function readPattern(array $segments, int $i, int $count): array
    {
        if ($i >= $count) {
            return [null, $i];
        }

        // [A-ZÄÖÜ]/u + mb_strlen (pas [A-Z]/strlen) : meme raison que readSingleLetterRun.
        // La comparaison de longueur ci-dessous protege specifiquement contre un caractere
        // qui CHANGERAIT DE NOMBRE de lettres apres normalize() (ex. "ß" brut, 1 caractere,
        // normalise en "SS", 2 caracteres) -- une telle entree desynchroniserait la
        // reconstruction position-par-position ci-dessous et DOIT etre rejetee. Comparer en
        // OCTETS (ancien code) ne detectait PAS ce cas particulier pour "ß" -> "SS" (2 octets
        // UTF-8 des deux cotes, coincidence numerique) : corrige ici en comparant par
        // CARACTERE (mb_strlen), la seule unite pertinente pour cette garde.
        $raw = $segments[$i];
        $letters = str_replace('-', '', $raw);
        $normalizedLetters = Normalizer::normalize($letters);

        if ($letters !== '' && (preg_match('/^[A-ZÄÖÜ]+\z/u', $normalizedLetters) !== 1 || mb_strlen($normalizedLetters) !== mb_strlen($letters))) {
            return [null, $i];
        }

        // Reconstruit le motif normalise en respectant la position d'origine des '-' : on ne
        // peut pas juste normaliser $raw tel quel, Normalizer::normalize() ne connait pas '-'.
        // mb_str_split (pas str_split) : $normalizedLetters/$raw peuvent contenir Ä/Ö/Ü.
        $pattern = '';
        $letterPos = 0;
        $normalizedChars = mb_str_split($normalizedLetters);

        foreach (mb_str_split($raw) as $char) {
            if ($char === '-') {
                $pattern .= '-';
                continue;
            }

            $pattern .= $normalizedChars[$letterPos] ?? '';
            $letterPos++;
        }

        if (mb_strlen($pattern) < Normalizer::MIN_LENGTH || mb_strlen($pattern) > Normalizer::MAX_LENGTH) {
            return [null, $i];
        }

        if ($letters === '') {
            // Motif entierement inconnu ("---") : refuse, n'apporte rien qu'une longueur ne
            // dise deja.
            return [null, $i];
        }

        return [$pattern, $i + 1];
    }

    /**
     * Detecte et retire un segment terminal "page/{n}". Absent -> page 1, segments
     * inchanges. "page/1" est syntaxiquement VALIDE (n'est pas une entree malformee) mais
     * jamais canonique -- canonicalUrl() l'omet toujours pour la page 1, donc le routeur
     * compare et redirige naturellement en 301 vers la forme sans "/page/1", meme mecanisme
     * que toute autre permutation (docs/05 : "toute autre permutation redirige en 301"), pas
     * un 404. Seule une valeur non numerique, 0 ou negative reste une entree malformee ->
     * [null, ...], propage vers fromPath() qui renvoie null (404).
     *
     * @param list<string> $segments
     * @return array{0: int|null, 1: list<string>}
     */
    private static function extractTrailingPage(array $segments): array
    {
        $count = count($segments);

        if ($count < 2 || $segments[$count - 2] !== 'page') {
            return [1, $segments];
        }

        if (preg_match('/^\d+\z/', $segments[$count - 1]) !== 1) {
            return [null, $segments];
        }

        $page = (int) $segments[$count - 1];

        if ($page < 1) {
            return [null, $segments];
        }

        return [$page, array_slice($segments, 0, $count - 2)];
    }

    /**
     * Chemin canonique, sans le "/woerter" initial ni le "/page/{n}" final (la pagination est
     * geree separement par le routeur/la vue, pas par cette representation de filtre --
     * meme raison que "page 1" n'apparait jamais dans l'URL). Toujours reconstruit dans
     * l'ordre impose, quel que soit l'ordre recu en entree.
     */
    public function canonicalPath(): string
    {
        // mb_strtolower (pas strtolower) dans toute cette methode : un segment Ä/Ö/Ü reste
        // sinon en MAJUSCULE dans une URL par ailleurs entierement minuscule (strtolower()
        // n'opere que sur A-Z ASCII) -- incoherent avec le reste du schema d'URL, et source
        // d'un aller-retour de redirection 301 inutile (canonicalPath() ne matcherait jamais
        // le chemin minuscule effectivement demande). Correctif de coherence, pas de
        // correction (les deux formes restent fonctionnellement valides, juste incoherentes).
        $segments = [];

        if ($this->length !== null) {
            $segments[] = $this->length . '-buchstaben';
        }

        if ($this->prefix !== null) {
            $segments[] = self::KEYWORD_PREFIX;
            $segments[] = mb_strtolower($this->prefix, 'UTF-8');
        }

        if ($this->contains !== null) {
            $segments[] = self::KEYWORD_CONTAINS;
            $segments[] = mb_strtolower($this->contains, 'UTF-8');
        }

        if ($this->suffix !== null) {
            $segments[] = self::KEYWORD_SUFFIX;
            $segments[] = mb_strtolower($this->suffix, 'UTF-8');
        }

        if ($this->position !== null) {
            $segments[] = self::KEYWORD_POSITION;
            $segments[] = (string) $this->position;
            $segments[] = mb_strtolower($this->positionLetter, 'UTF-8');
        }

        if ($this->withLetters !== []) {
            $segments[] = self::KEYWORD_WITH;
            foreach ($this->withLetters as $letter => $times) {
                for ($k = 0; $k < $times; $k++) {
                    $segments[] = mb_strtolower($letter, 'UTF-8');
                }
            }
        }

        if ($this->withoutLetters !== []) {
            $segments[] = self::KEYWORD_WITHOUT;
            foreach ($this->withoutLetters as $letter) {
                $segments[] = mb_strtolower($letter, 'UTF-8');
            }
        }

        if ($this->pattern !== null) {
            $segments[] = self::KEYWORD_PATTERN;
            $segments[] = mb_strtolower($this->pattern, 'UTF-8');
        }

        if ($this->status !== null) {
            $segments[] = self::KEYWORD_STATUS;
            $segments[] = $this->status;
        }

        if ($this->sort !== null) {
            $segments[] = self::KEYWORD_SORT;
            $segments[] = $this->sort;
        }

        return implode('/', $segments);
    }

    /** Chemin canonique complet, "/page/{n}" inclus si $this->page > 1. Prefixe "/woerter"
     * (D-DE-009, localise depuis "/mots" -- voir docs/DECISIONS.md). */
    public function canonicalUrl(): string
    {
        $base = '/woerter' . ($this->canonicalPath() !== '' ? '/' . $this->canonicalPath() : '');

        return $this->page > 1 ? $base . '/page/' . $this->page : $base;
    }

    /**
     * true si le filtre ne pose aucune contrainte (parcours complet de la base). "sortierung"
     * seul ne peut jamais rendre ce test faux a lui seul (il exige toujours une longueur, voir
     * fromPath()) ; "status" le peut (ex. /woerter/status/admis, sans autre contrainte) -- une
     * vraie restriction du panier, pas un parcours complet.
     */
    public function isEmpty(): bool
    {
        return $this->length === null && $this->prefix === null && $this->suffix === null
            && $this->contains === null && $this->withLetters === [] && $this->withoutLetters === []
            && $this->pattern === null && $this->position === null && $this->status === null;
    }

    /**
     * true si des predicats non couverts par un index dedie sont necessaires (enthalten,
     * mit-buchstaben, ohne, position (D-023), ou muster avec une case connue au-dela du
     * prefixe initial).
     * Determine si WordListSolver doit appliquer WordListSolver::ROW_EXAMINATION_CEILING (voir
     * sa documentation pour le detail des mesures qui justifient ce plafond).
     */
    public function needsUnindexedPredicates(): bool
    {
        if ($this->contains !== null || $this->withLetters !== [] || $this->withoutLetters !== [] || $this->position !== null) {
            return true;
        }

        if ($this->pattern !== null) {
            // Le prefixe initial (avant le premier '-') est deja couvert par l'index
            // normalized/length -- voir WordListSolver::patternLeadingPrefix(). Une case
            // connue (A-ZÄÖÜ) APRES la premiere case inconnue reste un predicat non indexe
            // (substr(normalized, position, 1) = lettre, evalue en ligne, pas via un index).
            // strpos()/substr() (pas leurs variantes mb_) restent SURS ici : '-' est un
            // caractere ASCII, jamais confondu avec un octet de continuation UTF-8 (0x80-0xBF)
            // -- la position OCTET qu'il renvoie est donc toujours aussi une frontiere de
            // caractere valide, la coupure substr() ne peut jamais tomber au milieu d'un Ä/Ö/Ü.
            $firstUnknown = strpos($this->pattern, '-');

            if ($firstUnknown === false) {
                // Pas de '-' du tout : readPattern() l'autorise (seul un motif ENTIEREMENT
                // fait de '-' est refuse) -- un motif entierement connu equivaut a
                // normalized = ?, couvert par l'index UNIQUE, aucun predicat supplementaire.
                return false;
            }

            // [A-ZÄÖÜ]/u (pas [A-Z] seul) : une case connue residuelle en Ä/Ö/Ü (ex. motif
            // "AB-Ö-") DOIT etre detectee comme predicat non indexe -- avec [A-Z] seul, ce
            // test renvoyait FAUX a tort, et WordListSolver aurait alors ignore silencieusement
            // la contrainte Ö en executant le regime EXACT (sans jamais appliquer le predicat
            // substr() correspondant) : un vrai bug de resultats, pas seulement de performance.
            return preg_match('/[A-ZÄÖÜ]/u', substr($this->pattern, $firstUnknown)) === 1;
        }

        // Prefixe ET suffixe combines : le suffixe est applique en predicat supplementaire
        // sur les lignes deja bornees par le prefixe (pas son propre index dans ce cas).
        if ($this->prefix !== null && $this->suffix !== null) {
            return true;
        }

        return false;
    }
}
