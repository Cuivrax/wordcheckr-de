<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Definitions de la fiche mot /mot/{mot}, pour tout terme TROUVE (admis ou non).
 *
 * ADAPTATION ALLEMANDE (voir schema.sql, CLAUDE.md) : "definitions/POS OUT OF SCOPE" pour
 * cette passe -- aucun pipeline de definitions allemand construit, aucune table word_senses
 * dans le schema allemand (contrairement au schema francais dont cette classe est issue).
 * find() COURT-CIRCUITE donc entierement la requete SQLite et renvoie toujours un WordSenses
 * VIDE (senses: [], queryCount: 0), SANS jamais ouvrir de curseur -- ce choix (plutot que de
 * garder une table word_senses vide en base) evite d'ajouter au schema une table sans aucune
 * donnee source ni pipeline de generation reel, conformement a la consigne explicite recue
 * ("do not add that table"). public/index.php (fichier partage, hors perimetre de cet agent)
 * continue d'appeler SenseLookup::find() sans condition pour CHAQUE mot trouve -- garder cette
 * classe et sa signature intactes (plutot que de la supprimer et modifier public/index.php)
 * evite de toucher un fichier partage pour un changement qui n'etait pas necessaire : le
 * court-circuit suffit, app/View/word.php recoit un WordSenses avec ->senses === [] et
 * n'affiche simplement aucune carte de definition (meme comportement deja natif du gabarit
 * pour les mots francais qui n'ont pas encore de sens genere -- "aucune section vide" est deja
 * la convention par defaut, pas une exception ajoutee ici).
 */
final class SenseLookup
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function find(string $normalized): WordSenses
    {
        unset($normalized); // Parametre conserve pour compatibilite d'appel, jamais utilise.
        // $this->connection n'est jamais lu : aucune requete SQLite n'est executee ici.

        return new WordSenses(senses: [], queryCount: 0);
    }
}
