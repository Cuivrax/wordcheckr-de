<?php

declare(strict_types=1);

use App\Seo\Family;
use Tests\Support\Assert;

/**
 * App\Seo\Family : liste fermee des familles de reporting/gouvernance et les regles dures qui
 * en decoulent (combinaisons infinies jamais dans un sitemap, allemand non admis jamais en
 * masse) -- verifie ici independamment de toute base de donnees, meme esprit que
 * tests/Search/WordListFiltersTest.php pour App\Search\WordListFilters.
 */
return function (): void {
    Assert::true(Family::isValid(Family::HOME));
    Assert::true(Family::isValid(Family::WORD_ADMITTED));
    Assert::true(Family::isValid(Family::WORD_GERMAN_NOT_ADMITTED));
    Assert::true(Family::isValid(Family::WORD_LIST_LENGTH));
    Assert::true(!Family::isValid('unbekannte_familie'));
    Assert::true(!Family::isValid(''));

    // Chaque valeur de ALL doit etre reconnue par isValid() -- coherence interne.
    foreach (Family::ALL as $family) {
        Assert::true(Family::isValid($family), "famille declaree mais non reconnue : {$family}");
    }

    // Combinaisons infinies : jamais de sitemap, quel que soit le lot (R4 de
    // scripts/apply_seo_batch.php).
    $expectedForbidden = [
        Family::WORD_LIST_CONTENANT,
        Family::WORD_LIST_AVEC,
        Family::WORD_LIST_SANS,
        Family::WORD_LIST_MOTIF,
        Family::RACK,
    ];

    foreach ($expectedForbidden as $family) {
        Assert::true(Family::forbidsSitemap($family), "attendu interdit de sitemap : {$family}");
    }

    // Familles bornees par construction (jamais interdites de sitemap en principe), meme si
    // seules HOME, WORD_ADMITTED et WORD_LIST_LENGTH sont effectivement peuplees a ce stade
    // (D-DE-013).
    $expectedAllowed = [
        Family::HOME,
        Family::WORD_ADMITTED,
        Family::WORD_GERMAN_NOT_ADMITTED,
        Family::WORD_LIST_LENGTH,
        Family::WORD_LIST_COMMENCANT,
        Family::WORD_LIST_TERMINANT,
        Family::WORD_LIST_POSITION,
        Family::WORD_LIST_COMBINED,
        // D-DE-026 : sous-familles BORNEES de "avec", distinctes de Family::WORD_LIST_AVEC
        // (generique, celle-ci reste dans $expectedForbidden ci-dessus).
        Family::WORD_LIST_AVEC_SINGLE_LETTER,
        Family::WORD_LIST_AVEC_TWO_LETTERS,
        Family::WORD_LIST_AVEC_THREE_LETTERS,
    ];

    foreach ($expectedAllowed as $family) {
        Assert::true(!Family::forbidsSitemap($family), "ne devrait pas etre interdit de sitemap : {$family}");
    }

    // D-DE-029 : seule word_german_not_admitted porte la contrainte "jamais en masse".
    Assert::true(Family::isGermanNotAdmitted(Family::WORD_GERMAN_NOT_ADMITTED));

    foreach (Family::ALL as $family) {
        if ($family === Family::WORD_GERMAN_NOT_ADMITTED) {
            continue;
        }

        Assert::true(!Family::isGermanNotAdmitted($family), "ne devrait pas etre allemand non admis : {$family}");
    }

    Assert::true(Family::MAX_BATCH_SIZE_GERMAN_NOT_ADMITTED >= 236_909, 'le plafond doit couvrir le volume reel de mots allemands non admis (D-DE-029)');
};
