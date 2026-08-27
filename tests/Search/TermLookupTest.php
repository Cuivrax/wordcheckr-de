<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Search\Normalizer;
use App\Search\TermLookup;
use App\Search\TermPage;
use Tests\Support\Assert;

/**
 * Exerce App\Search\TermLookup sur la vraie base storage/dictionary_de.sqlite (lecture
 * seule) : cas connus, formes invalides, voisinage alphabetique, et verification
 * exhaustive de score/signature/reversed/length sur les 590 850 lignes reelles -- pas un
 * echantillon.
 *
 * ADAPTATION ALLEMANDE : le modele a deux statuts pour cette premiere passe (admis /
 * inconnu, voir data/raw/PROVENANCE.md) -- TermPage::STATUS_FRENCH_NOT_ADMITTED n'est
 * produit par AUCUNE donnee actuelle, ce test ne l'exerce donc jamais (contrairement au
 * test francais equivalent, qui verifiait GHOSTER comme "francais non admis"). pos/
 * posSecondary/gender sont TOUJOURS nuls (aucune source de nature grammaticale allemande
 * retenue cette passe, voir schema.sql) -- verifie explicitement ci-dessous, pas juste
 * omis.
 */
return function (): void {
    $dbPath = __DIR__ . '/../../storage/dictionary_de.sqlite';
    Assert::true(is_file($dbPath), 'base manquante : ' . $dbPath);

    $siteConfig = require __DIR__ . '/../../config/sites/de.php';
    $tileScores = $siteConfig['tile_scores'];

    $connection = new Connection($dbPath);
    $lookup = new TermLookup($connection, $tileScores);

    // Mot admis simple, sans diacritique (verifie la base line de comportement).
    $haus = $lookup->find('haus');
    Assert::notNull($haus, 'HAUS devrait etre trouve');
    Assert::same('HAUS', $haus->normalized);
    Assert::same('haus', $haus->slug);
    Assert::true($haus->found);
    Assert::same(TermPage::STATUS_ADMITTED, $haus->status);
    Assert::same(5, $haus->score);
    Assert::same(4, $haus->length);
    Assert::true($haus->isOds8);
    Assert::true($haus->isOds9);
    Assert::same(4, count($haus->letters));
    Assert::same(5, array_sum(array_column($haus->letters, 'value')), 'la somme des tuiles doit egaler le score');
    Assert::same(['letter' => 'H', 'value' => 2], $haus->letters[0]);
    Assert::null($haus->pos, 'aucune source de nature grammaticale allemande cette passe');
    Assert::null($haus->posSecondary);
    Assert::null($haus->gender);

    // Coeur de la tache : Ä/Ö/Ü preservees, distinctes de A/O/U -- SCHÖN et SCHON sont
    // deux mots allemands differents, jamais confondus.
    $schoen = $lookup->find('schön');
    Assert::notNull($schoen, 'SCHÖN devrait etre trouve');
    Assert::same('SCHÖN', $schoen->normalized);
    Assert::same('schön', $schoen->slug, 'le slug doit conserver Ö en minuscule, pas le perdre ni le laisser en majuscule');
    Assert::true($schoen->found);
    Assert::same(TermPage::STATUS_ADMITTED, $schoen->status);
    Assert::same(16, $schoen->score, 'S1+C4+H2+Ö8+N1');
    Assert::same(5, $schoen->length, 'longueur en CARACTERES : 5, pas 6 (nombre d\'octets UTF-8)');
    Assert::same(5, count($schoen->letters));
    Assert::same(16, array_sum(array_column($schoen->letters, 'value')));
    Assert::same(['letter' => 'Ö', 'value' => 8], $schoen->letters[3]);

    $schon = $lookup->find('schon');
    Assert::notNull($schon, 'SCHON (sans trema) devrait aussi etre trouve, separement de SCHÖN');
    Assert::same('SCHON', $schon->normalized);
    Assert::true($schon->normalized !== $schoen->normalized, 'SCHON et SCHÖN doivent rester deux formes normalisees distinctes');

    // Eszett : accepte, converti en SS (regle officielle, pas de tuile ß dediee).
    $strasse = $lookup->find('Straße');
    Assert::notNull($strasse, 'Straße (Eszett) devrait etre trouve sous sa forme STRASSE');
    Assert::same('STRASSE', $strasse->normalized);
    Assert::true($strasse->found);
    Assert::same(TermPage::STATUS_ADMITTED, $strasse->status);

    // Terme absent, forme valide -> inconnu, pas une erreur (confirme absent de la base).
    $unknown = $lookup->find('ZZZQQQXXX');
    Assert::notNull($unknown, 'une forme valide, meme absente, doit produire une fiche');
    Assert::true(!$unknown->found);
    Assert::same(TermPage::STATUS_UNKNOWN, $unknown->status);
    Assert::same(9, $unknown->length);
    Assert::same(9, count($unknown->letters));
    Assert::true(!$unknown->isOds8 && !$unknown->isOds9);
    Assert::null($unknown->pos);
    Assert::null($unknown->posSecondary);
    Assert::null($unknown->gender);

    // Formes invalides -> aucune fiche, donc aucun quatrieme statut invente.
    Assert::null($lookup->find(''), 'entree vide');
    Assert::null($lookup->find('a'), 'une seule lettre, sous MIN_LENGTH');
    Assert::null($lookup->find('haus3'), 'chiffre dans l\'entree');
    Assert::null($lookup->find(str_repeat('a', Normalizer::MAX_LENGTH + 1)), 'au-dessus de MAX_LENGTH');

    // Voisinage alphabetique autour d'un mot present -- ordre BINARY strict sur toute la
    // base (590 850 formes) : Ä/Ö/Ü trient apres Z (voir Normalizer::signature()), donc le
    // voisinage de SCHÖN se fait parmi d'autres formes en SCH... plutot qu'avec SCHOEN
    // (qui n'existe pas comme forme normalisee separee) -- verifie a la main contre la
    // base reelle.
    Assert::same('SCHÖLTST', $schoen->previousWord);
    Assert::same('SCHÖNE', $schoen->nextWord);

    // Bornes de la base : AA est le premier mot (ordre BINARY, comme en francais -- pure
    // coincidence alphabetique, pas une consequence du changement de langue). ÜPPIGSTES
    // est le dernier : Ü, codepoint le plus eleve de l'alphabet allemand dans notre
    // convention de tri BINARY, trie apres tout mot commencant par A-Z ou meme Ä/Ö.
    $first = $lookup->find('AA');
    Assert::notNull($first);
    Assert::true($first->found);
    Assert::null($first->previousWord, 'AA est le premier mot de la base, pas de precedent');
    Assert::notNull($first->nextWord);

    $last = $lookup->find('ÜPPIGSTES');
    Assert::notNull($last);
    Assert::true($last->found);
    Assert::notNull($last->previousWord);
    Assert::null($last->nextWord, 'ÜPPIGSTES est le dernier mot de la base (ordre BINARY), pas de suivant');

    // Regression C1 (heritee du site francais) : entree UTF-8 invalide -> aucune fiche,
    // aucune exception qui remonterait au flux HTTP normal.
    Assert::null($lookup->find("\xFF\xFE"), 'octets UTF-8 invalides');

    // Regression C2 (heritee du site francais) : un saut de ligne final ne doit jamais
    // produire de fiche.
    Assert::null($lookup->find('haus' . "\n"), 'HAUS suivi d\'un saut de ligne');

    // Verification exhaustive : score/signature/reversed/length recalcules pour les
    // 590 850 lignes reelles, compares aux colonnes stockees par scripts/import_de.py.
    // Curseur PDO en streaming (pas de fetchAll) : ne charge pas la table en memoire.
    $pdo = $connection->pdo();
    $statement = $pdo->query('SELECT normalized, score, length, signature, reversed, is_admitted FROM terms');

    $rows = 0;
    $withUmlaut = 0;
    foreach ($statement as $row) {
        $rows++;
        $normalized = $row['normalized'];

        Assert::true(Normalizer::isValid($normalized), 'forme invalide en base : ' . $normalized);
        Assert::same((int) $row['score'], Normalizer::score($normalized, $tileScores), 'score de ' . $normalized);
        Assert::same((int) $row['length'], mb_strlen($normalized), 'length de ' . $normalized);
        Assert::same($row['signature'], Normalizer::signature($normalized), 'signature de ' . $normalized);
        Assert::same($row['reversed'], Normalizer::reverse($normalized), 'reversed de ' . $normalized);
        Assert::same(1, (int) $row['is_admitted'], 'toute ligne doit etre is_admitted = 1 (source unique, cette passe) : ' . $normalized);

        if (str_contains($normalized, 'Ä') || str_contains($normalized, 'Ö') || str_contains($normalized, 'Ü')) {
            $withUmlaut++;
        }
    }

    Assert::same(590850, $rows, 'nombre total de lignes verifiees, doit correspondre a docs/PHASE_STATUS.md');
    Assert::true($withUmlaut > 10000, 'sanity check : un nombre substantiel de mots doit contenir Ä/Ö/Ü (mesure reelle attendue bien au-dessus de 10 000)');
};
