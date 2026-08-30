<?php

declare(strict_types=1);

/**
 * Vue solveur /wortsuche/{buchstaben}, appelee par public/index.php avec $page
 * (App\Search\RackPage, Phase 2). Meme gabarit que app/View/word.php (statut,
 * tuiles, reponse directe, formulaire de repli) -- reutilise a l'identique les
 * composants deja unifies (.status-badge, .letter-tile, .edition-badge,
 * .inline-check), etendus uniquement d'une liste de resultats.
 *
 * ADAPTATION ALLEMANDE (D-DE-009, docs/DECISIONS.md) : route localisee depuis
 * "/jouer/{lettres}" -- voir App\Search\WordListFilters pour la justification complete du
 * schema d'URL allemand.
 *
 * Trois cas distincts (voir App\Search\RackPage) :
 * - capped = true       : aucune requete executee, pas d'erreur -- message
 *                         explicite invitant a preciser le tirage.
 * - matches = [] non capped : zero mot jouable avec ce tirage.
 * - matches non vide    : liste triee, chaque mot lie vers sa fiche /wort/{slug}.
 *   totalMatches est le compte REEL (jamais limite par displayLimit) ; si
 *   truncated, une mention courte indique que seuls displayLimit resultats
 *   sont affiches.
 *
 * Aucune relation, aucune contrainte avancee (longueur, prefixe...) : Phase 3,
 * hors perimetre ici. Aucun credit de source (D-015).
 */

require __DIR__ . '/helpers.php';

use App\Search\RackPage;

/** @var RackPage $page */
/** @var array<string, int> $tileScores */
/** @var \App\Seo\SeoMeta $seo */

$letters = '';

foreach ($page->letterCounts as $letter => $count) {
    $letters .= str_repeat($letter, $count);
}

$rackDisplay = $letters . str_repeat('?', $page->jokerCount);
$rackTileCount = array_sum($page->letterCounts) + $page->jokerCount;

$tileLabelParts = [];

foreach ($page->letterCounts as $letter => $count) {
    for ($i = 0; $i < $count; $i++) {
        $tileLabelParts[] = $letter;
    }
}

for ($i = 0; $i < $page->jokerCount; $i++) {
    $tileLabelParts[] = 'Joker';
}

$tilesAriaLabel = implode(' + ', $tileLabelParts);

// ADAPTATION ALLEMANDE (cette passe) : "gültig" reprend le meme registre que
// app/View/word.php (statusMeta, D-DE-009) -- jamais "zulässig" (vocabulaire officiel SDeV,
// volontairement pas retenu, voir reports/de-serp-terminology-research.md section 3.5).
$statusMeta = match (true) {
    $page->capped => [
        'modifier' => 'not-admitted',
        'badge' => 'Zu Viele Möglichkeiten',
        'subtitle' => 'Grenzen Sie Ihre Buchstaben ein.',
        'direct' => sprintf(
            'Für %s gibt es zu viele Kombinationen, um sie hier zu berechnen. Verringern Sie die Anzahl der Buchstaben oder Joker, um eine Antwort zu erhalten.',
            $rackDisplay,
        ),
    ],
    $page->matches === [] => [
        'modifier' => 'unknown',
        'badge' => 'Kein Wort',
        'subtitle' => 'Kein spielbares Wort gefunden.',
        'direct' => sprintf(
            'Mit %s kann kein gültiges Scrabble-Wort gebildet werden.',
            $rackDisplay,
        ),
    ],
    $page->totalMatches === 1 => [
        'modifier' => 'admitted',
        'badge' => 'Wort Gefunden',
        'subtitle' => 'Sie können es spielen.',
        'direct' => sprintf('Mit %s ist 1 gültiges Scrabble-Wort möglich.', $rackDisplay),
    ],
    default => [
        'modifier' => 'admitted',
        'badge' => 'Wörter Gefunden',
        'subtitle' => 'Sie können sie spielen.',
        'direct' => sprintf(
            'Mit %s sind %d gültige Scrabble-Wörter möglich.',
            $rackDisplay,
            $page->totalMatches,
        ),
    ],
};
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="<?= e($seo->robots) ?>">
<title>Spielen <?= e($rackDisplay) ?> | WORD CHECKR</title>
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
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a> › Spielen <?= e($rackDisplay) ?></nav>

  <article class="word-card">
    <section class="word-answer">
      <span class="status-badge status-badge--<?= e($statusMeta['modifier']) ?>"><?= e($statusMeta['badge']) ?></span>
      <h1 class="word-title"><?= e($rackDisplay) ?></h1>
      <p><?= e($statusMeta['subtitle']) ?></p>
    </section>

    <section class="facts">
      <div class="fact">
        <strong><?= $page->totalMatches !== null ? e($page->totalMatches) : '—' ?></strong>
        <span>Wörter Gefunden</span>
      </div>
      <div class="fact">
        <strong><?= e($rackTileCount) ?></strong>
        <span>Verfügbare Buchstaben</span>
      </div>
      <div class="fact fact-letters">
        <div class="letter-tiles" role="img" aria-label="<?= e($tilesAriaLabel) ?>">
<?php foreach ($page->letterCounts as $letter => $count): ?>
<?php for ($i = 0; $i < $count; $i++): ?>
          <span class="letter-tile" aria-hidden="true"><?= e($letter) ?><small><?= e($tileScores[$letter] ?? 0) ?></small></span>
<?php endfor; ?>
<?php endforeach; ?>
<?php for ($i = 0; $i < $page->jokerCount; $i++): ?>
          <span class="letter-tile" aria-hidden="true">?<small>0</small></span>
<?php endfor; ?>
        </div>
        <span>Verwendete Buchstaben</span>
      </div>
    </section>

    <section class="direct">
      <h2>Direkte Antwort</h2>
      <p><?= e($statusMeta['direct']) ?></p>
    </section>

<?php if ($page->matches !== []): ?>
    <section class="rack-results">
<?php if ($page->truncated): ?>
      <p class="help rack-results-note">Beste <?= e($page->displayLimit) ?> Wörter angezeigt, von <?= e($page->totalMatches) ?> insgesamt.</p>
<?php endif; ?>
      <div class="rack-result-head" aria-hidden="true">
        <span>Wort</span><span class="rack-result-head-center">Status</span><span class="rack-result-head-right">Punkte</span><span class="rack-result-head-length">Buchstaben</span>
      </div>
      <?php
      // D-DE-011 (docs/DECISIONS.md) : une seule pastille .status-badge (deja definie,
      // reutilisee telle quelle, aucun CSS ajoute) reflete is_admitted -- remplace les
      // deux pastilles ODS8/ODS9, sans equivalent public en allemand. Toujours "admitted"
      // ici : RackSolver ne renvoie que des mots effectivement jouables. Commentaire PHP
      // (pas HTML) et place UNE SEULE FOIS hors de la boucle ci-dessous -- un commentaire
      // HTML <!-- --> a cet endroit serait envoye au client a CHAQUE ligne de resultat
      // (jusqu'a plusieurs centaines de mots, bug reel mesure et corrige ici : audit
      // independant, ~48,6% du poids d'une page /wortsuche typique).
      ?>
      <ul class="rack-result-list">
<?php foreach ($page->matches as $match): ?>
        <li class="rack-result-row">
          <a class="rack-result-word" href="/wort/<?= e($match['slug']) ?>"><?= e($match['normalized']) ?></a>
          <span class="status-badge status-badge--admitted">Gültig</span>
          <span class="rack-result-points" aria-label="<?= e($match['score']) ?> Punkte"><?= e($match['score']) ?></span>
          <span class="rack-result-length" aria-label="<?= e($match['length']) ?> Buchstaben"><?= e($match['length']) ?></span>
        </li>
<?php endforeach; ?>
      </ul>
    </section>
<?php endif; ?>

    <form class="inline-check" action="/wortsuche" method="get">
      <label class="sr-only" for="buchstaben-check">Andere Buchstaben ausprobieren</label>
      <input class="field" type="text" id="buchstaben-check" name="buchstaben" maxlength="15" autocomplete="off" spellcheck="false" placeholder="Andere Buchstaben ausprobieren">
      <button class="btn btn-primary" type="submit">Spielen</button>
    </form>
  </article>
</main>

<footer class="footer">
  <div class="word-shell footer-row">
    <span>Unabhängiges Tool für Buchstabenspiele.</span>
    <?php // Voir app/View/word.php pour la justification complete de ce choix (footer
    // repete a l'identique sur toutes les vues). ?>
    <span class="footer-links"><a href="/mentions-legales">Mentions Légales</a> · <a href="/confidentialite">Confidentialité</a> · <a href="/contact">Kontakt</a></span>
  </div>
</footer>
</body>
</html>
