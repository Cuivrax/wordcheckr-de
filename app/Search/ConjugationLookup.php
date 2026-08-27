<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Liens de conjugaison de la fiche mot /mot/{mot}, pour tout terme TROUVE (admis ou non).
 *
 * ADAPTATION ALLEMANDE (voir schema.sql, CLAUDE.md) : aucune source de conjugaison allemande
 * retenue dans cette passe (hors perimetre explicite de la tache), aucune table verb_forms
 * dans le schema allemand (contrairement au schema francais dont cette classe est issue).
 * find() COURT-CIRCUITE donc entierement la requete SQLite et renvoie toujours une Conjugation
 * VIDE (asLemma: [], asForm: [], queryCount: 0), SANS jamais ouvrir de curseur -- meme raison
 * et meme convention que SenseLookup::find() (voir son docblock pour le detail complet du
 * choix). public/index.php (fichier partage, hors perimetre de cet agent) continue d'appeler
 * ConjugationLookup::find() sans condition pour CHAQUE mot trouve -- garder cette classe et sa
 * signature intactes evite de toucher un fichier partage : app/View/word.php recoit une
 * Conjugation avec ->asLemma === [] et ->asForm === [] et n'affiche simplement aucune section
 * de conjugaison (meme comportement deja natif du gabarit pour tout mot francais qui n'est pas
 * un verbe -- "aucune section vide" est deja la convention par defaut).
 */
final class ConjugationLookup
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function find(string $normalized): Conjugation
    {
        unset($normalized); // Parametre conserve pour compatibilite d'appel, jamais utilise.
        // $this->connection n'est jamais lu : aucune requete SQLite n'est executee ici.

        return new Conjugation(asLemma: [], asForm: [], queryCount: 0);
    }
}
