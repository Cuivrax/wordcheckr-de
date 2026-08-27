<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\Rack;
use App\Search\RackSolver;
use Tests\Support\Assert;

/**
 * Exerce App\Search\RackSolver sur la vraie base storage/dictionary_de.sqlite (lecture
 * seule) : correction croisee par force brute pour un chevalet connu, comportement du
 * plafond de securite, et le pire cas re-mesure pour l'alphabet allemand a 29 lettres
 * (RackSolver::ALPHABET_SIZE, voir son docblock de classe).
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $connection = new Connection($dbPath);
    $solver = new RackSolver($connection);

    // --- Entree invalide : aucun chevalet, meme convention que TermLookup::find(). ---
    Assert::null($solver->solve(''), 'entree vide');
    Assert::null($solver->solve('re3sen'), 'chiffre dans l\'entree');
    Assert::null($solver->solve('re***n'), 'trois jokers, au-dessus de Rack::MAX_JOKERS');
    Assert::null($solver->solve(str_repeat('a', 16)), '16 lettres, au-dessus de la borne D-010');

    // --- Correction, verifiee par force brute (pas un echantillon) : chevalet REISEN, ---
    // --- sans joker (R,E,I,S,E,N -- E en double). Tout mot admis de longueur <= 6 dont ---
    // --- chaque lettre est disponible en quantite suffisante doit apparaitre, et aucun ---
    // --- autre. ---
    $page = $solver->solve('reisen');
    Assert::notNull($page);
    Assert::true(!$page->capped);
    Assert::same('eeinrs', $page->slug, 'slug canonique = lettres triees, pas l\'ordre de saisie');
    Assert::same(0, $page->jokerCount);

    $pdo = $connection->pdo();
    $statement = $pdo->query('SELECT normalized FROM terms WHERE length <= 6 AND is_admitted = 1');
    $rackCounts = ['R' => 1, 'E' => 2, 'I' => 1, 'S' => 1, 'N' => 1];
    $bruteForce = [];
    foreach ($statement as $row) {
        $word = $row['normalized'];
        // mb_str_split (pas str_split) : par coherence avec RackSolver lui-meme, meme si
        // aucune lettre de ce chevalet particulier n'est Ä/Ö/Ü.
        $counts = array_count_values(mb_str_split($word));
        $fits = true;
        foreach ($counts as $letter => $count) {
            if (!isset($rackCounts[$letter]) || $count > $rackCounts[$letter]) {
                $fits = false;
                break;
            }
        }
        if ($fits) {
            $bruteForce[] = $word;
        }
    }
    sort($bruteForce);

    $solverWords = array_column($page->matches, 'normalized');
    sort($solverWords);

    Assert::same(70, count($bruteForce), 'nombre de mots attendus par force brute pour le chevalet REISEN (verifie a la main)');
    Assert::same($bruteForce, $solverWords, 'RackSolver doit trouver exactement les memes mots que la verification par force brute');
    Assert::true(in_array('REISEN', $solverWords, true), 'le mot REISEN lui-meme doit apparaitre (anagramme exacte du chevalet complet)');

    // Tri : score decroissant, puis longueur decroissante, puis alphabetique. Plusieurs
    // anagrammes de 6 lettres partagent le meme score (6) : EIERNS precede les autres par
    // ordre alphabetique, c'est EIERNS qui doit ouvrir la liste.
    $first = $page->matches[0];
    Assert::same('EIERNS', $first['normalized']);
    Assert::same(6, $first['score']);
    Assert::same(6, $first['length']);
    for ($i = 1; $i < count($page->matches); $i++) {
        $previous = $page->matches[$i - 1];
        $current = $page->matches[$i];
        $orderOk = $previous['score'] > $current['score']
            || ($previous['score'] === $current['score'] && $previous['length'] > $current['length'])
            || ($previous['score'] === $current['score'] && $previous['length'] === $current['length']
                && $previous['normalized'] <= $current['normalized']);
        Assert::true($orderOk, 'ordre invalide entre ' . $previous['normalized'] . ' et ' . $current['normalized']);
    }

    // Chaque correspondance est necessairement admise (is_admitted = 1, schema.sql) --
    // "quel mot puis-je jouer" ne repond qu'avec des mots jouables.
    foreach ($page->matches as $match) {
        Assert::true($match['isOds8'] || $match['isOds9'], $match['normalized'] . ' devrait etre admis');
    }

    Assert::true($page->queryCount <= 10, 'budget de requetes indexees depasse pour un chevalet de 6 lettres sans joker');

    // --- 1 joker : REISEN doit rester atteignable (R,E,I,S,E + 1 joker valant N). ---
    $withJoker = $solver->solve('reise?');
    Assert::notNull($withJoker);
    Assert::true(!$withJoker->capped);
    Assert::same(1, $withJoker->jokerCount);
    Assert::true(
        in_array('REISEN', array_column($withJoker->matches, 'normalized'), true),
        'REISEN doit etre atteignable avec R,E,I,S,E + 1 joker'
    );

    // --- Redirection canonique : '?' et '*' doivent produire le meme slug. ---
    $withStar = $solver->solve('reise*');
    Assert::notNull($withStar);
    Assert::same($withJoker->slug, $withStar->slug, "? et * doivent produire le meme chevalet, donc le meme slug canonique ('*')");

    // --- Joker representant Ä/Ö/Ü : coeur de l'adaptation allemande (RackSolver::
    // --- ALPHABET_SIZE = 29, pas 26) -- SCHÖN doit etre atteignable avec S,C,H,N + 1
    // --- joker valant Ö, exactement comme n'importe quelle lettre A-Z. Sans ce
    // --- correctif, jokerFillingsUpTo() n'aurait jamais genere Ö comme remplissage
    // --- possible et ce mot manquerait silencieusement.
    $jokerAsUmlaut = $solver->solve('schn?');
    Assert::notNull($jokerAsUmlaut);
    Assert::true(!$jokerAsUmlaut->capped);
    Assert::true(
        in_array('SCHÖN', array_column($jokerAsUmlaut->matches, 'normalized'), true),
        'SCHÖN doit etre atteignable avec S,C,H,N + 1 joker representant Ö'
    );

    // --- Pire cas re-mesure pour l'alphabet allemand (29 lettres) : 7 lettres distinctes
    // --- + 2 jokers, aucune contrainte -- voir RackSolver::SIGNATURE_CEILING (releve a
    // --- 65 000, docblock complet des mesures). Doit rester sous le plafond de securite
    // --- et repondre dans un temps raisonnable, toujours via l'index signature.
    $worstNamed = Rack::fromInput('aeiornt**');
    Assert::notNull($worstNamed);
    $upperBound = RackSolver::upperBoundSignatureCount($worstNamed);
    Assert::true($upperBound <= RackSolver::SIGNATURE_CEILING, 'le pire cas nomme (7 lettres + 2 jokers) doit rester sous le plafond de securite, obtenu upperBound=' . $upperBound);

    $start = hrtime(true);
    $worstPage = $solver->solve('aeiornt**');
    $elapsedMs = (hrtime(true) - $start) / 1e6;

    Assert::notNull($worstPage);
    Assert::true(!$worstPage->capped, 'le pire cas nomme (7 lettres + 2 jokers) ne doit pas declencher le plafond');
    Assert::same(2, $worstPage->jokerCount);
    Assert::true($worstPage->queryCount <= 10, 'le pire cas nomme doit rester sous 10 requetes avec CHUNK_SIZE = 5000, obtenu : ' . $worstPage->queryCount);
    Assert::true($worstPage->candidateSignatureCount > 30000, 'sanity check : le pire cas doit bien engendrer des dizaines de milliers de signatures candidates');
    Assert::true(count($worstPage->matches) === $worstPage->displayLimit, 'le pire cas nomme doit produire plus de resultats que la limite d\'affichage');
    Assert::true($worstPage->truncated, 'le pire cas nomme doit etre marque tronque');
    Assert::true($elapsedMs < 1000.0, 'le pire cas nomme doit repondre en moins d\'une seconde, obtenu : ' . $elapsedMs . ' ms');

    // --- Plafond de securite : un chevalet de 8 lettres distinctes + 2 jokers (avec ---
    // --- l'alphabet a 29 lettres) doit depasser le plafond et etre refuse AVANT toute ---
    // --- generation ou requete -- pas une erreur, un resultat distinct. ---
    $tooLarge = Rack::fromInput('eiralnts**');
    Assert::notNull($tooLarge, 'chevalet syntaxiquement valide : 8 lettres + 2 jokers = 10 caracteres, sous la borne D-010');
    Assert::true(
        RackSolver::upperBoundSignatureCount($tooLarge) > RackSolver::SIGNATURE_CEILING,
        'ce chevalet doit depasser le plafond de securite (verification de coherence du test lui-meme)'
    );

    $cappedPage = $solver->solve('eiralnts**');
    Assert::notNull($cappedPage, 'un chevalet trop grand est un resultat distinct, pas une entree invalide -> jamais null');
    Assert::true($cappedPage->capped, 'doit declencher le plafond de securite');
    Assert::same([], $cappedPage->matches, 'aucune correspondance calculee quand le plafond est declenche');
    Assert::null($cappedPage->totalMatches, 'totalMatches doit rester null (inconnu), jamais 0 (qui signifierait "aucun resultat trouve")');
    Assert::same(0, $cappedPage->queryCount, 'aucune requete SQLite ne doit etre executee quand le plafond est declenche');
    Assert::same(0, $cappedPage->candidateSignatureCount, 'aucune signature ne doit etre generee quand le plafond est declenche');
};
