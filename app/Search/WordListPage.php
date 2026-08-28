<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Resultat de /mots/... (Phase 3, docs/08), consomme par la couche de rendu (app/View/,
 * hors perimetre de cet agent -- structure prete a rendre, meme principe que TermPage pour
 * /wort/{wort} et RackPage pour /wortsuche/{lettres} -- D-DE-009 : URL localisees).
 *
 * Cette liste couvre TOUTE la base -- une fiche par forme presente en base, chaque entree
 * porte un statut explicite parmi les trois valeurs fermees de TermPage::STATUS_* (jamais
 * STATUS_UNKNOWN : une ligne presente dans `terms` designe un terme trouve par construction,
 * jamais un terme inconnu). ADAPTATION ALLEMANDE : dans cette premiere passe, toute ligne de
 * `terms` a is_admitted = 1 (source unique, voir schema.sql) -- le statut observe ici vaut
 * donc toujours TermPage::STATUS_ADMITTED, jamais STATUS_FRENCH_NOT_ADMITTED (constante
 * conservee pour le modele ferme, pas produite par les donnees actuelles).
 *
 * Deux regimes, determines par WordListSolver selon les contraintes presentes (voir sa
 * documentation pour la mesure qui justifie ce choix) :
 *
 * - exact = true  : $total est le compte EXACT de toutes les correspondances (pas seulement
 *   la page courante), obtenu par un COUNT() indexe. Pagination complete et fiable.
 * - exact = false : les contraintes demandent des predicats non indexes (contenant, avec,
 *   sans, motif partiel) -- $total est un compte trouve dans la fenetre examinee
 *   (WordListSolver::ROW_EXAMINATION_CEILING lignes au plus), jamais au-dela. $truncated
 *   signale que d'autres correspondances pourraient exister au-dela de cette fenetre : ne
 *   jamais presenter $total comme un compte exhaustif dans ce cas.
 */
final class WordListPage
{
    /**
     * @param list<array{normalized: string, slug: string, score: int, length: int, isOds8: bool, isOds9: bool, status: string}> $items
     *        page courante uniquement, taille au plus WordListSolver::PAGE_SIZE
     */
    public function __construct(
        public readonly string $canonicalPath,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly array $items,
        public readonly int $total,
        public readonly bool $exact,
        public readonly bool $truncated,
        public readonly bool $hasNextPage,
        public readonly bool $hasPreviousPage,
        public readonly int $queryCount,
    ) {
    }
}
