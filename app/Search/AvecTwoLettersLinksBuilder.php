<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\AvecTwoLettersLinks depuis list_counts (list_type 'length_with_pair'),
 * meme principe que App\Search\PositionLinksBuilder / App\Search\AvecSansLengthLinksBuilder --
 * une seule requete triviale, aucun calcul sur `terms` au runtime (voir
 * scripts/build_explore_hub_counts.php pour la mesure qui impose ce detour).
 *
 * list_key est toujours "{longueur}:{lettre1}:{lettre2}" avec lettre1 < lettre2 ALPHABETIQUEMENT
 * (une seule ligne par paire non ordonnee -- jamais les deux sens stockes separement). Depuis une
 * page "avec {X}" (palier 1), $letter peut se trouver des DEUX cotes de la paire stockee selon
 * l'ordre alphabetique avec son partenaire ($letter < partenaire OU partenaire < $letter) : cette
 * classe interroge donc les deux cas avec un OR sur deux motifs LIKE ({longueur}:{$letter}:% et
 * {longueur}:%:{$letter}), une seule requete, jamais deux executions separees. La table est
 * minuscule au pire (4 550 lignes au maximum, 14 longueurs x C(26,2) paires -- voir le docblock
 * de build_explore_hub_counts.php) : le second motif LIKE (joker en tete) ne beneficie pas de
 * l'index de cle primaire de la meme facon que le premier, mais le cout reste negligeable sur une
 * table de cette taille (mesure : reports/query-plans/avec-length-2-letters-full-sweep.md).
 *
 * L'URL cible est TOUJOURS construite via WordListFilters::fromPath()->canonicalUrl(), jamais
 * assemblee a la main : ksort() y trie deja les lettres "avec" par cle alphabetique (D-022), donc
 * peu importe l'ordre dans lequel $letter et le partenaire sont passes a fromPath() ici, l'URL
 * rendue est toujours la forme canonique (lettre1 < lettre2), identique a la cle list_counts.
 *
 * Deux filtres anti-doublon, appliques dans build() ci-dessous (analyse independante data-engine,
 * 2026-08-20, demandee en parallele du meme calcul cote seo-registry avant toute application
 * registre/sitemap -- meme discipline que D-037/D-038/D-039) : DUPLICATE_PARENT_KEYS (doublon avec
 * la page PARENTE palier 1, /mots/{N}-lettres/avec/{X}) et SIBLING_DUPLICATE_KEYS (doublon entre
 * pages SOEURS du palier 2, meme longueur).
 */
final class AvecTwoLettersLinksBuilder
{
    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, audit NO GO 2026-08-31 -- meme discipline que
     * D-DE-024/SuffixExtensionLinksBuilder::EXTERNAL_DUPLICATE_SUFFIXES, decision a journaliser
     * par la session principale dans docs/DECISIONS.md) : ces 4 triples etaient des doublons
     * structurels PROPRES A L'ORTHOGRAPHE FRANCAISE (ex. "aucun mot francais de 14/15 lettres ne
     * contient Q sans U") calcules sur storage/dictionary_fr.sqlite et copies tels quels lors du
     * portage du depot (git archive) -- jamais revalides pour l'allemand (qui n'a pas la meme
     * regle Q/U). Videe plutot que conservee.
     *
     * Cible App\Seo\Family::WORD_LIST_AVEC_TWO_LETTERS, qui A des lignes reelles dans
     * storage/seo_de.sqlite (5 202 au 2026-08-31) : verifie par balayage ad hoc equivalent a
     * scripts/check_combinatorial_duplicates.php sur les 8 familles combinatoires actuellement
     * peuplees du registre allemand -- 0 groupe de doublons touchant word_list_avec_two_letters
     * ou sa famille parente word_list_avec_single_letter (voir le rapport AFTER de cette tache).
     * Le calcul REEL d'un equivalent allemand (le cas echeant) reste a faire dans une passe
     * separee.
     *
     * @var list<string>
     */
    private const DUPLICATE_PARENT_KEYS = [];

    /**
     * Doublons de contenu entre pages SOEURS du palier 2 (deux paires DIFFERENTES a la MEME
     * longueur produisant exactement le meme ensemble de mots, ni l'une ni l'autre deja exclue par
     * DUPLICATE_PARENT_KEYS ci-dessus) -- meme classe de defaut que
     * App\Search\StartEndWithLinksBuilder::SIBLING_DUPLICATE_KEYS (D-038), recherchee ici de la
     * meme facon : regroupement par (longueur, count) parmi les 4 272 paires survivantes du filtre
     * parent (necessaire mais pas suffisant, deux ensembles distincts peuvent partager un compte),
     * PUIS verification par empreinte SQL GROUP_CONCAT (liste triee des mots concernes, comparaison
     * de chaines completes, aucun hash, aucune collision possible) sur les 286 groupes candidats
     * (1 064 paires) trouves par ce premier tri.
     *
     * Resultat : LISTE VOLONTAIREMENT VIDE -- 0 collision reelle trouvee sur les 1 064 candidates
     * verifiees par empreinte (contrairement au palier 3, voir
     * App\Search\AvecThreeLettersLinksBuilder::SIBLING_DUPLICATE_KEYS, ou 234 collisions reelles
     * existent). Mecanisme garde en place pour la coherence de forme avec les autres builders de
     * cette serie et en garde-fou si une reconstruction future de la base faisait apparaitre un cas
     * -- le test associe revalide ce chiffre a chaque execution, jamais suppose silencieusement.
     *
     * @var list<string>
     */
    private const SIBLING_DUPLICATE_KEYS = [];

    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, meme discipline que DUPLICATE_PARENT_KEYS
     * ci-dessus) : cette liste de 138 clés "{longueur}:{lettre1}:{lettre2}" etait calculee sur
     * storage/dictionary_fr.sqlite (D-041 cote francais) et copiee telle quelle lors du portage
     * du depot -- jamais revalidee pour l'allemand. Videe plutot que conservee.
     *
     * Cible App\Seo\Family::WORD_LIST_AVEC_TWO_LETTERS (5 202 lignes reelles au 2026-08-31) :
     * meme verification que DUPLICATE_PARENT_KEYS ci-dessus, 0 groupe de doublons trouve pour
     * cette famille par le balayage ad hoc (voir le rapport AFTER de cette tache).
     *
     * @var list<string>
     */
    private const EXTERNAL_DUPLICATE_KEYS = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function build(int $length, string $letter): AvecTwoLettersLinks
    {
        $statement = $this->connection->pdo()->prepare(
            "SELECT list_key, count FROM list_counts WHERE list_type = 'length_with_pair'"
            . ' AND (list_key LIKE ? OR list_key LIKE ?)'
        );
        $statement->execute([$length . ':' . $letter . ':%', $length . ':%:' . $letter]);

        $links = [];

        foreach ($statement as $row) {
            $key = (string) $row['list_key'];

            if (
                in_array($key, self::DUPLICATE_PARENT_KEYS, true)
                || in_array($key, self::SIBLING_DUPLICATE_KEYS, true)
                || in_array($key, self::EXTERNAL_DUPLICATE_KEYS, true)
            ) {
                continue;
            }

            $parts = explode(':', $key, 3);
            $partner = $parts[1] === $letter ? $parts[2] : $parts[1];
            $count = (int) $row['count'];

            // D-DE-010 : "-lettres" -> "-buchstaben" (localisation d'URL, voir docs/DECISIONS.md).
            // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- ces lettres
            // peuvent contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
            // D-DE-015 : "avec" -> WordListFilters::KEYWORD_WITH ("mit-buchstaben").
            $path = $length . '-buchstaben/' . WordListFilters::KEYWORD_WITH . '/' . mb_strtolower($letter, 'UTF-8')
                . '/' . mb_strtolower($partner, 'UTF-8');
            $url = WordListFilters::fromPath($path)?->canonicalUrl();

            if ($url !== null) {
                $links[] = ['letter' => $partner, 'url' => $url, 'count' => $count];
            }
        }

        usort($links, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);

        return new AvecTwoLettersLinks(links: $links, queryCount: 1);
    }
}
