<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Solveur /wortsuche/{lettres} (Phase 2, docs/08 ; D-DE-009 : localise depuis /jouer) :
 * quels mots admis au Scrabble peut-on
 * former avec un chevalet, jokers compris.
 *
 * Strategie retenue apres mesure, validee explicitement par le coordinateur avant
 * implementation (reports/query-plans/phase2.md pour le detail complet) :
 *
 * 1. Un chevalet engendre l'ensemble de ses sous-multiensembles de lettres connues (0 a
 *    n lettres), croise avec 0, 1 ou 2 "remplissages" de jokers (chaque joker vaut
 *    n'importe quelle lettre A-Z). Chaque combinaison produit une signature candidate
 *    (lettres triees) -- signature est deja indexee (idx_terms_signature, D-012).
 * 2. Avant de generer quoi que ce soit, une borne SUPERIEURE bon marche (formule
 *    combinatoire fermee, aucun appel base, aucune allocation) determine si le nombre
 *    de signatures candidates peut depasser SIGNATURE_CEILING. Au-dela, aucune
 *    generation ni requete n'est executee : RackPage::$capped = true, un resultat
 *    distinct plutot qu'un calcul complet ou un blocage de worker PHP partage.
 * 3. En-deca du plafond, les signatures deduites (dedupliquees) sont regroupees en lots
 *    de CHUNK_SIZE et interrogees via `signature IN (...)`, chacune servie par
 *    idx_terms_signature (EXPLAIN QUERY PLAN : SEARCH ... USING INDEX, jamais de SCAN).
 *    ADAPTATION ALLEMANDE : pire cas RE-MESURE sur cette base (7 lettres distinctes + 2
 *    jokers, ex. "AEIORNT??") apres extension de l'alphabet des remplissages de joker a
 *    29 lettres (voir ALPHABET_SIZE) : upperBound = 59 520 (etait 50 4XX sur 26 lettres),
 *    46 722 signatures candidates reellement generees, 10 requetes a CHUNK_SIZE = 5000,
 *    ~169 ms mesures (34,5 ms generation + 134,2 ms requetes) -- sous le plafond retenu de
 *    65 000 (voir SIGNATURE_CEILING). Un rack de 8 lettres distinctes + 2 jokers (ex.
 *    "EIRALNTS??") depasse 65 000 (upperBound = 119 040, 90 082 signatures reelles,
 *    jusqu'a 261,6 ms mesures -- au-dessus du budget TTFB p95 < 250 ms) : capped = true
 *    s'applique correctement a cette classe, comme prevu.
 * 4. Seuls les mots admis (is_admitted = 1) sont retournes : voir RackPage. ADAPTATION
 *    ALLEMANDE : is_ods8/is_ods9 (double lexique francais) fusionnes en is_admitted
 *    unique (schema.sql) -- le SELECT ci-dessous aliase is_admitted vers is_ods8 ET
 *    is_ods9 (meme valeur des deux cotes) uniquement pour que le tableau ->matches reste
 *    compatible sans modification avec app/View/play.php (hors perimetre de cet agent),
 *    qui lit ['isOds8']/['isOds9'] pour deux pastilles -- consequence signalee dans le
 *    rapport AFTER (pastilles "ODS8"/"ODS9" incorrectes pour l'allemand tant qu'un
 *    passage frontend/microcopy dedie n'a pas adapte ce gabarit).
 * 5. Tri score decroissant puis longueur decroissante puis alphabetique en PHP (les
 *    volumes mesures -- au plus quelques milliers de lignes pour le pire cas -- restent
 *    triviaux en memoire), puis LIMIT DISPLAY_LIMIT applique apres tri, jamais avant
 *    (le tri porte sur l'ensemble des correspondances, pas sur un sous-ensemble
 *    arbitraire de lots).
 */
final class RackSolver
{
    /**
     * Sous le defaut SQLITE_LIMIT_VARIABLE_NUMBER (32766 sur SQLite >= 3.32) avec marge
     * confortable -- un IN() de CHUNK_SIZE = 5000 reussit toujours et le temps total est
     * domine par le nombre de recherches d'index effectuees, pas par le nombre de
     * requetes PHP-vers-SQLite (comportement heritee du site francais, non re-mesure
     * explicitement ici : la relation reste la meme, seul le volume de signatures change
     * avec l'alphabet elargi -- voir SIGNATURE_CEILING pour les mesures allemandes
     * completes).
     */
    public const CHUNK_SIZE = 5000;

    /**
     * ADAPTATION ALLEMANDE (re-mesuree, pas simplement heritee) : 65 000, releve depuis
     * 50 000 (valeur francaise, alphabet a 26 lettres) parce que l'alphabet des
     * remplissages de joker compte desormais 29 lettres (ALPHABET_SIZE, Ä/Ö/Ü comprises)
     * -- un rack a upperBound mecaniquement plus grand a nombre de lettres egal (facteur
     * ~1,23 pour 2 jokers, voir le calcul dans le docblock de classe). Choisi pour laisser
     * passer la meme CLASSE de pire cas que le site francais (7 lettres distinctes + 2
     * jokers, upperBound = 59 520, 46 722 signatures reelles, ~169 ms mesures) tout en
     * continuant a bloquer la classe suivante (8 lettres distinctes + 2 jokers,
     * upperBound = 119 040, jusqu'a 261,6 ms mesures -- au-dessus du budget TTFB).
     * Comparee a une borne SUPERIEURE bon marche (avant deduplication, voir
     * upperBoundSignatureCount()), jamais au compte reel -- un chevalet refuse ne
     * declenche donc jamais la generation qu'il est cense eviter.
     */
    public const SIGNATURE_CEILING = 65_000;

    /** Limite d'affichage validee par le coordinateur. */
    public const DISPLAY_LIMIT = 300;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Normalise l'entree brute et resout le chevalet correspondant.
     *
     * Renvoie null si l'entree n'est pas un chevalet exploitable (voir
     * Rack::fromInput()) : c'est une erreur de saisie, pas un resultat de recherche, et
     * elle n'engendre aucune requete SQLite. Au routeur de traduire ce null en reponse
     * 404, meme convention que TermLookup::find().
     */
    public function solve(string $rawInput): ?RackPage
    {
        $rack = Rack::fromInput($rawInput);

        if ($rack === null) {
            return null;
        }

        if (self::upperBoundSignatureCount($rack) > self::SIGNATURE_CEILING) {
            return new RackPage(
                slug: $rack->slug,
                letterCounts: $rack->letterCounts,
                jokerCount: $rack->jokerCount,
                capped: true,
                matches: [],
                totalMatches: null,
                truncated: false,
                displayLimit: self::DISPLAY_LIMIT,
                candidateSignatureCount: 0,
                queryCount: 0,
            );
        }

        $signatures = self::candidateSignatures($rack);
        [$rows, $queryCount] = $this->fetchMatches($signatures);

        usort($rows, static function (array $a, array $b): int {
            $cmp = (int) $b['score'] <=> (int) $a['score'];

            if ($cmp === 0) {
                $cmp = (int) $b['length'] <=> (int) $a['length'];
            }

            if ($cmp === 0) {
                $cmp = $a['normalized'] <=> $b['normalized'];
            }

            return $cmp;
        });

        $total = count($rows);
        $limited = array_slice($rows, 0, self::DISPLAY_LIMIT);

        // mb_strtolower (pas strtolower) : voir TermLookup::find() pour la meme raison.
        $matches = array_map(static fn (array $row): array => [
            'normalized' => $row['normalized'],
            'slug' => mb_strtolower($row['normalized'], 'UTF-8'),
            'score' => (int) $row['score'],
            'length' => (int) $row['length'],
            'isOds8' => (int) $row['is_ods8'] === 1,
            'isOds9' => (int) $row['is_ods9'] === 1,
        ], $limited);

        return new RackPage(
            slug: $rack->slug,
            letterCounts: $rack->letterCounts,
            jokerCount: $rack->jokerCount,
            capped: false,
            matches: $matches,
            totalMatches: $total,
            truncated: $total > self::DISPLAY_LIMIT,
            displayLimit: self::DISPLAY_LIMIT,
            candidateSignatureCount: count($signatures),
            queryCount: $queryCount,
        );
    }

    /**
     * ADAPTATION ALLEMANDE : ALPHABET_SIZE = 29 (A-Z + Ä/Ö/Ü), pas 26 -- un joker allemand
     * doit pouvoir representer N'IMPORTE laquelle des 29 lettres du jeu, Ä/Ö/Ü comprises
     * (lettres distinctes, pas des variantes de A/O/U, voir app/Search/Normalizer.php).
     * Omettre Ä/Ö/Ü des remplissages de joker ferait manquer silencieusement tout mot
     * valide dont la seule occurrence de Ä/Ö/Ü provient d'un joker -- correctif de fond,
     * pas cosmetique.
     */
    private const ALPHABET_SIZE = 29;

    /**
     * Borne superieure bon marche (aucun appel base, formule fermee, O(ALPHABET_SIZE)) :
     * nombre de sous-multiensembles connus (produit des multiplicites + 1, incluant le
     * sous-ensemble vide) multiplie par la somme, pour 0 a $rack->jokerCount jokers, des
     * remplissages possibles (combinaisons AVEC repetition parmi ALPHABET_SIZE lettres).
     * Toujours superieure ou egale au compte reel deduplique -- jamais l'inverse --
     * puisqu'elle ignore les collisions entre combinaisons distinctes qui produisent la
     * meme signature triee.
     */
    public static function upperBoundSignatureCount(Rack $rack): int
    {
        $subsetCount = 1;

        foreach ($rack->letterCounts as $count) {
            $subsetCount *= ($count + 1);
        }

        $jokerFillingsSum = 0;

        for ($j = 0; $j <= $rack->jokerCount; $j++) {
            $jokerFillingsSum += self::multisetCount(self::ALPHABET_SIZE, $j);
        }

        return $subsetCount * $jokerFillingsSum;
    }

    /** Nombre de multiensembles de taille $k choisis parmi $n types : C(n + k - 1, k). */
    private static function multisetCount(int $n, int $k): int
    {
        if ($k === 0) {
            return 1;
        }

        return self::binomial($n + $k - 1, $k);
    }

    private static function binomial(int $n, int $k): int
    {
        if ($k < 0 || $k > $n) {
            return 0;
        }

        $k = min($k, $n - $k);
        $result = 1;

        for ($i = 0; $i < $k; $i++) {
            $result = intdiv($result * ($n - $i), $i + 1);
        }

        return $result;
    }

    /**
     * Genere puis deduplique les signatures candidates. N'est appelee que lorsque
     * upperBoundSignatureCount() est deja sous SIGNATURE_CEILING : la generation reelle
     * est donc elle-meme bornee par construction.
     *
     * @return list<string> signatures candidates, dedupliquees
     */
    private static function candidateSignatures(Rack $rack): array
    {
        $knownSubsets = self::knownLetterSubsets($rack->letterCounts);
        $jokerFillings = self::jokerFillingsUpTo($rack->jokerCount);

        $signatures = [];

        foreach ($knownSubsets as $subset) {
            // mb_strlen (pas strlen) : $subset peut contenir Ä/Ö/Ü (deux octets UTF-8
            // chacune) -- strlen() compterait des octets, faussant la borne MAX_LENGTH.
            $subsetLength = mb_strlen($subset);

            for ($j = 0; $j <= $rack->jokerCount; $j++) {
                $length = $subsetLength + $j;

                if ($length < Normalizer::MIN_LENGTH || $length > Normalizer::MAX_LENGTH) {
                    continue;
                }

                foreach ($jokerFillings[$j] as $filling) {
                    $signatures[self::mergeSorted($subset, $filling)] = true;
                }
            }
        }

        return array_keys($signatures);
    }

    /**
     * Tous les sous-multiensembles connus, deja tries alphabetiquement : les lettres
     * sont traitees dans l'ordre croissant (Rack::fromInput trie $letterCounts par cle)
     * et chaque copie choisie est ajoutee en fin de chaine, donc jamais avant une lettre
     * deja placee -- le resultat est trie sans appel supplementaire a sort().
     *
     * @param array<string, int> $letterCounts deja triees par cle
     * @return list<string>
     */
    private static function knownLetterSubsets(array $letterCounts): array
    {
        $subsets = [''];

        foreach ($letterCounts as $letter => $count) {
            $next = [];

            foreach ($subsets as $prefix) {
                for ($k = 0; $k <= $count; $k++) {
                    $next[] = $prefix . str_repeat($letter, $k);
                }
            }

            $subsets = $next;
        }

        return $subsets;
    }

    /**
     * @return array<int, list<string>> remplissages tries pour 0, 1 et (si demande) 2
     *         jokers -- respectivement 1, ALPHABET_SIZE (29) puis 435 chaines.
     *         ADAPTATION ALLEMANDE : alphabet etendu a 29 lettres (A-Z + Ä/Ö/Ü, ajoutees
     *         APRES Z pour rester coherent avec l'ordre BINARY utilise partout ailleurs --
     *         signature triee via SORT_STRING, index reversed -- ou Ä/Ö/Ü, codees sur deux
     *         octets UTF-8, trient deja apres Z par construction). Un joker doit pouvoir
     *         representer N'IMPORTE quelle lettre du jeu, Ä/Ö/Ü comprises -- les omettre
     *         ferait manquer silencieusement des mots valides.
     */
    private static function jokerFillingsUpTo(int $maxJokers): array
    {
        $result = [0 => ['']];

        if ($maxJokers < 1) {
            return $result;
        }

        $alphabet = array_merge(range('A', 'Z'), ['Ä', 'Ö', 'Ü']);
        $result[1] = $alphabet;

        if ($maxJokers < 2) {
            return $result;
        }

        $pairs = [];

        foreach ($alphabet as $i => $a) {
            foreach ($alphabet as $j => $b) {
                if ($j < $i) {
                    continue;
                }

                $pairs[] = $a . $b;
            }
        }

        $result[2] = $pairs;

        return $result;
    }

    /**
     * Fusion de deux chaines deja triees en une seule chaine triee. $a et $b font
     * chacune au plus Normalizer::MAX_LENGTH caracteres : un tri PHP standard sur une
     * chaine aussi courte est trivial, pas la peine d'ecrire une fusion lineaire
     * manuelle pour ce volume (mesure : cout negligeable devant les requetes SQLite,
     * voir reports/query-plans/phase2.md).
     */
    private static function mergeSorted(string $a, string $b): string
    {
        if ($b === '') {
            return $a;
        }

        // mb_str_split (pas str_split) : $a/$b peuvent contenir Ä/Ö/Ü (deux octets UTF-8
        // chacune) -- str_split() les aurait coupees en deux octets isoles.
        $chars = mb_str_split($a . $b);
        sort($chars, SORT_STRING);

        return implode('', $chars);
    }

    /**
     * Requetes chunkees `signature IN (...)`, un statement prepare reutilise par taille
     * de lot rencontree (au plus deux tailles distinctes en pratique : CHUNK_SIZE pour
     * les lots pleins, le reste pour le dernier lot).
     *
     * @param list<string> $signatures
     * @return array{0: list<array{normalized: string, score: string|int, length: string|int, is_ods8: string|int, is_ods9: string|int}>, 1: int}
     */
    private function fetchMatches(array $signatures): array
    {
        if ($signatures === []) {
            return [[], 0];
        }

        $pdo = $this->connection->pdo();
        $statementCache = [];
        $rows = [];
        $queryCount = 0;

        foreach (array_chunk($signatures, self::CHUNK_SIZE) as $chunk) {
            $count = count($chunk);

            if (!isset($statementCache[$count])) {
                $placeholders = implode(',', array_fill(0, $count, '?'));
                // is_admitted AS is_ods8/is_ods9 : voir docblock de classe (ADAPTATION
                // ALLEMANDE) -- un seul indicateur reel, aliase deux fois pour la
                // compatibilite du tableau ->matches avec app/View/play.php.
                $statementCache[$count] = $pdo->prepare(
                    'SELECT normalized, score, length, is_admitted AS is_ods8, is_admitted AS is_ods9 FROM terms '
                    . "WHERE signature IN ($placeholders) AND is_admitted = 1"
                );
            }

            $statement = $statementCache[$count];
            $statement->execute($chunk);

            foreach ($statement->fetchAll() as $row) {
                $rows[] = $row;
            }

            $queryCount++;
        }

        return [$rows, $queryCount];
    }
}
