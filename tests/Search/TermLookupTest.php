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
    // base (827 765 formes, D-DE-029 : enz/hippler + kaikki_de) : Ä/Ö/Ü trient apres Z (voir
    // Normalizer::signature()), donc le voisinage de SCHÖN se fait parmi d'autres formes en
    // SCH... plutot qu'avec SCHOEN (qui n'existe pas comme forme normalisee separee) --
    // verifie a la main contre la base reelle. Valeurs mises a jour par D-DE-029 (la base a
    // grossi de 590 856 a 827 765 termes, de nouveaux voisins alphabetiques sont apparus).
    Assert::same('SCHÖMERICHS', $schoen->previousWord);
    Assert::same('SCHÖNAICHS', $schoen->nextWord);

    // Bornes de la base : AA est le premier mot (ordre BINARY, comme en francais -- pure
    // coincidence alphabetique, pas une consequence du changement de langue, ni de D-DE-029).
    // ÜTTFELDS est desormais le dernier mot (D-DE-029 : ancien dernier ÜPPIGSTES, verifie
    // toujours present mais plus le dernier une fois kaikki_de fusionne) : Ü, codepoint le
    // plus eleve de l'alphabet allemand dans notre convention de tri BINARY, trie apres tout
    // mot commencant par A-Z ou meme Ä/Ö.
    $first = $lookup->find('AA');
    Assert::notNull($first);
    Assert::true($first->found);
    Assert::null($first->previousWord, 'AA est le premier mot de la base, pas de precedent');
    Assert::notNull($first->nextWord);

    // ÜPPIGSTES reste dans la base (verifie) mais n'est plus le dernier mot depuis D-DE-029 --
    // sanity check qu'il a bien un successeur desormais, pas de reprise du role "dernier mot".
    $uppigstes = $lookup->find('ÜPPIGSTES');
    Assert::notNull($uppigstes);
    Assert::true($uppigstes->found);
    Assert::notNull($uppigstes->previousWord);
    Assert::notNull($uppigstes->nextWord, 'ÜPPIGSTES n\'est plus le dernier mot de la base depuis D-DE-029 (kaikki_de)');

    $last = $lookup->find('ÜTTFELDS');
    Assert::notNull($last);
    Assert::true($last->found);
    Assert::notNull($last->previousWord);
    Assert::null($last->nextWord, 'ÜTTFELDS est le dernier mot de la base (ordre BINARY) depuis D-DE-029, pas de suivant');

    // Regression C1 (heritee du site francais) : entree UTF-8 invalide -> aucune fiche,
    // aucune exception qui remonterait au flux HTTP normal.
    Assert::null($lookup->find("\xFF\xFE"), 'octets UTF-8 invalides');

    // Regression C2 (heritee du site francais) : un saut de ligne final ne doit jamais
    // produire de fiche.
    Assert::null($lookup->find('haus' . "\n"), 'HAUS suivi d\'un saut de ligne');

    // Verification exhaustive : score/signature/reversed/length recalcules pour les
    // 827 765 lignes reelles (590 856 enz/hippler, D-DE-006 + 236 909 kaikki_de non admis,
    // D-DE-029), compares aux colonnes stockees par scripts/import_de.py. Curseur PDO en
    // streaming (pas de fetchAll) : ne charge pas la table en memoire.
    $pdo = $connection->pdo();
    $statement = $pdo->query('SELECT normalized, score, length, signature, reversed, is_enz, is_hippler, is_german, is_admitted FROM terms');

    $rows = 0;
    $withUmlaut = 0;
    $enzOnly = 0;
    $hipplerOnly = 0;
    $both = 0;
    $kaikkiOnly = 0;
    foreach ($statement as $row) {
        $rows++;
        $normalized = $row['normalized'];
        $isEnz = (int) $row['is_enz'];
        $isHippler = (int) $row['is_hippler'];
        $isGerman = (int) $row['is_german'];

        Assert::true(Normalizer::isValid($normalized), 'forme invalide en base : ' . $normalized);
        Assert::same((int) $row['score'], Normalizer::score($normalized, $tileScores), 'score de ' . $normalized);
        Assert::same((int) $row['length'], mb_strlen($normalized), 'length de ' . $normalized);
        Assert::same($row['signature'], Normalizer::signature($normalized), 'signature de ' . $normalized);
        Assert::same($row['reversed'], Normalizer::reverse($normalized), 'reversed de ' . $normalized);

        // D-DE-006 : provenance par mot -- toute ligne vient d'AU MOINS une des TROIS
        // sources depuis D-DE-029 (jamais aucune des trois, cette ligne n'existerait pas), et
        // is_admitted reste un OR REEL de is_enz/is_hippler UNIQUEMENT -- jamais is_german
        // (D-DE-029, troisieme statut du modele CLAUDE.md : reel mais pas necessairement
        // admis).
        Assert::true($isEnz === 1 || $isHippler === 1 || $isGerman === 1, $normalized . ' doit venir d\'au moins une source');
        Assert::same($isEnz || $isHippler ? 1 : 0, (int) $row['is_admitted'], 'is_admitted doit toujours egaler is_enz OR is_hippler (jamais is_german) : ' . $normalized);

        if ($isEnz && $isHippler) {
            $both++;
        } elseif ($isEnz) {
            $enzOnly++;
        } elseif ($isHippler) {
            $hipplerOnly++;
        } else {
            $kaikkiOnly++;
        }

        if (str_contains($normalized, 'Ä') || str_contains($normalized, 'Ö') || str_contains($normalized, 'Ü')) {
            $withUmlaut++;
        }
    }

    Assert::same(827765, $rows, 'nombre total de lignes verifiees, doit correspondre a docs/PHASE_STATUS.md (D-DE-029 : 590 856 enz/hippler + 236 909 kaikki_de non admis)');
    Assert::true($withUmlaut > 10000, 'sanity check : un nombre substantiel de mots doit contenir Ä/Ö/Ü (mesure reelle attendue bien au-dessus de 10 000)');

    // Comptes de provenance (D-DE-006), mesures au build et re-verifies ici independamment
    // sur la base reelle -- pas seulement fait confiance au rapport import-summary.json.
    // enz/hippler/both INCHANGES par D-DE-029 (kaikki_de n'affecte que kaikkiOnly, un
    // quatrieme compartiment, jamais les trois premiers).
    Assert::same(297690, $enzOnly, 'termes presents uniquement dans enz');
    Assert::same(6, $hipplerOnly, 'termes presents uniquement dans hippler (confirme empiriquement : la quasi-totalite des ~6 400 formes brutes hippler-seules sont des doublons de graphie ß/ss, pas une perte de couverture reelle)');
    Assert::same(293160, $both, 'termes presents dans les deux sources');
    Assert::same(236909, $kaikkiOnly, 'termes reels allemands (kaikki_de) absents d\'enz/hippler, D-DE-029 -- non admis par construction');

    // Coeur de la decouverte D-DE-006 : ABSCHIEDSGRUSS existe cote hippler (graphie suisse
    // "ss") ET cote enz (graphie standard "ß") -- les deux formes brutes distinctes se
    // rejoignent en UNE seule forme normalisee (Eszett -> SS), donc en UNE seule ligne
    // marquee presente dans les DEUX sources, pas une collision a departager.
    $abschiedsgruss = $lookup->find('Abschiedsgruss');
    Assert::notNull($abschiedsgruss, 'ABSCHIEDSGRUSS devrait etre trouve');
    Assert::true($abschiedsgruss->isOds8, 'ABSCHIEDSGRUSS doit rester admis (au moins une source), meme si isOds8 reflete ici is_admitted (voir TermLookup, ADAPTATION ALLEMANDE)');

    // Un des 6 termes reellement uniques a hippler : trouvable normalement, admis (une
    // source suffit), meme si absent d'enz.
    $eth = $lookup->find('ETH');
    Assert::notNull($eth, 'ETH (unique a hippler) devrait quand meme etre trouve');
    Assert::true($eth->found);
    Assert::same(TermPage::STATUS_ADMITTED, $eth->status, 'ETH doit rester admis : une seule source suffit (is_admitted = is_enz OR is_hippler)');
};
