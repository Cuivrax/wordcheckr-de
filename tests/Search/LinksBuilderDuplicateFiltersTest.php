<?php

declare(strict_types=1);

use Tests\Support\Assert;

/**
 * Garde de regression pour le correctif C2 (audit NO GO, 2026-08-31) : toutes les constantes
 * `*DUPLICATE*_KEYS`/`EXTERNAL_DUPLICATE_PREFIXES`/`EXTERNAL_DUPLICATE_SUFFIXES` des builders de
 * maillage `App\Search\*LinksBuilder` DOIVENT rester des tableaux VIDES sur ce depot -- elles
 * etaient toutes calculees sur storage/dictionary_fr.sqlite (838 180 termes francais) et copiees
 * telles quelles lors du portage du depot (git archive), jamais revalidees pour l'allemand.
 * Filtrer avec des donnees d'une autre langue amputait le maillage de pages allemandes deja
 * indexees par pure coincidence de format de cle (ex. 'W:L' francais retirait a tort le lien vers
 * la page allemande /woerter/beginnend-mit/w/endend-mit/l, 290 mots reels) -- voir le rapport
 * AFTER de cette tache pour le detail (nom de constante, fichier, nombre d'entrees retirees) et
 * `App\Search\SuffixExtensionLinksBuilder::EXTERNAL_DUPLICATE_SUFFIXES` (D-DE-024) pour le
 * precedent deja etabli dans ce depot.
 *
 * Ce test ne verifie PAS que le calcul d'un futur equivalent allemand est correct (aucun calcul
 * de ce type n'existe a ce jour, voir chaque docblock de constante) -- seulement qu'un futur
 * portage accidentel depuis le francais (ex. un nouveau `git cherry-pick`/merge depuis le depot
 * cousin) ne repeuple pas silencieusement une de ces listes sans revalidation explicite. Toute
 * constante NON VIDE ici doit faire l'objet d'une entree docs/DECISIONS.md dediee AVANT d'etre
 * ajoutee a la liste ci-dessous comme "attendue peuplee".
 *
 * Utilise ReflectionClass plutot qu'une liste de valeurs codees en dur : verifie TOUTE constante
 * dont le nom contient "DUPLICATE" sur chaque classe listee, y compris une constante future qui
 * n'existe pas encore aujourd'hui -- un oubli de nommage ne pourrait pas se glisser sous le radar
 * de ce test comme il le pourrait avec une liste de noms explicites.
 */
return function (): void {
    $classes = [
        \App\Search\LengthLinksBuilder::class,
        \App\Search\LetterCombinedLinksBuilder::class,
        \App\Search\LengthCombinedLinksBuilder::class,
        \App\Search\PositionLinksBuilder::class,
        \App\Search\StartEndWithLinksBuilder::class,
        \App\Search\AvecTwoLettersLinksBuilder::class,
        \App\Search\AvecThreeLettersLinksBuilder::class,
        \App\Search\PrefixAvecLinksBuilder::class,
        \App\Search\PrefixExtensionLinksBuilder::class,
        \App\Search\SuffixExtensionLinksBuilder::class,
    ];

    $checked = 0;

    foreach ($classes as $class) {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getReflectionConstants() as $constant) {
            $name = $constant->getName();

            if (!str_contains($name, 'DUPLICATE')) {
                continue;
            }

            /** @var mixed $value */
            $value = $constant->getValue();

            Assert::true(
                is_array($value),
                "{$class}::{$name} devrait etre un tableau",
            );
            Assert::same(
                [],
                $value,
                "{$class}::{$name} doit rester vide sur ce depot allemand (correctif C2) -- "
                . 'liste calculee sur des donnees francaises, jamais revalidee pour ce depot',
            );

            $checked++;
        }
    }

    // Garde-fou du garde-fou : si ce nombre baisse, une classe/constante a disparu du perimetre
    // sans que ce test ait ete mis a jour consciemment -- 20 constantes exactement au 2026-08-31
    // (15 videes par le correctif C2, 5 deja vides avant ce correctif, voir le rapport AFTER).
    Assert::same(20, $checked, 'nombre de constantes DUPLICATE* inspectees inattendu -- perimetre a revalider');
};
