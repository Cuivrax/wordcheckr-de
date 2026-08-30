<?php

declare(strict_types=1);

use App\Seo\Family;
use Tests\Support\Assert;

/**
 * App\Seo\Family : liste fermee des familles de reporting/gouvernance et la regle dure qui en
 * decoule (combinaisons infinies jamais dans un sitemap) -- verifie ici independamment de toute
 * base de donnees, meme esprit que tests/Search/WordListFiltersTest.php pour
 * App\Search\WordListFilters.
 *
 * PAS de test pour un plafond "non admis en masse" (contrairement aux depots francais/espagnol
 * cousins) : le modele de donnees allemand n'a, dans cette premiere passe, aucune famille de ce
 * type (app/Seo/Family.php, CLAUDE.md "Modele A Statuts").
 */
return function (): void {
    Assert::true(Family::isValid(Family::HOME));
    Assert::true(Family::isValid(Family::WORD_ADMITTED));
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
};
