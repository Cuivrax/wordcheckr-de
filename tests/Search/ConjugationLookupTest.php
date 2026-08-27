<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\ConjugationLookup;
use Tests\Support\Assert;

/**
 * ADAPTATION ALLEMANDE : ConjugationLookup::find() est COURT-CIRCUITE (voir son docblock
 * de classe) -- aucune source de conjugaison allemande retenue cette passe, aucune table
 * verb_forms dans schema.sql. Ce test verifie donc le contrat degrade (toujours vide,
 * jamais une requete SQLite), pas un contenu genere -- contrairement au test francais dont
 * ce fichier est issu, qui verifiait des liens de conjugaison reels sur
 * storage/dictionary_fr.sqlite.
 *
 * N'ouvre pas storage/dictionary_de.sqlite : find() ne le lit jamais, la Connection passee
 * au constructeur sert uniquement a verifier qu'elle n'est jamais sollicitee.
 */
return function (): void {
    $connection = new Connection(__DIR__ . '/../../storage/dictionary_de.sqlite');
    $lookup = new ConjugationLookup($connection);

    foreach (['SCHÖN', 'HAUS', 'STRASSE', 'ZZZQQQXXX', ''] as $word) {
        $result = $lookup->find($word);
        Assert::same([], $result->asLemma, $word . ' : asLemma doit toujours etre vide (aucune source allemande cette passe)');
        Assert::same([], $result->asForm, $word . ' : asForm doit toujours etre vide, meme raison');
        Assert::same(0, $result->queryCount, $word . ' : aucune requete SQLite ne doit jamais etre executee');
    }
};
