<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Chevalet analyse a partir de l'entree brute de /jouer/{lettres} (Phase 2, docs/08).
 *
 * Reutilise Normalizer::normalize() (D-009, source unique de la regle de normalisation)
 * sur la chaine COMPLETE, jokers inclus : '?' et '*' ne sont ni des ligatures, ni des
 * caracteres diacritiques, et ne sont pas affectes par la mise en majuscule -- ils
 * traversent normalize() sans modification. Cela evite de dupliquer la regle de
 * normalisation pour les traiter separement (meme discipline que D-009 : une seule
 * source de verite).
 *
 * Bornes (D-010, meme plafond que Normalizer::MAX_LENGTH) : de 1 a 15 cases au total
 * (lettres connues + jokers), au plus MAX_JOKERS jokers -- le sac de Scrabble allemand
 * (102 tuiles) ne contient lui aussi que deux jetons blancs, meme valeur que le sac
 * francais dont ce fichier est issu.
 *
 * Representation canonique d'URL : jokers rendus par '*', jamais '?'. Un '?' litteral
 * non encode dans une URL introduit une chaine de requete cote client -- un lien ou une
 * redirection generes avec '?' casseraient la navigation. '?' reste accepte en ENTREE
 * (docs/01_MASTER_BRIEF.md : "? et * valent joker"), mais toute forme canonique (slug,
 * redirection 301) utilise exclusivement '*'.
 */
final class Rack
{
    /** Le sac de Scrabble francais ne contient que deux jetons blancs. */
    public const MAX_JOKERS = 2;

    public const MIN_TILES = 1;

    /**
     * @param array<string, int> $letterCounts lettres A-Z connues avec leur
     *        multiplicite, triees par cle (ordre alphabetique, SORT_STRING)
     */
    private function __construct(
        public readonly array $letterCounts,
        public readonly int $jokerCount,
        public readonly string $slug,
    ) {
    }

    /**
     * Renvoie null pour toute entree qui n'est pas un chevalet exploitable : forme
     * normalisee vide, caractere hors A-Z/joker, hors bornes de taille, ou plus de
     * MAX_JOKERS jokers. Aucune exception ne remonte -- meme discipline que
     * Normalizer::normalize() (audit Phase 1, C1) : une entree utilisateur ne doit
     * jamais faire planter le flux HTTP normal.
     */
    public static function fromInput(string $rawInput): ?self
    {
        $normalized = Normalizer::normalize($rawInput);

        if ($normalized === '') {
            return null;
        }

        $jokerCount = substr_count($normalized, '?') + substr_count($normalized, '*');
        $lettersOnly = str_replace(['?', '*'], '', $normalized);

        // [A-ZÄÖÜ] (pas [A-Z] seul) + modificateur /u : Ä/Ö/Ü sont des lettres allemandes
        // valides sur un chevalet, pas des variantes de A/O/U -- meme correctif que
        // Normalizer::VALID_PATTERN, pour la meme raison (sequences UTF-8 multioctet).
        if ($lettersOnly !== '' && preg_match('/^[A-ZÄÖÜ]+\z/u', $lettersOnly) !== 1) {
            return null;
        }

        // mb_strlen (pas strlen) : $lettersOnly peut contenir Ä/Ö/Ü (deux octets UTF-8
        // chacune) -- strlen() compterait des octets, pas des cases de chevalet.
        $totalTiles = mb_strlen($lettersOnly) + $jokerCount;

        if ($totalTiles < self::MIN_TILES || $totalTiles > Normalizer::MAX_LENGTH) {
            return null;
        }

        if ($jokerCount > self::MAX_JOKERS) {
            return null;
        }

        // mb_str_split (pas str_split) : meme raison, un chevalet avec Ä/Ö/Ü serait
        // sinon compte/regroupe par octet plutot que par lettre.
        /** @var array<string, int> $letterCounts */
        $letterCounts = $lettersOnly === '' ? [] : array_count_values(mb_str_split($lettersOnly));
        ksort($letterCounts, SORT_STRING);

        return new self($letterCounts, $jokerCount, self::buildSlug($letterCounts, $jokerCount));
    }

    /**
     * @param array<string, int> $letterCounts deja triees par cle
     */
    private static function buildSlug(array $letterCounts, int $jokerCount): string
    {
        $letters = '';

        foreach ($letterCounts as $letter => $count) {
            $letters .= str_repeat($letter, $count);
        }

        // mb_strtolower (pas strtolower) : $letters peut contenir Ä/Ö/Ü -- strtolower()
        // (ASCII uniquement) les laisserait en majuscule dans le slug d'URL.
        return mb_strtolower($letters, 'UTF-8') . str_repeat('*', $jokerCount);
    }
}
