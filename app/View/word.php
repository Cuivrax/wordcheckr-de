<?php

declare(strict_types=1);

/**
 * Vue fiche mot, appelee par public/index.php avec $page (App\Search\TermPage),
 * $relations (App\Search\TermRelations|null, Phase 4) et $conjugation
 * (App\Search\Conjugation, D-018). $relations est null des que le mot n'est pas
 * effectivement admis (francais non admis ou inconnu) -- aucune section relations n'est
 * alors rendue (aucune section vide, docs/01_MASTER_BRIEF.md).
 *
 * Le statut est ferme a trois valeurs (CLAUDE.md) : admitted / french_not_admitted /
 * unknown. Les deux premieres phrases de reponse directe reprennent le texte exact
 * de docs/01_MASTER_BRIEF.md (et docs/08_PROMPTS_PHASES.md pour le remplacement de
 * QUEULEULEU par GHOSTER) ; la phrase "unknown" est un texte fonctionnel minimal,
 * a confirmer par l'agent microcopy.
 *
 * Relations (Phase 4) : dix categories, seules les non vides sont rendues (voir
 * app/Search/TermRelations.php pour le contrat exact). Le surlignage <mark> de la partie
 * conservee/modifiee est calcule ici par simple comparaison de chaines entre le mot pivot
 * et chaque candidat -- sauf changeOneLetter, ou le backend fournit deja position/newLetter,
 * reutilises tels quels. Les liens "Voir les N mots ->" (rallonges, mot contenu) reutilisent
 * App\Search\WordListFilters::canonicalUrl(), jamais une URL construite a la main.
 *
 * Nature grammaticale / genre / conjugaison (D-018) : $page->pos, $page->posSecondary et
 * $page->gender sont nullables (absence de donnee Kartmaan pour ~12,3% des termes, pas une
 * erreur) -- aucune ligne rendue si $page->pos est null, meme convention "pas de section
 * vide" que le reste de la fiche. $conjugation n'est jamais null pour un mot TROUVE (admis
 * ou francais non admis), mais ses deux listes ($asLemma, $asForm) le sont le plus souvent
 * simultanement (mot ni verbe ni forme conjuguee) : la section conjugaison entiere est alors
 * absente.
 *
 * Definitions (D-0XX, pilote 100 mots -- revise D-004, voir reports/definitions-nature-
 * feasibility-audit.md) : $senses n'est jamais null pour un mot TROUVE, mais $senses->senses
 * est vide pour la tres grande majorite des termes tant que le lot reste un pilote partiel --
 * aucune section rendue dans ce cas, meme convention que le reste de la fiche. Des qu'au moins
 * un sens existe, la ligne $posLine (une phrase compacte) devient redondante avec les cartes
 * de sens (qui portent deja pos/genre chacune) -- $posLine n'est alors PAS rendue, evite de
 * dire deux fois la meme chose de deux facons differentes sur la meme page.
 */

require __DIR__ . '/helpers.php';

use App\Search\Conjugation;
use App\Search\TermPage;
use App\Search\WordListFilters;
use App\Search\WordSenses;

/** @var TermPage $page */
/** @var \App\Seo\SeoMeta $seo */
/** @var \App\Search\TermRelations|null $relations */
/** @var Conjugation $conjugation */
/** @var WordSenses $senses */

// ADAPTATION ALLEMANDE (D-DE-009, localisation d'URL + patron de reponse directe) :
// texte traduit d'apres reports/de-serp-terminology-research.md section 2.1 -- "gültig"
// (pas "zulässig", vocabulaire officiel SDeV volontairement hors perimetre de cette tache
// de routage, voir docs/DECISIONS.md D-DE-009) est le terme grand public dominant chez les
// concurrents pour "valide au Scrabble". Patron H1/reponse directe de type question-reponse
// vu chez UN SEUL concurrent (scrabble123.de, confiance plus faible que cote FR/ES ou deux
// sites independants confirmaient la meme formule) -- retenu quand meme a la demande
// explicite du porteur de projet, coherent avec le patron "Reponse Directe" deja en place
// cote FR et ES.
$statusMeta = match ($page->status) {
    TermPage::STATUS_ADMITTED => [
        'modifier' => 'admitted',
        'badge' => 'Ja, Gültiges Wort',
        'subtitle' => 'Sie können es spielen.',
        'direct' => sprintf(
            'Das Wort %s ist ein gültiges Scrabble-Wort. Der Grundwert beträgt %d Punkte, ohne Feldbonus.',
            $page->normalized,
            $page->score,
        ),
        // Titre raccourci (D-DE-013, point 4 ; correctif cette passe) : le gabarit precedent
        // ("Ja, X Ist Ein Gültiges Scrabble-Wort (N Punkte) | WORD CHECKR") depassait 60
        // caracteres pour 590 856/590 856 mots admis (100%, mesure exhaustive), jusqu'a 76
        // pour HYPOMIXOLYDISCH (15 lettres, 53 points) -- bloquait toute ouverture de la
        // famille word_admitted a l'indexation. "Ist Ein ... Scrabble-Wort" -> "Ist Gültig"
        // (sens identique, "gultig" deja le terme retenu par D-DE-009) ramene le pire cas
        // mesure en base (15 lettres, score max reel 53, jamais 3 chiffres) a 56 caracteres
        // AVEC le suffixe " | WORD CHECKR" -- suffixe conserve (pas de regime special pour
        // cette famille, coherence avec les autres vues qui le gardent toutes).
        'title' => sprintf('Ja, %s Ist Gültig (%d Punkte)', $page->normalized, $page->score),
    ],
    TermPage::STATUS_FRENCH_NOT_ADMITTED => [
        'modifier' => 'not-admitted',
        'badge' => 'Nicht Gültig',
        'subtitle' => 'Sie können es nicht spielen.',
        'direct' => sprintf(
            'Das Wort %s ist kein gültiges Scrabble-Wort in dieser Datenbank.',
            $page->normalized,
        ),
        // Meme raccourci que le statut admis ci-dessus, meme raison (D-DE-013 point 4) --
        // "Ist Kein Gültiges Scrabble-Wort" -> "Ist Nicht Gültig". Statut structurellement
        // ferme (CLAUDE.md) mais jamais produit par les donnees allemandes actuelles
        // (TermLookup::find(), aucune source "reel mais non admis") -- corrige quand meme
        // pour coherence, pas une branche morte a laisser incoherente.
        'title' => sprintf('Nein, %s Ist Nicht Gültig', $page->normalized),
    ],
    default => [
        'modifier' => 'unknown',
        'badge' => 'Unbekannter Begriff',
        'subtitle' => 'Nicht in der Datenbank.',
        'direct' => sprintf(
            'Das Wort %s wurde nicht in der Datenbank gefunden. Es kann nicht als gültiges Scrabble-Wort bestätigt werden.',
            $page->normalized,
        ),
        // Verifie (D-DE-013 point 4, correctif cette passe) : deja sous le budget de ~60
        // caracteres avec le suffixe " | WORD CHECKR" au pire cas mesure (mot a 15 lettres,
        // 50 caracteres) -- non touche, aucun changement necessaire.
        'title' => sprintf('%s: Unbekannter Begriff', $page->normalized),
    ],
};

$letterList = implode(' + ', array_column($page->letters, 'letter'));
$tilesAriaLabel = sprintf('%s, insgesamt %d Punkte', $letterList, $page->score);

// Relations (Phase 4) : construites uniquement si $relations !== null (mot effectivement
// admis, voir doc de tete). Tout le calcul est de la simple comparaison de chaines et de la
// construction d'URL deja etablie ailleurs (WordListFilters) -- aucune requete, aucune
// logique metier nouvelle.
$relationCategories = [];
$relatedLabel = null;

if ($relations !== null) {
    $pivot = $page->normalized;

    // ADAPTATION ALLEMANDE (cette passe, voir rapport de tache pour la traçabilité complète) :
    // pluriel allemand "Wort"/"Wörter" (pas un simple suffixe -s comme en francais), calcule
    // une fois par appel plutot que duplique -- meme logique que $moreLinkLabel ci-dessous.
    $countLabel = static function (int $count, bool $truncated = false): string {
        $word = $count === 1 ? 'Wort' : 'Wörter';

        if ($truncated) {
            return sprintf('Mindestens %d %s', $count, $word);
        }

        return sprintf('%d %s', $count, $word);
    };

    $moreLinkLabel = static function (int $total, bool $truncated): string {
        $word = $total === 1 ? 'Wort' : 'Wörter';

        return $truncated ? sprintf('Mindestens %d %s ansehen →', $total, $word) : sprintf('Alle %d %s ansehen →', $total, $word);
    };

    $extensionUrl = static function (string $keyword, string $word): ?string {
        // mb_strtolower (pas strtolower ASCII) : correctif signale (audit independant,
        // docs/DECISIONS.md D-DE-011) -- un mot contenant Ä/Ö/Ü restait sinon en MAJUSCULE
        // dans l'URL generee ici, provoquant une redirection 301 supplementaire.
        return WordListFilters::fromPath($keyword . '/' . mb_strtolower($word, 'UTF-8'))?->canonicalUrl();
    };

    // Surlignage <mark> : position/newLetter deja fournis par le backend pour
    // changeOneLetter, reutilises tels quels. Pour les autres categories surlignees
    // (insertOneLetter, rightExtensions, leftExtensions, containingWords), simple
    // comparaison de chaines entre le mot pivot et le candidat -- pas de logique metier.
    // anagrams, removeOneLetter, substrings, anagramsPlusOne, anagramsMinusOne ne sont
    // jamais surlignes (meme convention que prototype/mot-poser.html).
    $highlighted = static function (array $item, string $key, string $pivot): string {
        $word = $item['normalized'];

        switch ($key) {
            case 'changeOneLetter':
                // mb_substr (pas substr/$word[$pos] octets) : correctif signale (audit
                // independant, docs/DECISIONS.md D-DE-011). $item['position'] vient de
                // App\Search\RelationsFinder::changeOneLetterCandidates() comme un index de
                // CARACTERE (boucle sur mb_str_split(), voir son docblock) -- le consommer ici
                // via substr()/[] octets desynchronisait la coupure des que le mot contenait
                // Ä/Ö/Ü (2 octets UTF-8) avant la position changee : la sequence UTF-8 coupee
                // au milieu devenait une CHAINE VIDE une fois passee a e() (htmlspecialchars()
                // sans ENT_SUBSTITUTE avant correctif, voir helpers.php), effacant tout le mot
                // affiche plutot que de mal le surligner (ex. mesure : /wort/backer affichait
                // "B<mark></mark>" au lieu de "BÄCKER").
                $pos = $item['position'] - 1;

                return e(mb_substr($word, 0, $pos)) . '<mark>' . e(mb_substr($word, $pos, 1)) . '</mark>' . e(mb_substr($word, $pos + 1));

            case 'insertOneLetter':
                // mb_strlen/mb_substr (pas strlen/[] octets) : meme correctif que
                // changeOneLetter ci-dessus -- la boucle de comparaison octet-par-octet
                // trouvait deja la bonne frontiere de caractere (prefixe commun identique
                // entre pivot et candidat), mais l'extraction de la lettre inseree via
                // $word[$i] isolait un seul octet d'un caractere Ä/Ö/Ü qui en occupe deux.
                $pivotLength = mb_strlen($pivot);
                $i = 0;

                while ($i < $pivotLength && mb_substr($pivot, $i, 1) === mb_substr($word, $i, 1)) {
                    $i++;
                }

                return e(mb_substr($word, 0, $i)) . '<mark>' . e(mb_substr($word, $i, 1)) . '</mark>' . e(mb_substr($word, $i + 1));

            case 'rightExtensions':
                // strlen/substr (pas mb_) restent SURS ici, contrairement aux deux cas
                // ci-dessus : $word commence TOUJOURS par la chaine COMPLETE $pivot (extension
                // a droite), donc la frontiere en octet strlen($pivot) tombe toujours en fin
                // d'un caractere complet, jamais au milieu d'un Ä/Ö/Ü (meme raisonnement que
                // leftExtensions/containingWords ci-dessous -- verifie, pas suppose).
                $pivotLength = strlen($pivot);

                return '<mark>' . e(substr($word, 0, $pivotLength)) . '</mark>' . e(substr($word, $pivotLength));

            case 'leftExtensions':
                // strlen/substr surs ici : $word se termine TOUJOURS par la chaine COMPLETE
                // $pivot (extension a gauche) -- strlen($word) - strlen($pivot) est donc
                // toujours la frontiere en octet de DEBUT du pivot complet, jamais un milieu de
                // caractere.
                $prefixLength = strlen($word) - strlen($pivot);

                return e(substr($word, 0, $prefixLength)) . '<mark>' . e(substr($word, $prefixLength)) . '</mark>';

            case 'containingWords':
                // strpos/substr surs ici : $pivot apparait comme sous-chaine COMPLETE et
                // valide dans $word (contenant) -- une recherche octet ne peut matcher qu'a une
                // frontiere de caractere valide pour une sous-chaine UTF-8 valide (un octet de
                // continuation 0x80-0xBF ne peut jamais entamer la sequence recherchee).
                $at = strpos($word, $pivot);

                if ($at === false) {
                    return e($word);
                }

                return e(substr($word, 0, $at)) . '<mark>' . e($pivot) . '</mark>' . e(substr($word, $at + strlen($pivot)));

            default:
                return e($word);
        }
    };

    // Regroupement par lettre ajoutee ("+A", "+I", "+T"...), uniquement pour anagrammesPlusOne
    // (seule categorie ou le CSS du prototype prevoit .word-text/.plus) -- addedLetter est
    // deja fourni par TermRelations, aucune donnee inventee cote vue.
    $plusOneGroups = [];
    foreach ($relations->anagramsPlusOne as $item) {
        $plusOneGroups[$item['addedLetter']][] = $item;
    }
    ksort($plusOneGroups, SORT_STRING);

    // Titres de categorie (ADAPTATION ALLEMANDE, cette passe) : "Anagramme" reprend le
    // consensus fort du rapport concurrentiel (reports/de-serp-terminology-research.md,
    // section 2.5, 4/4 sources). Les huit autres titres (changer/retirer/inserer une lettre,
    // sous-mots, rallonges gauche/droite, "mot dans un mot plus long", anagrammes +-1 lettre)
    // NE SONT COUVERTS PAR AUCUNE source du rapport (categories propres a ce site, pas un
    // gabarit SERP observe chez un concurrent) -- traduction descriptive directe, signalee
    // explicitement ici et dans le rapport de tache plutot que devinee en silence.
    $relationCategories = [
        [
            'key' => 'anagrams', 'title' => 'Anagramme', 'items' => $relations->anagrams, 'full' => false,
            'count' => $countLabel(count($relations->anagrams)),
        ],
        [
            'key' => 'changeOneLetter', 'title' => 'Einen Buchstaben Ändern', 'items' => $relations->changeOneLetter, 'full' => false,
            'count' => $countLabel(count($relations->changeOneLetter)),
        ],
        [
            'key' => 'removeOneLetter', 'title' => 'Einen Buchstaben Entfernen', 'items' => $relations->removeOneLetter, 'full' => false,
            'count' => $countLabel(count($relations->removeOneLetter)),
        ],
        [
            'key' => 'insertOneLetter', 'title' => 'Einen Buchstaben Einfügen', 'items' => $relations->insertOneLetter, 'full' => false,
            'count' => $countLabel(count($relations->insertOneLetter)),
        ],
        [
            'key' => 'substrings', 'title' => 'Teilwörter', 'items' => $relations->substrings, 'full' => false,
            'count' => $countLabel(count($relations->substrings)),
        ],
        [
            'key' => 'rightExtensions', 'title' => 'Verlängerungen Nach Rechts', 'items' => $relations->rightExtensions, 'full' => true,
            'count' => $countLabel($relations->rightExtensionsTotal, $relations->rightExtensionsTruncated),
            'moreUrl' => count($relations->rightExtensions) < $relations->rightExtensionsTotal ? $extensionUrl('beginnend-mit', $pivot) : null,
            'moreLabel' => $moreLinkLabel($relations->rightExtensionsTotal, $relations->rightExtensionsTruncated),
        ],
        [
            'key' => 'leftExtensions', 'title' => 'Verlängerungen Nach Links', 'items' => $relations->leftExtensions, 'full' => true,
            'count' => $countLabel($relations->leftExtensionsTotal, $relations->leftExtensionsTruncated),
            'moreUrl' => count($relations->leftExtensions) < $relations->leftExtensionsTotal ? $extensionUrl('endend-mit', $pivot) : null,
            'moreLabel' => $moreLinkLabel($relations->leftExtensionsTotal, $relations->leftExtensionsTruncated),
        ],
        [
            'key' => 'containingWords', 'title' => $pivot . ' In Einem Längeren Wort', 'items' => $relations->containingWords, 'full' => true,
            'count' => $countLabel($relations->containingWordsTotal, $relations->containingWordsTruncated),
            // Pas de lien "Voir les N mots" ici (retire, audit final 3e passe, bloquant) :
            // pointerait vers /woerter/contenant/{mot} SANS ancrage, exactement le parcours complet
            // de la table que la correction C1 rend correct mais couteux -- voir le commentaire
            // de RelationsFinder::relatedSearches() pour le detail complet. Le compte total
            // ($countLabel ci-dessus) reste affiche, seul le lien cliquable disparait.
            'moreUrl' => null,
            'moreLabel' => $moreLinkLabel($relations->containingWordsTotal, $relations->containingWordsTruncated),
        ],
        [
            'key' => 'anagramsPlusOne', 'title' => 'Anagramme Mit Einem Buchstaben Mehr', 'items' => $relations->anagramsPlusOne, 'full' => true,
            'count' => $countLabel(count($relations->anagramsPlusOne)),
            'groups' => $plusOneGroups,
        ],
        [
            'key' => 'anagramsMinusOne', 'title' => 'Anagramme Mit Einem Buchstaben Weniger', 'items' => $relations->anagramsMinusOne, 'full' => true,
            'count' => $countLabel(count($relations->anagramsMinusOne)),
        ],
    ];

    // Libelle lisible d'une recherche liee, reconstruit par reparse de l'URL deja fournie par
    // le backend (App\Search\WordListFilters::fromPath(), meme technique que app/View/word-list.php
    // pour son propre titre) -- jamais de concatenation manuelle de chaine metier.
    $relatedLabel = static function (array $link, string $pivot): string {
        if ($link['type'] === 'play') {
            return 'Wortsuche Mit ' . $pivot;
        }

        if ($link['type'] === 'exploreAll') {
            return 'Alle Wörter Durchsuchen';
        }

        $rawPath = preg_replace('#^/woerter/#', '', $link['url']) ?? $link['url'];
        $filters = WordListFilters::fromPath($rawPath);

        if ($filters === null) {
            return $pivot;
        }

        if ($filters->withLetters !== []) {
            $letters = array_keys($filters->withLetters);
            $count = count($letters);
            $joined = $count > 1
                ? implode(', ', array_slice($letters, 0, -1)) . ' und ' . $letters[$count - 1]
                : $letters[0];

            // "Buchstabe"/"Buchstaben" (pas un simple suffixe -s comme en francais).
            return sprintf('%d %s Mit %s', $filters->length, $filters->length > 1 ? 'Buchstaben' : 'Buchstabe', $joined);
        }

        // "Beginnend Mit X"/"Endend Mit X" volontairement CONSERVES ici (registre passe en revue,
        // D-DE-029+) : $relatedLabel n'alimente que des pastilles courtes de ".related-links"
        // ("Verwandte Suchen" ci-dessous), jamais une phrase/H1 -- meme compromis que le cousin
        // espagnol (ES/app/View/word.php, "Empiezan Por X"/"Terminan En X" dans la MEME fonction
        // relatedLabel(), verifie explicitement avant cette decision). app/View/home.php et
        // app/View/word-list.php (H1/H2 de phrase complete, labels de champ de formulaire) ont
        // ete reformules dans ce meme lot, pas ces pastilles.
        if ($filters->prefix !== null) {
            return 'Beginnend Mit ' . $filters->prefix;
        }

        if ($filters->suffix !== null) {
            return 'Endend Mit ' . $filters->suffix;
        }

        if ($filters->contains !== null) {
            // "Enthält" (contient) : concept non couvert par reports/de-serp-terminology-
            // research.md (aucun concurrent audite n'expose ce type de lien) -- traduction
            // litterale directe, signalee explicitement plutot que devinee en silence.
            return 'Enthält ' . $filters->contains;
        }

        if ($filters->length !== null) {
            // "Wörter mit N Buchstaben" (D-DE-009) : consensus tres fort de la recherche
            // concurrentielle (reports/de-serp-terminology-research.md section 2.2).
            return sprintf('Wörter Mit %d Buchstaben', $filters->length);
        }

        return $pivot;
    };
}

// Nature grammaticale + genre (D-018) : une ligne discrete, absente si $page->pos est null
// (terme non couvert par Kartmaan -- absence de donnee, pas une erreur). Jeu ferme de 9
// codes, gender jamais associe a un pos autre que N (voir docs/DECISIONS.md D-018).
$posLabels = [
    'N' => 'Nom',
    'V' => 'Verbe',
    'Adj' => 'Adjectif',
    'Adv' => 'Adverbe',
    'Pronom' => 'Pronom',
    'Prep' => 'Préposition',
    'Conj' => 'Conjonction',
    'Interj' => 'Interjection',
    'Art' => 'Article',
];
$genderLabels = ['m' => 'masculin', 'f' => 'féminin', 'e' => 'épicène'];

// $posLine reste le repli quand aucune definition n'existe encore pour ce terme (lot
// partiel, D-0XX) -- des qu'au moins un sens existe, chaque carte de sens porte deja son
// propre pos/genre : $posLine deviendrait une redite, elle n'est alors pas construite.
$posLine = null;
if ($senses->senses === [] && $page->pos !== null && isset($posLabels[$page->pos])) {
    $posLine = $posLabels[$page->pos];

    if ($page->pos === 'N' && $page->gender !== null && isset($genderLabels[$page->gender])) {
        $posLine .= ' ' . $genderLabels[$page->gender];
    }

    if ($page->posSecondary !== null && isset($posLabels[$page->posSecondary])) {
        $secondary = mb_strtolower($posLabels[$page->posSecondary]);

        if ($page->posSecondary === 'N' && $page->pos !== 'N' && $page->gender !== null && isset($genderLabels[$page->gender])) {
            $secondary .= ' ' . $genderLabels[$page->gender];
        }

        $posLine .= ', aussi ' . $secondary;
    }
}

// Cartes de definition (D-0XX) : une par sens, pos + genre (si nom) en etiquette, phrase de
// definition en dessous. $senses->senses est deja borne (SenseLookup::ROW_LIMIT), aucune
// pagination necessaire ici.
//
// Fusion des sens a texte identique (retour utilisateur, ex. QUIZOMADAIRES) : de nombreuses
// formes flechies sont a la fois nom ET adjectif (ex. "campagnards" = pluriel du nom ET de
// l'adjectif "campagnard") -- render_grammatical_template() produit alors la MEME phrase pour
// les deux sens (le gabarit ne varie pas selon le pos). Afficher deux cartes identiques cote a
// cote lit comme du contenu duplique ; on fusionne en UNE carte, etiquette combinee
// ("adjectif / nom"), plutot que de perdre l'un des deux sens en base.
$senseCards = [];
$cardIndexByDefinition = [];
foreach ($senses->senses as $sense) {
    $label = $posLabels[$sense['pos']] ?? $sense['pos'];
    if ($sense['pos'] === 'N' && $sense['gender'] !== null && isset($genderLabels[$sense['gender']])) {
        $label = mb_strtolower($label) . ' ' . $genderLabels[$sense['gender']];
    } else {
        $label = mb_strtolower($label);
    }

    $definitionKey = mb_strtolower(trim($sense['definition']));
    if (isset($cardIndexByDefinition[$definitionKey])) {
        $existing = $cardIndexByDefinition[$definitionKey];
        if (!in_array($label, $senseCards[$existing]['pos_labels'], true)) {
            $senseCards[$existing]['pos_labels'][] = $label;
        }
        continue;
    }

    $cardIndexByDefinition[$definitionKey] = count($senseCards);
    $senseCards[] = ['pos_labels' => [$label], 'definition' => $sense['definition']];
}
foreach ($senseCards as &$card) {
    $card['pos_label'] = implode(' / ', $card['pos_labels']);
}
unset($card);

// Conjugaison (D-018) : temps/personne traduits en francais lisible, jamais de tag anglais
// brut affiche. Selection representative fixe (docs/DECISIONS.md D-018) -- ordre canonique
// impose ici, pas l'ordre alphabetique renvoye par ConjugationLookup.
$tenseOrder = ['present', 'future', 'imperfect', 'participle_present', 'participle_past'];
$tenseLabels = [
    'present' => 'Présent',
    'future' => 'Futur',
    'imperfect' => 'Imparfait',
    'participle_present' => 'Participe présent',
    'participle_past' => 'Participe passé',
];
$personLabels = [
    '1s' => '1re pers. sing.',
    '2s' => '2e pers. sing.',
    '3s' => '3e pers. sing.',
    '1p' => '1re pers. plur.',
    '2p' => '2e pers. plur.',
    '3p' => '3e pers. plur.',
];
$personRank = array_flip(['1s', '2s', '3s', '1p', '2p', '3p']);

// asForm : phrase courte par entree ("Forme conjuguee de LEMME (temps, personne)."), jamais
// fusionnee -- reste simple meme quand un meme lemme apparait sous plusieurs temps/personnes
// (rare, ex. TABLE -> TABLER).
$conjugationFormPhrases = [];
foreach ($conjugation->asForm as $formEntry) {
    $tenseLabel = mb_strtolower($tenseLabels[$formEntry['tense']] ?? $formEntry['tense']);
    $personLabel = $formEntry['person'] !== null ? ($personLabels[$formEntry['person']] ?? null) : null;

    $conjugationFormPhrases[] = [
        'lemma' => $formEntry['lemma'],
        'slug' => $formEntry['slug'],
        'detail' => $personLabel !== null ? $tenseLabel . ', ' . $personLabel : $tenseLabel,
    ];
}

// asLemma : regroupe par temps (comme anagramsPlusOne regroupe par lettre ajoutee, meme
// composant .word-stream) puis par forme -- deux personnes homographes (ex. "pose" pour
// 1s ET 3s) partagent la meme cible /wort/pose et ne doivent donc apparaitre qu'une fois
// dans le flux ; les personnes concernees restent visibles au survol (title).
$conjugationLemmaGroups = [];
foreach ($conjugation->asLemma as $formEntry) {
    $tense = $formEntry['tense'];
    $slug = $formEntry['slug'];

    if (!isset($conjugationLemmaGroups[$tense][$slug])) {
        $conjugationLemmaGroups[$tense][$slug] = ['form' => $formEntry['form'], 'slug' => $slug, 'persons' => []];
    }

    if ($formEntry['person'] !== null) {
        $conjugationLemmaGroups[$tense][$slug]['persons'][] = $formEntry['person'];
    }
}
foreach ($conjugationLemmaGroups as &$groupsBySlug) {
    foreach ($groupsBySlug as &$group) {
        usort($group['persons'], static fn (string $a, string $b): int => ($personRank[$a] ?? 99) <=> ($personRank[$b] ?? 99));
    }
    unset($group);
}
unset($groupsBySlug);

$conjugationHeading = $conjugation->asLemma !== [] ? 'Se Conjugue' : 'Conjugaison';
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="<?= e($seo->robots) ?>">
<title><?= e($statusMeta['title']) ?> | WORD CHECKR</title>
<meta name="description" content="<?= e($statusMeta['direct']) ?>">
<?php if ($seo->canonicalUrl !== null): ?>
<link rel="canonical" href="<?= e($seo->canonicalUrl) ?>">
<?php endif; ?>
<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="shortcut icon" href="/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<meta name="apple-mobile-web-app-title" content="WordCheckr">
<link rel="manifest" href="/site.webmanifest">
<link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>
<a class="skip-link" href="#main">Zum Inhalt springen</a>
<header class="header">
  <div class="site header-row">
    <a class="logo" href="/"><img class="logo-mark" src="/assets/img/logo.png" alt="" width="32" height="32">WORD CHECKR</a>
    <nav class="nav" aria-label="Hauptnavigation"><a href="/">Neue Suche</a></nav>
  </div>
</header>

<main class="word-shell main" id="main">
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a> › Wort <?= e($page->normalized) ?></nav>

  <article class="word-card">
    <section class="word-answer">
      <span class="status-badge status-badge--<?= e($statusMeta['modifier']) ?>"><?= e($statusMeta['badge']) ?></span>
      <h1 class="word-title"><?= e($page->normalized) ?></h1>
      <p><?= e($statusMeta['subtitle']) ?></p>
      <?php
      // D-DE-011 (docs/DECISIONS.md) : pastilles ODS8/ODS9 retirees ici -- le schema
      // allemand n'a pas d'equivalent public a un split par edition de dictionnaire
      // francais (is_enz/is_hippler sont des sources internes, jamais affichees, D-015).
      // .status-badge ci-dessus reflete deja is_admitted a lui seul : rien a ajouter.
      // Commentaire PHP (pas HTML <!-- -->) : un commentaire HTML est envoye au client,
      // jamais souhaitable pour une note d'implementation interne (audit independant).
      ?>
    </section>

    <section class="facts">
      <div class="fact">
        <strong><?= e($page->score) ?></strong>
        <span>Punkte Ohne Bonus</span>
      </div>
      <div class="fact">
        <strong><?= e($page->length) ?></strong>
        <span>Buchstaben</span>
      </div>
      <div class="fact fact-letters">
        <div class="letter-tiles" role="img" aria-label="<?= e($tilesAriaLabel) ?>">
<?php foreach ($page->letters as $tile): ?>
          <span class="letter-tile" aria-hidden="true"><?= e($tile['letter']) ?><small><?= e($tile['value']) ?></small></span>
<?php endforeach; ?>
        </div>
        <span>Verwendete Buchstaben</span>
      </div>
    </section>

    <section class="direct">
      <h2>Direkte Antwort</h2>
      <p><?= e($statusMeta['direct']) ?></p>
<?php if ($posLine !== null): ?>
      <p class="pos-line"><?= e($posLine) ?></p>
<?php endif; ?>
    </section>

<?php if ($senseCards !== []): ?>
    <section class="word-senses">
      <h2 class="sr-only">Définition</h2>
<?php foreach ($senseCards as $card): ?>
      <div class="sense-card">
        <p class="sense-meta"><span class="sense-label">Définition</span> <span class="sense-pos"><?= e($card['pos_label']) ?></span></p>
        <p class="sense-text"><?= e($card['definition']) ?></p>
      </div>
<?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($conjugation->asLemma !== [] || $conjugation->asForm !== []): ?>
    <section class="conjugation">
      <h2><?= e($conjugationHeading) ?></h2>
<?php foreach ($conjugationFormPhrases as $phrase): ?>
      <p class="conjugation-form">Forme conjuguée de <a href="/wort/<?= e($phrase['slug']) ?>"><?= e($phrase['lemma']) ?></a> (<?= e($phrase['detail']) ?>).</p>
<?php endforeach; ?>
<?php if ($conjugation->asLemma !== []): ?>
      <p class="word-stream">
<?php foreach ($tenseOrder as $tenseKey): ?>
<?php if (!isset($conjugationLemmaGroups[$tenseKey])): continue; endif; ?>
<span class="word-text"><span class="plus"><?= e($tenseLabels[$tenseKey]) ?></span></span> <?php foreach ($conjugationLemmaGroups[$tenseKey] as $group): ?><a href="/wort/<?= e($group['slug']) ?>"<?php if ($group['persons'] !== []): ?> title="<?= e(implode(' / ', array_map(static fn (string $p): string => $personLabels[$p] ?? $p, $group['persons']))) ?>"<?php endif; ?>><?= e($group['form']) ?></a> <?php endforeach; ?>
<?php endforeach; ?>
      </p>
<?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($relations !== null): ?>
    <section class="relations">
      <h2 class="relations-title">Rund Um <?= e($page->normalized) ?> Spielen</h2>
      <p class="relations-intro">Nur die passenden Kategorien werden angezeigt. Der beibehaltene oder geänderte Teil ist leicht hervorgehoben.</p>
      <div class="relation-grid">
<?php foreach ($relationCategories as $category): ?>
<?php if ($category['items'] === []): continue; endif; ?>
        <section class="relation<?= $category['full'] ? ' full' : '' ?>">
          <h3><span><?= e($category['title']) ?></span><span class="relation-count"><?= e($category['count']) ?></span></h3>
          <p class="word-stream">
<?php if (isset($category['groups'])): ?>
<?php foreach ($category['groups'] as $letter => $groupItems): ?>
<span class="word-text"><span class="plus">+<?= e($letter) ?></span></span> <?php foreach ($groupItems as $item): ?><a href="/wort/<?= e($item['slug']) ?>"><?= e($item['normalized']) ?></a> <?php endforeach; ?>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($category['items'] as $item): ?><a href="/wort/<?= e($item['slug']) ?>"><?= $highlighted($item, $category['key'], $page->normalized) ?></a> <?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($category['moreUrl'])): ?><a class="more-link" href="<?= e($category['moreUrl']) ?>"><?= e($category['moreLabel']) ?></a><?php endif; ?>
          </p>
        </section>
<?php endforeach; ?>
      </div>
    </section>

<?php if ($relations->relatedSearches !== []): ?>
    <section class="related">
      <h2>Verwandte Suchen</h2>
      <div class="related-links">
<?php foreach ($relations->relatedSearches as $link): ?>
        <a href="<?= e($link['url']) ?>"><?= e($relatedLabel($link, $page->normalized)) ?></a>
<?php endforeach; ?>
      </div>
    </section>
<?php endif; ?>
<?php endif; ?>

    <?php
    // mb_strtolower (pas strtolower ASCII) sur les deux liens ci-dessous : correctif
    // signale (audit independant, docs/DECISIONS.md D-DE-011) -- meme raison que
    // $extensionUrl plus haut. Commentaire PHP (pas HTML), jamais envoye au client.
    ?>
    <nav class="word-nav" aria-label="Alphabetische Navigation">
<?php if ($page->previousWord !== null): ?>
      <a href="/wort/<?= e(mb_strtolower($page->previousWord, 'UTF-8')) ?>">← <?= e($page->previousWord) ?></a>
<?php else: ?>
      <span></span>
<?php endif; ?>
<?php if ($page->nextWord !== null): ?>
      <a href="/wort/<?= e(mb_strtolower($page->nextWord, 'UTF-8')) ?>"><?= e($page->nextWord) ?> →</a>
<?php else: ?>
      <span></span>
<?php endif; ?>
    </nav>

    <form class="inline-check" action="/pruefen" method="get">
      <label class="sr-only" for="wort-check">Ein anderes Wort prüfen</label>
      <input class="field" type="text" id="wort-check" name="wort" maxlength="15" autocomplete="off" spellcheck="false" placeholder="Ein anderes Wort prüfen">
      <button class="btn btn-primary" type="submit">Prüfen</button>
    </form>
  </article>
</main>

<footer class="footer">
  <div class="word-shell footer-row">
    <span>Unabhängiges Tool für Buchstabenspiele.</span>
    <?php // D-DE-021 : les pages legales (mentions-legales.php/confidentialite.php, noms de
    // fichier internes inchanges) ont depuis recu un contenu allemand reel (Impressum §5 TMG,
    // Datenschutzerklarung DSGVO) et leurs propres routes localisees (/impressum,
    // /datenschutz) -- l'ancienne reserve juridique ci-dessus (etiquette allemande pointant
    // vers du contenu francais) ne s'applique plus. ?>
    <span class="footer-links"><a href="/impressum">Impressum</a> · <a href="/datenschutz">Datenschutz</a> · <a href="/contact">Kontakt</a></span>
  </div>
</footer>
</body>
</html>
