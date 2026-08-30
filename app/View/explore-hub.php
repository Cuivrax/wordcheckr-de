<?php

declare(strict_types=1);

/**
 * Page hub /woerter, appelee par public/index.php avec $hub (App\Search\ExploreHub). Trois
 * grilles completes vers les familles deja indexees et finies (longueur, beginnend-mit,
 * endend-mit -- 66 liens, D-017), chacune avec son compte reel. Corrige l'absence de lien
 * entrant vers ces pages, releve par l'audit SEO final (seo-technical-auditor, C4).
 *
 * ADAPTATION ALLEMANDE (D-DE-009, docs/DECISIONS.md) : route localisee depuis "/mots" --
 * "commencant"/"terminant" -> "beginnend-mit"/"endend-mit" partout ci-dessous.
 *
 * "Contenant" n'a JAMAIS de grille ici (App\Seo\Family::NEVER_SITEMAP, combinaisons
 * infinies) -- seulement un outil de recherche borne a 3 lettres (decision produit), qui
 * soumet en GET vers /woerter?contenant=... (repli sans JavaScript deja cable par
 * public/index.php, redirection pure vers la forme canonique /woerter/contenant/{lettres}).
 *
 * Aucun credit de source (D-015). noindex/canonical deja resolus par public/index.php.
 *
 * Garde d'etat vide (D-DE-013 point 5, correctif cette passe) : les trois grilles ci-dessous
 * sont chacune derriere `if ($hub->byX !== [])` -- list_counts (schema.sql) est vide sur ce
 * depot (aucun lot dedie encore ecrit, voir CLAUDE.md "Ce Qui N'est Pas Encore Construit"),
 * donc $hub->byLength/byStart/byEnd sont TOUS vides aujourd'hui : sans ce garde, chaque
 * section rendait un <h2> suivi d'un <div class="related-links"></div> strictement vide,
 * incoherent avec la convention "pas de section vide" deja appliquee partout ailleurs (voir
 * app/View/word-list.php, $refine/$lengthLinks/... et app/View/word.php, $posLine/
 * $senseCards/relations). Cette page reste noindex,follow (decision separee, non touchee
 * ici) -- le defaut de rendu etait reel independamment de l'indexation.
 */

require __DIR__ . '/helpers.php';

use App\Search\ExploreHub;

/** @var ExploreHub $hub */
/** @var bool $error */
/** @var \App\Seo\SeoMeta $seo */
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="<?= e($seo->robots) ?>">
<title>Alle Wörter Durchsuchen | WORD CHECKR</title>
<meta name="description" content="Durchsuchen Sie die Scrabble-Wörter nach Länge, nach Anfangs- oder Endbuchstabe, oder suchen Sie Wörter mit einer bestimmten Buchstabenfolge.">
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
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a> › Alle Wörter durchsuchen</nav>

  <article class="word-card">
    <section class="word-answer">
      <h1 class="word-title explore-title">Alle Wörter Durchsuchen</h1>
      <p>Nach Länge, nach Anfangs- oder Endbuchstabe, oder nach enthaltenen Buchstaben.</p>
<?php if ($error): ?>
      <div class="alert" role="alert">Filter nicht erkannt. Überprüfen Sie Ihre Eingabe und versuchen Sie es erneut.</div>
<?php endif; ?>
    </section>

<?php if ($hub->byLength !== []): ?>
    <section class="explore-group">
      <h2>Nach Länge</h2>
      <div class="related-links">
<?php foreach ($hub->byLength as $entry): ?>
        <a href="<?= e($entry['url']) ?>"><span class="explore-label"><?= e($entry['length']) ?> Buchstaben</span> <span class="explore-count">(<?= e(number_format($entry['count'], 0, ',', ' ')) ?>)</span></a>
<?php endforeach; ?>
      </div>
    </section>
<?php endif; ?>

<?php if ($hub->byStart !== []): ?>
    <section class="explore-group">
      <h2>Nach Anfangsbuchstabe</h2>
      <div class="related-links">
<?php foreach ($hub->byStart as $entry): ?>
        <a href="<?= e($entry['url']) ?>"><span class="explore-label"><?= e($entry['letter']) ?></span> <span class="explore-count">(<?= e(number_format($entry['count'], 0, ',', ' ')) ?>)</span></a>
<?php endforeach; ?>
      </div>
    </section>
<?php endif; ?>

<?php if ($hub->byEnd !== []): ?>
    <section class="explore-group">
      <h2>Nach Endbuchstabe</h2>
      <div class="related-links">
<?php foreach ($hub->byEnd as $entry): ?>
        <a href="<?= e($entry['url']) ?>"><span class="explore-label"><?= e($entry['letter']) ?></span> <span class="explore-count">(<?= e(number_format($entry['count'], 0, ',', ' ')) ?>)</span></a>
<?php endforeach; ?>
      </div>
    </section>
<?php endif; ?>

    <section class="explore-group">
      <h2>Enthält</h2>
      <form class="inline-check" action="/woerter" method="get">
        <label class="sr-only" for="enthalten">Enthaltene Buchstaben (maximal 3)</label>
        <input class="field" type="text" id="enthalten" name="enthalten" maxlength="3" autocomplete="off" spellcheck="false" placeholder="Z. B. SCH">
        <button class="btn btn-primary" type="submit">Suchen</button>
      </form>
      <p class="help">Bis zu 3 Buchstaben, in der Reihenfolge, in der sie im Wort vorkommen.</p>
    </section>

    <form class="inline-check" action="/pruefen" method="get">
      <label class="sr-only" for="wort-check">Ein Wort prüfen</label>
      <input class="field" type="text" id="wort-check" name="wort" maxlength="15" autocomplete="off" spellcheck="false" placeholder="Ein Wort prüfen">
      <button class="btn btn-primary" type="submit">Prüfen</button>
    </form>
  </article>
</main>

<footer class="footer">
  <div class="word-shell footer-row">
    <span>Unabhängiges Tool für Buchstabenspiele.</span>
    <?php // Voir app/View/word.php pour la justification complete de ce choix (footer
    // repete a l'identique sur toutes les vues). ?>
    <span class="footer-links"><a href="/impressum">Impressum</a> · <a href="/datenschutz">Datenschutz</a> · <a href="/contact">Kontakt</a></span>
  </div>
</footer>
</body>
</html>
