<?php

declare(strict_types=1);

namespace App\Seo;

/**
 * Liste fermee des familles de reporting/gouvernance du registre SEO allemand
 * (storage/seo_de.sqlite). Meme patron que les depots francais et espagnol cousins
 * (app/Seo/Family.php, FR/ES), ADAPTE plutot que copie tel quel : ce fichier repart d'une
 * base neuve, sans l'historique de paliers "avec"/"position"/"combined" du site francais
 * (D-023 a D-041, FR/docs/DECISIONS.md) -- aucun de ces paliers n'a ete mesure ni ouvert ici.
 *
 * Une famille correspond a un TYPE de route, pas a une route individuelle -- elle sert a :
 * - produire les metriques quantifiees exigees par lot (URL par famille) ;
 * - appliquer les regles dures par famille (ex. NEVER_SITEMAP ci-dessous), a la fois dans
 *   scripts/apply_seo_batch.php (refus a l'ecriture) et dans les rapports de rollout.
 *
 * PAS D'EQUIVALENT WORD_FRENCH_NOT_ADMITTED / WORD_SPANISH_NOT_ADMITTED ICI, contrairement
 * aux depots francais et espagnol cousins : le modele de donnees allemand n'a, dans cette
 * premiere passe, que DEUX statuts peuplés (admis / inconnu -- CLAUDE.md, "Modele A Statuts").
 * Il n'existe aucune colonne is_german/is_french-equivalent dans storage/dictionary_de.sqlite
 * (data/raw/PROVENANCE.md : aucune source de dictionnaire general allemand independante
 * retenue faute de licence claire) -- rien a classer sous une famille "allemand non admis" tant
 * que cette donnee n'existe pas. Ajouter une constante de reservation pour une famille SANS
 * aucune donnee sous-jacente serait une spéculation d'architecture, pas une décision fondée --
 * a ajouter le jour ou une source réelle existe, jamais avant.
 *
 * ETAT REEL DE CE DEPOT (premier palier, voir docs/DECISIONS.md D-DE-013) : seules HOME,
 * WORD_ADMITTED et WORD_LIST_LENGTH ont des lignes dans storage/seo_de.sqlite a ce stade, et
 * WORD_LIST_LENGTH n'est peuplee QUE pour les 2 longueurs (7 et 9) reellement liees depuis
 * app/View/home.php (verifie en direct, pas suppose) -- les 12 autres longueurs restent
 * noindex,follow, la grille /woerter (App\Search\ExploreHub) etant inerte tant que
 * list_counts reste vide (0 ligne, voir schema.sql du dictionnaire et docs/DECISIONS.md
 * D-DE-010). Toutes les autres constantes existent pour que la FORME du registre (schema,
 * classes, outils de build) soit complete et prete a recevoir de futurs paliers sans migration
 * de schema -- mais aucune d'entre elles n'est peuplee. Une famille non peuplee n'indexe rien
 * PAR CONSTRUCTION : une route absente de `registry` reste noindex,follow (meme contrat que
 * D-005 du depot francais, voir App\Seo\Registry::resolve()).
 *
 * Correspondance avec les prefixes de fragments de sitemap reellement generes a ce stade
 * (voir FAMILY_FRAGMENT_PREFIXES dans scripts/build_sitemaps.php) : core-* (home + hub
 * /woerter), words-* (mots admis), letters-* (2 listes de longueur). Les autres prefixes
 * documentes sur les depots cousins (starts-, ends-, contains-, avec-*, position-,
 * combined-*...) restent des reservations de nommage pour de futurs paliers, jamais generes
 * par ce depot a ce jour.
 */
final class Family
{
    public const HOME = 'home';
    public const WORD_ADMITTED = 'word_admitted';

    public const WORD_LIST_LENGTH = 'word_list_length';

    /**
     * Constantes reservees pour de futurs paliers combinatoires (beginnend-mit, endend-mit,
     * contenant, avec, sans, motif, position, combinaisons commencant+terminant) -- AUCUNE
     * n'est peuplee ni couverte par une regle de forme dans scripts/apply_seo_batch.php a ce
     * stade, meme si beginnend-mit/a et endend-mit/s sont DEJA demontrablement liees depuis
     * app/View/home.php (phrase d'aide) : un lien entrant reel est une condition NECESSAIRE,
     * jamais SUFFISANTE, a l'indexation (garde-fou de role) -- ces deux pages restent un
     * candidat explicite pour un prochain palier, pas ouvertes par ce lot (voir
     * docs/DECISIONS.md D-DE-013 pour la discipline de largeur de palier retenue, alignee sur
     * le depot espagnol cousin, ES-009).
     *
     * Presentes ici uniquement pour que Family::ALL/NEVER_SITEMAP restent la liste fermee
     * complete attendue par le reste de app/Seo/ (et pour que app/Search/*LinksBuilder.php,
     * deja cable dans public/index.php pour le rendu de /woerter/..., ait un nom de famille
     * disponible le jour ou un palier reel est mesure et propose). Toute ouverture future
     * exige, comme sur les depots francais/espagnol : balayage complet des combinaisons
     * reelles, mesure TTFB, maillage interne construit ET verifie AVANT application, et sa
     * propre entree docs/DECISIONS.md -- jamais une simple reutilisation de cette liste.
     */
    public const WORD_LIST_COMMENCANT = 'word_list_commencant';
    public const WORD_LIST_TERMINANT = 'word_list_terminant';
    public const WORD_LIST_CONTENANT = 'word_list_contenant';
    public const WORD_LIST_AVEC = 'word_list_avec';
    public const WORD_LIST_SANS = 'word_list_sans';
    public const WORD_LIST_MOTIF = 'word_list_motif';
    public const WORD_LIST_POSITION = 'word_list_position';
    public const WORD_LIST_COMBINED = 'word_list_combined';

    /** Route /wortsuche/{buchstaben} -- tirage de chevalet, combinatoire, jamais indexable. */
    public const RACK = 'rack';

    /** @var list<string> */
    public const ALL = [
        self::HOME,
        self::WORD_ADMITTED,
        self::WORD_LIST_LENGTH,
        self::WORD_LIST_COMMENCANT,
        self::WORD_LIST_TERMINANT,
        self::WORD_LIST_CONTENANT,
        self::WORD_LIST_AVEC,
        self::WORD_LIST_SANS,
        self::WORD_LIST_MOTIF,
        self::WORD_LIST_POSITION,
        self::WORD_LIST_COMBINED,
        self::RACK,
    ];

    /**
     * Familles dont l'espace d'URL est combinatoire, potentiellement non borne en pratique
     * (contenant/avec/sans/motif : toute sous-chaine, tout multiensemble de lettres, toute
     * combinaison de cases connues). Contrainte dure du role seo-registry : "Refuse infinite
     * letter/sequence combinations as indexable by default." Ces familles ne doivent JAMAIS
     * recevoir de sitemap_fragment, quel que soit le lot -- applique en dur par
     * scripts/apply_seo_batch.php, pas seulement documente ici.
     *
     * WORD_LIST_COMMENCANT/WORD_LIST_TERMINANT/WORD_LIST_POSITION/WORD_LIST_COMBINED ne sont
     * PAS dans cette liste (espace borne par construction -- 26 lettres, positions bornees par
     * longueur, etc., meme raisonnement que sur le depot francais une fois mesure) mais ne
     * sont pas non plus peuplees a ce stade : une famille peut etre "autorisee en principe"
     * sans avoir encore de lignes reelles. RACK reste ici (tirage jusqu'a 15 tuiles, jokers
     * compris, espace quasi illimite, comme /jouer/{lettres} sur le depot francais).
     *
     * @var list<string>
     */
    public const NEVER_SITEMAP = [
        self::WORD_LIST_CONTENANT,
        self::WORD_LIST_AVEC,
        self::WORD_LIST_SANS,
        self::WORD_LIST_MOTIF,
        self::RACK,
    ];

    public static function isValid(string $family): bool
    {
        return in_array($family, self::ALL, true);
    }

    public static function forbidsSitemap(string $family): bool
    {
        return in_array($family, self::NEVER_SITEMAP, true);
    }
}
