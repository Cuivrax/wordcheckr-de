<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Fiche mot consommee directement par la couche de rendu (app/View/, hors perimetre de
 * cet agent).
 *
 * N'existe que pour une entree dont la forme est syntaxiquement valide : un terme de
 * forme invalide n'atteint jamais ce point (voir TermLookup::find(), qui renvoie null
 * avant toute construction de fiche).
 *
 * Le modele a trois statuts est ferme (CLAUDE.md) : $status ne prend jamais que l'une
 * des trois constantes STATUS_* ci-dessous. STATUS_FRENCH_NOT_ADMITTED (nom herite du
 * depot francais, jamais renomme ici -- identifiant PHP interne uniquement, ne fuite
 * jamais vers le HTML : voir app/View/word.php, qui le mappe vers modifier='not-admitted'/
 * badge='Nicht Gültig', deja en allemand correct) EST PRODUIT depuis D-DE-029 : la
 * troisieme source (is_german, kaikki.org/dewiktionary) alimente reellement ce statut
 * (236 909 formes allemandes reelles non admises, is_admitted=0 AND is_german=1) --
 * TermLookup::find() le derive deja correctement depuis is_admitted (jamais is_german
 * directement), aucune modification necessaire a ce fichier ni a TermLookup.php.
 *
 * display_term == normalized sur toute ligne de la base : un seul champ "normalized" est
 * expose, pas de doublon display/normalized.
 *
 * isOds8/isOds9 : NOMS HERITES du site francais (double lexique ODS8/ODS9), conserves
 * TELS QUELS ici uniquement pour que app/View/word.php et app/View/play.php (hors
 * perimetre de cet agent) continuent de fonctionner sans modification -- TermLookup leur
 * assigne desormais la MEME valeur (is_admitted, lexique allemand unique). Consequence
 * SIGNALEE explicitement (rapport AFTER) : ces deux champs produisent deux pastilles
 * "ODS8"/"ODS9" identiques et incorrectes pour l'allemand tant qu'un passage frontend/
 * microcopy dedie n'aura pas adapte ce gabarit vers une seule pastille "admis" -- pas
 * fait ici, hors perimetre data-engine.
 *
 * pos/posSecondary/gender : TOUJOURS NULS sur le site allemand (aucune source de nature
 * grammaticale retenue cette passe, aucune colonne correspondante dans schema.sql
 * allemand) -- champs conserves uniquement pour compatibilite avec app/View/word.php, qui
 * gere deja nativement ce cas (meme convention "pas de section vide" que le site
 * francais, ou ~12,3 % des termes sont deja dans cet etat).
 */
final class TermPage
{
    public const STATUS_ADMITTED = 'admitted';
    public const STATUS_FRENCH_NOT_ADMITTED = 'french_not_admitted';
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * @param list<array{letter: string, value: int}> $letters lettre et valeur de
     *        tuile, dans l'ordre du mot ; la somme des valeurs egale toujours $score
     * @param string|null $previousWord forme normalisee (majuscules) du mot
     *        alphabetiquement precedent en base, ou null en debut de base
     * @param string|null $nextWord forme normalisee du mot alphabetiquement suivant,
     *        ou null en fin de base
     * @param string|null $pos nature grammaticale primaire (D-018), null si non trouve
     *        ou non couvert par Kartmaan
     * @param string|null $posSecondary second sens grammaticale distinct, si l'homographe
     *        en porte un (ex. TABLE : pos=N, posSecondary=V)
     * @param string|null $gender 'm', 'f' ou 'e', null si non applicable ou non couvert
     */
    public function __construct(
        public readonly string $normalized,
        public readonly string $slug,
        public readonly bool $found,
        public readonly string $status,
        public readonly int $score,
        public readonly int $length,
        public readonly bool $isOds8,
        public readonly bool $isOds9,
        public readonly array $letters,
        public readonly ?string $previousWord,
        public readonly ?string $nextWord,
        public readonly ?string $pos = null,
        public readonly ?string $posSecondary = null,
        public readonly ?string $gender = null,
    ) {
    }
}
