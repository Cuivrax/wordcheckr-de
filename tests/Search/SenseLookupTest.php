<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\SenseLookup;
use Tests\Support\Assert;

/**
 * ADAPTATION ALLEMANDE : SenseLookup::find() est COURT-CIRCUITE (voir son docblock de
 * classe) -- aucun pipeline de definitions allemand construit cette passe, aucune table
 * word_senses dans schema.sql. Ce test verifie donc le contrat degrade (toujours vide,
 * jamais une requete SQLite), pas un contenu genere -- contrairement au test francais
 * dont ce fichier est issu, qui verifiait des sens reels sur storage/dictionary_fr.sqlite.
 *
 * N'ouvre pas storage/dictionary_de.sqlite : find() ne le lit jamais, la Connection passee
 * au constructeur sert uniquement a verifier qu'elle n'est jamais sollicitee.
 */
return function (): void {
    $connection = new Connection(__DIR__ . '/../../storage/dictionary_de.sqlite');
    $lookup = new SenseLookup($connection);

    foreach (['SCHÖN', 'HAUS', 'STRASSE', 'ZZZQQQXXX', ''] as $word) {
        $result = $lookup->find($word);
        Assert::same([], $result->senses, $word . ' : senses doit toujours etre vide (aucune source allemande cette passe)');
        Assert::same(0, $result->queryCount, $word . ' : aucune requete SQLite ne doit jamais etre executee');
    }
};
