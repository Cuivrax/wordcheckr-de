<?php

declare(strict_types=1);

use App\Search\Normalizer;
use Tests\Support\Assert;

/**
 * Compare App\Search\Normalizer a scripts/lib/normalize.py (D-009) sur un echantillon
 * de cas adversariaux specifiques au site allemand (Eszett, Ä/Ö/Ü, emprunts etrangers,
 * bornes de longueur -- voir scripts/build_normalize_fixture.py), genere depuis le
 * script Python -- la fixture est committee, ce test n'invoque jamais Python (D-007 :
 * la couche runtime PHP reste independante).
 */
return function (): void {
    $fixturePath = __DIR__ . '/../fixtures/normalize_samples.json';

    Assert::true(
        is_file($fixturePath),
        'fixture manquante : ' . $fixturePath . ' -- lancer python scripts/build_normalize_fixture.py'
    );

    $cases = json_decode((string) file_get_contents($fixturePath), true, flags: JSON_THROW_ON_ERROR);
    Assert::true(count($cases) > 0, 'fixture vide');

    $siteConfig = require __DIR__ . '/../../config/sites/de.php';
    $tileScores = $siteConfig['tile_scores'];

    foreach ($cases as $case) {
        $raw = $case['raw'];
        $normalized = Normalizer::normalize($raw);

        Assert::same($case['normalized'], $normalized, 'normalize(' . json_encode($raw) . ')');

        $valid = Normalizer::isValid($normalized);
        Assert::same($case['valid'], $valid, 'isValid(' . json_encode($normalized) . ')');

        if ($valid) {
            Assert::same(
                $case['score'],
                Normalizer::score($normalized, $tileScores),
                'score(' . $normalized . ')'
            );
            Assert::same(
                $case['signature'],
                Normalizer::signature($normalized),
                'signature(' . $normalized . ')'
            );
            Assert::same(
                $case['reversed'],
                Normalizer::reverse($normalized),
                'reverse(' . $normalized . ')'
            );
        }
    }

    // Regression C1 (audit Phase 1) : des octets UTF-8 invalides (ex. 0xFF 0xFE, tels
    // que produits par /verifier?mot=%FF%FE) ne doivent jamais faire planter
    // normalize(). Avant le correctif, \Normalizer::normalize() renvoyait false,
    // transmis tel quel a preg_replace() sous strict_types -> TypeError non rattrapee.
    $invalidUtf8 = "\xFF\xFE";
    $normalizedInvalid = Normalizer::normalize($invalidUtf8);
    Assert::same('', $normalizedInvalid, 'normalize() sur des octets UTF-8 invalides doit rester une chaine vide');
    Assert::true(!Normalizer::isValid($normalizedInvalid), 'des octets UTF-8 invalides ne doivent jamais etre valides');

    // Regression C2 (audit Phase 1) : un saut de ligne ne doit jamais rendre un terme
    // valide. Avant le correctif, VALID_PATTERN ancrait avec $ (qui autorise un \n
    // final en PCRE), acceptant a tort "POSER\n" comme si c'etait "POSER".
    Assert::true(!Normalizer::isValid('SPIELEN' . "\n"), 'SPIELEN suivi d\'un saut de ligne doit rester invalide');
    Assert::true(!Normalizer::isValid("\n" . 'SPIELEN'), 'un saut de ligne en tete doit rester invalide');
    Assert::true(Normalizer::isValid('SPIELEN'), 'SPIELEN seul doit rester valide (non-regression du correctif \\z)');

    // Meme regression C2, mais avec Ä/Ö/Ü en derniere position -- verifie que le
    // modificateur /u ajoute a VALID_PATTERN (specifique a l'allemand) ancre bien \z sur
    // le CODEPOINT final, pas sur le dernier OCTET d'une sequence UTF-8 multioctet (un
    // bug plausible si /u avait ete oublie en meme temps que la classe [A-ZÄÖÜ]).
    Assert::true(Normalizer::isValid('SCHÖN'), 'SCHÖN (Ö final) doit etre valide');
    Assert::true(!Normalizer::isValid('SCHÖN' . "\n"), 'SCHÖN suivi d\'un saut de ligne doit rester invalide');

    // Regression specifique au site allemand (coeur de cette tache) : Ä/Ö/Ü ne doivent
    // JAMAIS se replier sur A/O/U -- avant correctif, le retrait des marques Unicode Mn
    // apres decomposition NFD effacait silencieusement le trema (Ä -> A + combining
    // diaeresis -> A). Verifie ici explicitement, pas seulement via la fixture, pour que
    // ce test reste comprehensible seul comme preuve du correctif.
    Assert::same('SCHÖN', Normalizer::normalize('schön'), 'Ö ne doit pas se replier sur O');
    Assert::same('SCHON', Normalizer::normalize('schon'), 'SCHON (sans trema) doit rester distinct de SCHÖN');
    Assert::true(
        Normalizer::normalize('schön') !== Normalizer::normalize('schon'),
        'schön et schon sont deux mots allemands distincts, jamais la meme forme normalisee'
    );
    Assert::same('MÄHNE', Normalizer::normalize('Mähne'), 'Ä ne doit pas se replier sur A');
    Assert::same('ÜBEL', Normalizer::normalize('Übel'), 'Ü ne doit pas se replier sur U');

    // Regression specifique au site allemand : ß (Eszett) doit etre ACCEPTE (converti en
    // SS), jamais rejete -- avant correctif, ß n'a aucune decomposition NFD, traverse le
    // pipeline inchange, puis echoue [A-Z] apres mise en majuscules (devient ẞ, hors
    // classe). Des mots parmi les plus courants de la langue (Straße, groß, Fuß, weiß)
    // auraient ete rejetes comme invalides plutot que correctement notes.
    Assert::true(Normalizer::isValid(Normalizer::normalize('Straße')), 'Straße doit etre accepte, pas rejete');
    Assert::same('STRASSE', Normalizer::normalize('Straße'));
    Assert::same('SSUSSE', Normalizer::normalize('ẞUSSE'), 'ẞ (Eszett majuscule) converti en SS comme ß');
};
