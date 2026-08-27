<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Reimplementation stricte de scripts/lib/normalize.py (D-009, site allemand).
 *
 * scripts/lib/normalize.py est la source unique de la regle de normalisation ; cette
 * classe doit produire EXACTEMENT les memes sorties. Tout ecart est un bug de
 * correspondance, pas une variante -- verifie par :
 * - tests/Search/NormalizerTest.php contre tests/fixtures/normalize_samples.json,
 *   genere depuis normalize.py par scripts/build_normalize_fixture.py ;
 * - tests/Search/TermLookupTest.php, qui recalcule score/signature/reversed/length
 *   pour les lignes reelles de storage/dictionary_de.sqlite.
 *
 * Repris du site francais (D-009) puis adapte pour l'allemand -- reports/
 * de-site-feasibility-audit.md (cote depot francais), section 5 : contrairement au
 * francais, l'allemand a TROIS lettres supplementaires semantiquement distinctes
 * (Ä, Ö, Ü -- jamais des variantes accentuees de A/O/U a replier) et une lettre sans
 * decomposition NFD (ß, Eszett) qui doit etre acceptee plutot que rejetee. Ce fichier
 * est SPECIFIQUE a l'allemand (ce depot ne sert jamais que le site allemand) --
 * contrairement a la version francaise, il n'a pas besoin de rester generique.
 *
 * Piege PHP specifique corrige ici, absent du site francais : str_split()/strrev()
 * sont BYTE-orientes (pas codepoint-orientes), alors que Ä/Ö/Ü sont chacune codees sur
 * DEUX octets en UTF-8. Utiliser str_split()/strrev() directement sur un mot allemand
 * couperait un Ä en deux octets invalides isoles -- score()/signature()/reverse()
 * utilisent donc mb_str_split()/un renversement multioctet explicite, jamais les
 * fonctions natives PHP mono-octet. SQLite, lui, compte deja substr()/length() par
 * CARACTERE sur les colonnes TEXT (documentation SQLite) : aucun correctif necessaire
 * cote schema.sql pour les expressions substr(normalized, 1, 1) etc.
 *
 * Seul ecart assume et documente : score() recoit sa table de points en parametre
 * plutot qu'en constante de classe (meme convention que le site francais).
 */
final class Normalizer
{
    /**
     * Les ligatures ne sont PAS decomposees par la forme normale NFD. Repris tel quel
     * du site francais : la regle reste correcte pour l'allemand (emprunts etrangers
     * rares mais presents dans la source, voir data/raw/PROVENANCE.md).
     */
    private const LIGATURES = [
        "\u{0153}" => 'oe', // œ
        "\u{0152}" => 'OE', // Œ
        "\u{00e6}" => 'ae', // æ
        "\u{00c6}" => 'AE', // Æ
    ];

    /**
     * ß (Eszett, U+00DF) et ẞ (ß majuscule, U+1E9E) n'ont AUCUNE decomposition NFD : ce
     * n'est pas une lettre accentuee, c'est une lettre a part entiere. Sans ce mapping,
     * "Straße", "groß", "Fuß", "weiß", "heißen" -- parmi les mots les plus courants de
     * la langue -- echoueraient VALID_PATTERN (hors [A-ZÄÖÜ] apres majuscules).
     * Repli sur SS avant NFD : regle officielle confirmee (scrabble-info.de/
     * scrabbleturniere-und-das-eszett, voir reports/de-site-feasibility-audit.md
     * cote depot francais, §1.a) -- le ß n'a pas de tuile propre au Scrabble
     * allemand, un mot qui en contient un se pose avec deux tuiles S.
     */
    private const ESZETT = [
        "\u{00df}" => 'SS', // ß
        "\u{1e9e}" => 'SS', // ẞ
    ];

    /**
     * Ä/Ö/Ü (et leurs formes minuscules) sont protegees de la decomposition NFD par une
     * substitution temporaire vers la zone d'usage prive Unicode (jamais presente dans
     * une entree reelle), le temps de l'etape NFD + retrait des marques diacritiques
     * (categorie Mn) qui, sans cette protection, replierait Ä -> A, Ö -> O, Ü -> U
     * exactement comme un e francais perd son accent -- FAUX pour l'allemand, ou ces
     * trois lettres sont semantiquement distinctes de A/O/U (schon != schön,
     * Ofen != Öfen, Mahne != Mähne). Restaurees apres le retrait des Mn, avant la mise
     * en majuscules.
     */
    private const GERMAN_PROTECT = [
        "\u{00c4}" => "\u{e000}", "\u{00e4}" => "\u{e000}", // Ä / ä
        "\u{00d6}" => "\u{e001}", "\u{00f6}" => "\u{e001}", // Ö / ö
        "\u{00dc}" => "\u{e002}", "\u{00fc}" => "\u{e002}", // Ü / ü
    ];

    private const GERMAN_RESTORE = [
        "\u{e000}" => 'Ä',
        "\u{e001}" => 'Ö',
        "\u{e002}" => 'Ü',
    ];

    /**
     * Le plateau fait 15 cases : un mot de plus de 15 lettres ne peut jamais etre
     * pose. Plafond applique aux donnees, pas seulement a la saisie (D-010, heritee
     * du site francais). Mesure directement sur enz/german-wordlist
     * (data/raw/PROVENANCE.md) : 84 433 formes sur 685 789 (12,31 %) depassent 15
     * CARACTERES -- nettement plus lourd qu'en francais (2,2 %), attendu et
     * documente (composition allemande), pas une anomalie.
     */
    public const MIN_LENGTH = 2;
    public const MAX_LENGTH = 15;

    // /u (PCRE_UTF8) obligatoire ici, absent de la version francaise : sans lui, le
    // moteur PCRE compte {2,15} en OCTETS et [A-ZÄÖÜ] echoue a reconnaitre les
    // sequences UTF-8 multioctet de Ä/Ö/Ü comme un seul caractere de la classe.
    // \z (pas $) : $ accepte un \n final en PCRE, ce qui admettrait a tort
    // "POSER\n" comme terme valide (audit Phase 1 France, C2). \z ancre strictement
    // la fin de la chaine, sans exception pour un saut de ligne terminal.
    private const VALID_PATTERN = '/^[A-ZÄÖÜ]{' . self::MIN_LENGTH . ',' . self::MAX_LENGTH . '}\z/u';

    /**
     * Eszett, protection Ä/Ö/Ü, ligatures, puis NFD, puis retrait des diacritiques
     * (categorie Unicode Mn), puis majuscules, puis restauration Ä/Ö/Ü.
     *
     * Ne valide pas : renvoie la forme normalisee telle quelle, eventuellement
     * invalide. Utiliser isValid() pour trancher -- une entree qui n'est pas de
     * l'UTF-8 valide, ou que \Normalizer::normalize() refuse de decomposer, renvoie
     * une chaine vide, qui echoue toujours isValid() (audit Phase 1 France, C1). Ne
     * leve jamais d'exception : find() doit pouvoir traiter toute entree utilisateur
     * sans jamais laisser remonter une erreur au flux HTTP normal.
     */
    public static function normalize(string $form): string
    {
        if (!mb_check_encoding($form, 'UTF-8')) {
            return '';
        }

        $form = strtr($form, self::ESZETT);
        $form = strtr($form, self::GERMAN_PROTECT);
        $form = strtr($form, self::LIGATURES);
        $decomposed = \Normalizer::normalize($form, \Normalizer::FORM_D);

        if ($decomposed === false) {
            // \Normalizer::normalize() peut renvoyer false sur une sequence que
            // mb_check_encoding() n'aurait pas rejetee (ex. normalisation ICU
            // refusee) -- meme traitement : jamais un terme valide.
            return '';
        }

        $stripped = preg_replace('/\p{Mn}/u', '', $decomposed);
        $stripped ??= $decomposed;

        $upper = mb_strtoupper($stripped, 'UTF-8');

        return strtr($upper, self::GERMAN_RESTORE);
    }

    /** Un terme retenu ne contient que des A-ZÄÖÜ et fait de 2 a 15 lettres. */
    public static function isValid(string $normalized): bool
    {
        return preg_match(self::VALID_PATTERN, $normalized) === 1;
    }

    /**
     * Score brut, hors bonus de plateau. La somme des tuiles affichees doit toujours
     * etre egale a cette valeur.
     *
     * mb_str_split() (pas str_split()) : $normalized peut contenir Ä/Ö/Ü, codees sur
     * deux octets UTF-8 chacune -- str_split() les aurait coupees en deux octets
     * invalides isoles au lieu d'une lettre.
     *
     * Defense en profondeur (audit Phase 1 France, C2) : une lettre absente de
     * $tileScores ne doit jamais produire un total silencieusement faux (avertissement
     * PHP + addition avec null) -- leve une exception explicite, rattrapee en amont par
     * le gestionnaire global (app/bootstrap.php) plutot que de fuiter dans la reponse.
     * Ne devrait jamais se produire pour un $normalized valide (isValid() garantit des
     * lettres A-ZÄÖÜ, toutes presentes dans config/sites/de.php) : signale donc une
     * incoherence interne, pas une erreur de saisie utilisateur.
     *
     * @param array<string, int> $tileScores
     */
    public static function score(string $normalized, array $tileScores): int
    {
        $total = 0;

        foreach (mb_str_split($normalized) as $letter) {
            if (!array_key_exists($letter, $tileScores)) {
                throw new \InvalidArgumentException(sprintf('Lettre sans valeur de tuile : %s', $letter));
            }

            $total += $tileScores[$letter];
        }

        return $total;
    }

    /**
     * Lettres triees : deux anagrammes partagent la meme signature.
     *
     * mb_str_split() + sort(..., SORT_STRING) : la comparaison BINARY de SORT_STRING
     * trie par OCTET, mais Ä/Ö/Ü (U+00C4/D6/DC, encodees sur deux octets en UTF-8, le
     * premier octet 0xC3 restant identique pour les trois) restent regroupees et
     * placees apres Z (0x5A) -- propriete de l'UTF-8 (l'ordre par octet coincide avec
     * l'ordre par codepoint), meme ordre que Python sorted() sur des str cote build
     * (scripts/lib/normalize.py). Coherent entre build et runtime, mais PAS l'ordre
     * alphabetique allemand usuel (ou Ä trie proche de A) -- limite assumee et
     * documentee (schema.sql), les plages d'index restent correctes.
     */
    public static function signature(string $normalized): string
    {
        $letters = mb_str_split($normalized);
        sort($letters, SORT_STRING);

        return implode('', $letters);
    }

    /**
     * Terme inverse : permet de traiter un suffixe comme un prefixe indexe.
     *
     * mb_str_split() + array_reverse() (pas strrev()) : strrev() est byte-oriente et
     * couperait Ä/Ö/Ü (deux octets UTF-8 chacune) en deux octets isoles, produisant une
     * sequence UTF-8 invalide plutot qu'un mot inverse.
     */
    public static function reverse(string $normalized): string
    {
        return implode('', array_reverse(mb_str_split($normalized)));
    }
}
