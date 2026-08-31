<?php

declare(strict_types=1);

namespace App\Search;

use App\Database\Connection;

/**
 * Construit App\Search\LetterCombinedLinks depuis list_counts (D-024), meme principe que
 * App\Search\LengthLinksBuilder -- une seule requete triviale, aucun GROUP BY sur `terms` au
 * runtime (voir scripts/build_explore_hub_counts.php pour la mesure qui impose ce detour).
 *
 * list_key est toujours "{debut}:{fin}" pour 'start_end'. Le cote "fin" n'a pas de prefixe
 * exploitable par un index (`LIKE '%:Y'`, jokers en tete) -- accepte tel quel : list_counts ne
 * compte que 1 731 lignes au total, un SCAN complet reste trivial (aucun rapport avec un SCAN
 * sur `terms`, 838 180 lignes).
 *
 * Budget runtime : 1 requete SQLite par page (buildForStart() OU buildForEnd(), jamais les
 * deux sur la meme page).
 */
final class LetterCombinedLinksBuilder
{
    /**
     * NEUTRALISEE POUR L'ALLEMAND (correctif C2, audit NO GO 2026-08-31 -- meme discipline que
     * D-DE-024/SuffixExtensionLinksBuilder::EXTERNAL_DUPLICATE_SUFFIXES, decision a journaliser
     * par la session principale dans docs/DECISIONS.md) : cette liste de 33 paires
     * "{début}:{fin}" etait calculee sur storage/dictionary_fr.sqlite (838 180 termes francais,
     * D-041 cote francais, voir historique ci-dessous) et copiee telle quelle lors du portage du
     * depot (git archive) -- jamais revalidee pour l'allemand. Trouvee lue au runtime par l'audit
     * independant, avec un exemple direct verifie sur ce depot : 'W:L' filtrait a tort la page
     * allemande /woerter/beginnend-mit/w/endend-mit/l (290 mots reels, list_counts confirme) --
     * le lien depuis /woerter/beginnend-mit/w (page deja indexee, word_list_commencant) etait
     * absent alors que la page cible repond 200. Videe plutot que conservee : une liste de cles
     * francaises filtrerait des paires allemandes par pure coincidence de format, sans aucun
     * rapport avec un vrai doublon allemand -- pire que ne rien filtrer du tout. Le calcul REEL
     * d'un equivalent allemand (doublons croises commencant/terminant sur
     * storage/dictionary_de.sqlite) reste a faire dans une passe separee si besoin -- NOTE :
     * word_list_combined (la famille cible de ce builder) n'a encore AUCUNE ligne dans
     * storage/seo_de.sqlite a ce jour (2026-08-31, famille pas encore deployee au registre) ; le
     * balayage ad hoc de ce correctif (voir le rapport AFTER) a bien trouve 67 groupes de vrais
     * doublons croises allemands, mais tous entre word_list_commencant/word_list_terminant
     * multi-lettres (famille geree par Prefix/SuffixExtensionLinksBuilder, pas par ce builder-ci)
     * -- l'absence de doublon confirme ici est donc partielle (aucune ligne word_list_combined a
     * comparer pour l'instant), a reverifier des que cette famille sera peuplee au registre. Ce
     * champ ne bloque QUE l'affichage du lien "explorer plus loin" sur une page deja indexee,
     * jamais une decision d'indexation (calculee independamment par storage/seo_de.sqlite).
     *
     * Historique francais (pour memoire, ne s'applique plus a cette base) : doublons de contenu
     * CROISÉS avec une famille EXTÉRIEURE à la variante commençant+terminant SANS longueur
     * (D-041) -- une page "commençant/{X}/terminant/{Y}" perdait face à un adversaire à 1 seul
     * composant (commençant/terminant multi-lettres, ex. F:Q perdait face à /mots/terminant/faq).
     *
     * @var list<string>
     */
    public const EXTERNAL_DUPLICATE_KEYS = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /** Pour une page /mots/commencant/{X} : liens vers /mots/commencant/{X}/terminant/{Y}. */
    public function buildForStart(string $startLetter): LetterCombinedLinks
    {
        return $this->build($startLetter . ':%', fromStart: true);
    }

    /** Pour une page /mots/terminant/{Y} : liens vers /mots/commencant/{X}/terminant/{Y}. */
    public function buildForEnd(string $endLetter): LetterCombinedLinks
    {
        return $this->build('%:' . $endLetter, fromStart: false);
    }

    private function build(string $likePattern, bool $fromStart): LetterCombinedLinks
    {
        $statement = $this->connection->pdo()->prepare(
            "SELECT list_key, count FROM list_counts WHERE list_type = 'start_end' AND list_key LIKE ?"
        );
        $statement->execute([$likePattern]);

        $links = [];

        foreach ($statement as $row) {
            $key = (string) $row['list_key'];

            if (in_array($key, self::EXTERNAL_DUPLICATE_KEYS, true)) {
                continue;
            }

            [$start, $end] = explode(':', $key, 2);
            $other = $fromStart ? $end : $start;

            // D-DE-010 : "commencant"/"terminant" -> "beginnend-mit"/"endend-mit" (localisation
            // d'URL, voir docs/DECISIONS.md).
            // D-DE-011 : strtolower() (ASCII) -> mb_strtolower(..., 'UTF-8') -- $start/$end
            // peuvent contenir Ä/Ö/Ü (list_counts), signale par l'audit independant.
            $url = WordListFilters::fromPath('beginnend-mit/' . mb_strtolower($start, 'UTF-8') . '/endend-mit/' . mb_strtolower($end, 'UTF-8'))?->canonicalUrl();

            if ($url !== null) {
                $links[] = ['letter' => $other, 'url' => $url, 'count' => (int) $row['count']];
            }
        }

        usort($links, static fn (array $a, array $b): int => $a['letter'] <=> $b['letter']);

        return new LetterCombinedLinks(links: $links, queryCount: 1);
    }
}
