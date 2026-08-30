<?php

declare(strict_types=1);

/**
 * Vue statique /contact, appelee par public/index.php avec $error/$success (booleens issus
 * de ?erreur=1 / ?envoye=1, meme convention F3 que app/View/home.php). Formulaire POST natif
 * vers /contact -- aucun JavaScript requis, fonctionne integralement sans (CLAUDE.md).
 *
 * Champ "site_web" cache (piege a bots, voir public/index.php) : aria-hidden, hors du flux
 * visuel (CSS), hors de l'ordre de tabulation -- invisible et inaccessible a un visiteur
 * humain, y compris au clavier ou au lecteur d'ecran, mais present dans le DOM pour les bots
 * qui remplissent tous les champs sans distinction.
 *
 * L'adresse de destination n'apparait nulle part ici ni dans public/index.php (demande
 * utilisateur, anti-spam) -- configuree uniquement cote serveur (variable d'environnement
 * SCRABBLE_CONTACT_EMAIL).
 */

require __DIR__ . '/helpers.php';

/** @var bool $error */
/** @var bool $success */
/** @var \App\Seo\SeoMeta $seo */
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="<?= e($seo->robots) ?>">
<title>Kontakt | WORD CHECKR</title>
<meta name="description" content="Kontaktieren Sie WORD CHECKR über das Formular, für eine Frage, eine Meldung oder eine Anfrage zu Ihren personenbezogenen Daten.">
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
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a> › Kontakt</nav>

  <article class="word-card">
    <section class="word-answer">
      <h1 class="word-title">Kontakt</h1>
      <p>Stellen Sie uns eine Frage, melden Sie ein fehlendes Wort oder ein technisches Problem, oder stellen Sie eine Anfrage zu Ihren personenbezogenen Daten.</p>
    </section>

    <section class="direct">
<?php if ($success): ?>
      <div class="alert" role="alert">Nachricht gesendet. Vielen Dank, wir antworten Ihnen so schnell wie möglich an die angegebene Adresse.</div>
<?php endif; ?>
<?php if ($error): ?>
      <div class="alert" role="alert">Der Versand ist fehlgeschlagen. Überprüfen Sie Ihre E-Mail-Adresse und Ihre Nachricht (maximal 5000 Zeichen) und versuchen Sie es erneut.</div>
<?php endif; ?>
      <form action="/contact" method="post">
        <div class="hp-field" aria-hidden="true">
          <label for="site_web">Webseite</label>
          <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
        </div>

        <div class="constraint-panel">
          <div class="constraint-field">
            <label class="label" for="name">Name (Optional)</label>
            <input class="field" type="text" id="name" name="name" maxlength="100" autocomplete="name">
          </div>
          <div class="constraint-field">
            <label class="label" for="email">Ihre E-Mail-Adresse</label>
            <input class="field" type="email" id="email" name="email" maxlength="254" required autocomplete="email" placeholder="sie@beispiel.de">
            <p class="help">Wird ausschließlich zur Beantwortung Ihrer Anfrage verwendet, nie veröffentlicht oder an Dritte weitergegeben.</p>
          </div>
          <div class="constraint-field constraint-field-wide">
            <label class="label" for="message">Nachricht</label>
            <textarea class="field" id="message" name="message" rows="6" maxlength="5000" required></textarea>
          </div>
          <button class="btn btn-primary" type="submit">Senden</button>
        </div>
      </form>
    </section>
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
