<?php

declare(strict_types=1);

/**
 * Vue statique /impressum (D-DE-021), appelee par public/index.php sans donnees de recherche
 * (page d'information pure, aucune requete SQLite). Meme gabarit que les autres vues
 * (header/footer identiques, .word-card/.direct reutilises tel quel pour chaque rubrique,
 * pas de nouveau motif visuel). Nom de fichier interne (mentions-legales.php) INCHANGE --
 * identifiant technique, pas une URL, meme convention que word.php/contact.php (D-DE-020).
 *
 * CONTENU REEL EN ALLEMAND (D-DE-021, remplace le contenu francais precedent). Identite de
 * l'editeur (BIGBANG MEDIA) et de l'hebergeur (o2switch) reprises A L'IDENTIQUE de
 * mentions-legales.php cote francais (D-025ter, sources verifiees a l'epoque aupres de
 * RCS/INPI/Infogreffe, jamais inventees) -- meme personne morale, memes faits, traduits en
 * allemand et restructures selon le formalisme habituel d'un Impressum (§5 TMG) plutot que
 * traduits mot a mot depuis la structure francaise (LCEN). Nom personnel, adresse complete du
 * siege et email restent volontairement absents (meme demande explicite du proprietaire du
 * produit que D-025ter, reconduite ici a l'identique) -- cet ecart est signale ci-dessous dans
 * la rubrique "Anbieter", pas silencieusement comble. BIGBANG MEDIA est etablie en France,
 * seul Etat membre de l'UE ou elle a un etablissement -- aucun representant allemand au sens
 * de l'article 27 RGPD n'est requis (cette obligation ne vise que les responsables etablis
 * HORS UE, pas un etablissement intra-UE proposant ses services dans un autre Etat membre).
 *
 * Ponctuation allemande : Anfuehrungszeichen "..." (deutsche Form), pas de tiret cadratin
 * medial (meme discipline typographique que le reste du site DE).
 */

require __DIR__ . '/helpers.php';

/** @var \App\Seo\SeoMeta $seo */
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="<?= e($seo->robots) ?>">
<title>Impressum | WORD CHECKR</title>
<meta name="description" content="Impressum von WORD CHECKR: Anbieter, Hosting, Urheberrecht, Cookies und vollständige rechtliche Angaben zur Website.">
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
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a> › Impressum</nav>

  <article class="word-card">
    <section class="word-answer">
      <h1 class="word-title">Impressum</h1>
      <p>Anbieter, Hosting, Urheberrecht und vollständige rechtliche Angaben zur Website.</p>
    </section>

    <section class="direct">
      <h2>Inhaltsverzeichnis</h2>
      <ul class="legal-toc">
        <li><a href="#anbieter">Anbieter</a></li>
        <li><a href="#verantwortlich">Inhaltlich Verantwortlich</a></li>
        <li><a href="#hosting">Hosting</a></li>
        <li><a href="#entwicklung">Konzeption Und Entwicklung</a></li>
        <li><a href="#urheberrecht">Urheberrecht</a></li>
        <li><a href="#links">Externe Links</a></li>
        <li><a href="#cookies">Cookies Und Tracker</a></li>
        <li><a href="#dienste">Anwendungen Und Dienste Dritter</a></li>
        <li><a href="#daten">Personenbezogene Daten</a></li>
        <li><a href="#barrierefreiheit">Barrierefreiheit</a></li>
        <li><a href="#verfuegbarkeit">Verfügbarkeit Und Wartung</a></li>
        <li><a href="#aenderungen">Änderungen Dieses Impressums</a></li>
        <li><a href="#recht">Anwendbares Recht</a></li>
        <li><a href="#begriffe">Begriffsbestimmungen</a></li>
      </ul>
    </section>

    <section class="direct" id="anbieter">
      <h2>Anbieter</h2>
      <p>Diese Website WORD CHECKR, erreichbar unter www.wordcheckr.de, wird von der Gesellschaft BIGBANG MEDIA angeboten.</p>
      <p>Firma: BIGBANG MEDIA.</p>
      <p>Rechtsform: EURL (französische Einpersonen-Gesellschaft mit beschränkter Haftung), Stammkapital 1.000 €.</p>
      <p>Registereintrag: RCS Laval, SIREN 917 929 382, SIRET 917 929 382 00013 (französisches Handelsregister, gleichwertig einem deutschen Handelsregistereintrag).</p>
      <p>Angemeldete Haupttätigkeit: Code APE/NAF 6201Z, Softwareentwicklung. Der Unternehmensgegenstand umfasst die Erstellung, Verwaltung, Suchmaschinenoptimierung und Verwertung von Websites.</p>
      <p>Sitz: 53000 Laval, Frankreich. Aus Datenschutzgründen wird die vollständige Anschrift des Sitzes auf dieser Seite bewusst nicht veröffentlicht; sie bleibt über die offiziellen französischen öffentlichen Register einsehbar (Infogreffe, INPI, Unternehmensverzeichnis data.gouv.fr) für jede Person, die sie auf diesem Weg überprüfen möchte. Diese bewusste Lücke gegenüber der üblichen Vollständigkeit eines Impressums (§5 TMG) wurde dem Betreiber der Website gemeldet, nicht stillschweigend geschlossen; das <a href="/contact">Kontaktformular</a> stellt einen echten Kontaktweg zur Verfügung, ohne je eine E-Mail-Adresse zu veröffentlichen.</p>
    </section>

    <section class="direct" id="verantwortlich">
      <h2>Inhaltlich Verantwortlich</h2>
      <p>Inhaltlich verantwortlich ist der gesetzliche Vertreter der Gesellschaft BIGBANG MEDIA, auf dieser Seite nach Funktion statt namentlich benannt, aus Datenschutzgründen des Betreibers der Website.</p>
      <p>Fragen zur inhaltlichen Verantwortung können über unser <a href="/contact">Kontaktformular</a> gestellt werden.</p>
    </section>

    <section class="direct" id="hosting">
      <h2>Hosting</h2>
      <p>Die Website wird von der Gesellschaft o2switch gehostet.</p>
      <p>Firma: o2switch.</p>
      <p>Rechtsform: SAS (französische vereinfachte Aktiengesellschaft), Stammkapital 100.000 €.</p>
      <p>Sitz: Chemin des Pardiaux, 63000 Clermont-Ferrand, Frankreich.</p>
      <p>Registereintrag: RCS Clermont-Ferrand, SIREN 510 909 807, SIRET 510 909 807 00032.</p>
      <p>Telefon: +33 4 44 44 60 40.</p>
      <p>Offizielle Website: <a href="https://www.o2switch.fr">o2switch.fr</a>.</p>
      <p>Der physische Server und sämtliche Daten der Website befinden sich in Frankreich, im Gebiet der Europäischen Union.</p>
    </section>

    <section class="direct" id="entwicklung">
      <h2>Konzeption Und Entwicklung</h2>
      <p>Konzeption, Entwicklung und technische Wartung der Website erfolgen direkt durch BIGBANG MEDIA, ohne Beteiligung einer externen Agentur oder eines externen Dienstleisters für den Anwendungscode.</p>
      <p>Die Website ist in PHP ohne Anwendungsframework entwickelt, mit einer lokalen, nur lesbaren Datenbank und minimalem JavaScript im Browser, ausschließlich für fortschreitende Verbesserungen (Suchvervollständigung, Anzeige der Buchstabensteine), die den Betrieb der Website ohne aktiviertes JavaScript niemals verhindern.</p>
    </section>

    <section class="direct" id="urheberrecht">
      <h2>Urheberrecht</h2>
      <p>Die Struktur der Website, ihre Suchmaschine, der Algorithmus zur Punkteberechnung, die Organisation und Strukturierung der Wortdatenbank, die Texte, das Layout, der Quellcode, die Stylesheets und sämtliche technischen und redaktionellen Elemente der Website sind, sofern nicht anders angegeben, ausschließliches Eigentum von BIGBANG MEDIA.</p>
      <p>Dieser Schutz besteht insbesondere nach französischem Urheberrecht (Code de la propriété intellectuelle, Art. L111-1 ff.) und, für die Strukturierung und Organisation der Wortdatenbank, nach dem Sui-generis-Schutzrecht für Datenbankhersteller (Code de la propriété intellectuelle, Art. L341-1 ff.), das dem deutschen Datenbankherstellerrecht (§§ 87a ff. UrhG) inhaltlich entspricht.</p>
      <p>Die deutsche Sprache und der Status ihrer Wörter im Hinblick auf die offiziellen Scrabble-Wörterbücher gehören niemandem. Diese Website beansprucht kein Recht an den Wörtern selbst, sondern ausschließlich an ihrer eigenen technischen und redaktionellen Gestaltung, das heißt an der Art und Weise, wie diese Informationen organisiert, berechnet und dargestellt werden.</p>
      <p>Jede Vervielfältigung, Wiedergabe, Bearbeitung, Veröffentlichung oder Anpassung sämtlicher oder eines Teils der Elemente der Website, gleich mit welchem Mittel oder Verfahren, ist ohne vorherige schriftliche Zustimmung von BIGBANG MEDIA untersagt, außer für einen streng persönlichen und nicht kommerziellen Gebrauch im Rahmen der gesetzlich zulässigen Grenzen.</p>
      <p>Der Name WORD CHECKR sowie die grafischen Unterscheidungsmerkmale der Website dürfen ohne vorherige Zustimmung nicht verwendet werden.</p>
    </section>

    <section class="direct" id="links">
      <h2>Externe Links</h2>
      <p>Die Website enthält eine bewusst begrenzte Anzahl ausgehender Links, im Wesentlichen zu offiziellen Institutionen (wie der französischen Aufsichtsbehörde CNIL) oder zum Hosting-Anbieter. BIGBANG MEDIA übt keine Kontrolle über den Inhalt derart verlinkter Drittseiten aus und übernimmt keine Verantwortung für deren Inhalt, Verfügbarkeit oder eigene Praktiken im Umgang mit personenbezogenen Daten.</p>
      <p>Das Setzen eines Hyperlinks auf diese Website ist grundsätzlich frei möglich, sofern dieser Link die Interessen von BIGBANG MEDIA nicht beeinträchtigt und auf einfache Anfrage entfernt wird. Deep-Links oder die Einbindung der Website in einen Frame ohne vorherige Zustimmung sind nicht gestattet.</p>
    </section>

    <section class="direct" id="cookies">
      <h2>Cookies Und Tracker</h2>
      <p>Diese Website setzt keinerlei Cookies, weder technisch notwendige noch funktionale, Reichweitenmess- oder Werbe-Cookies. Es wird kein Tracker, kein unsichtbarer Pixel und keine vergleichbare Technologie in irgendeiner Form eingesetzt.</p>
      <p>Es wird daher kein Cookie-Einwilligungsbanner angezeigt: Dies wäre gegenstandslos, da weder nach der ePrivacy-Richtlinie noch nach § 25 TTDSG eine Einwilligung erforderlich ist, wenn kein nicht zwingend erforderliches Cookie tatsächlich gesetzt wird.</p>
      <p>Vollständige Einzelheiten zu dieser fehlenden Datenerhebung finden Sie in unserer <a href="/datenschutz">Datenschutzerklärung</a>.</p>
    </section>

    <section class="direct" id="dienste">
      <h2>Anwendungen Und Dienste Dritter</h2>
      <p>Aus bewusster Entscheidung bindet diese Website keinen Drittdienst ein, der Daten sammeln oder die Anzeige verlangsamen könnte. Konkret verwendet die Website zum Zeitpunkt der Erstellung dieser Seite:</p>
      <ul class="legal-list">
        <li>kein Reichweitenmess- oder Statistik-Tool (wie Google Analytics, Matomo oder vergleichbar);</li>
        <li>keine extern gehostete Schriftart (wie Google Fonts), sämtliche verwendeten Schriftarten sind Systemschriften, die bereits auf dem Gerät der Besucherin oder des Besuchers vorhanden sind;</li>
        <li>kein externes Content Delivery Network (CDN) zum Laden von Code, Stilen oder Bildern der Website;</li>
        <li>kein eingebundenes Social-Media-Modul (Teilen-Button, Like- oder Kommentar-Widget);</li>
        <li>kein von einem Drittdienst gehostetes Video oder keine gehostete Karte (wie YouTube oder Google Maps);</li>
        <li>kein Instant-Messaging- oder Online-Chat-Tool eines Drittanbieters;</li>
        <li>keine Werbevermarktung und kein Retargeting-Netzwerk;</li>
        <li>keinen Single-Sign-on-Dienst eines Drittanbieters (wie "Anmelden mit Google" oder "Anmelden mit Facebook"), die Website bietet ohnehin kein Benutzerkonto an;</li>
        <li>keinen Online-Zahlungsdienst, die Website ist vollständig kostenlos und ohne Verkaufsfunktion.</li>
      </ul>
      <p>Der einzige Drittakteur, der am Betrieb der Website beteiligt ist, ist der Hosting-Anbieter o2switch, beschrieben in der Rubrik "Hosting" oben, sowie der Mailversanddienst, der für die Weiterleitung der über unser <a href="/contact">Kontaktformular</a> gesendeten Nachrichten verwendet wird.</p>
    </section>

    <section class="direct" id="daten">
      <h2>Personenbezogene Daten</h2>
      <p>Die Verarbeitung personenbezogener Daten, die betroffenen Datenkategorien, ihre Rechtsgrundlage, ihre Aufbewahrungsdauer und die Modalitäten zur Ausübung Ihrer Rechte sind vollständig in unserer <a href="/datenschutz">Datenschutzerklärung</a> dargestellt.</p>
    </section>

    <section class="direct" id="barrierefreiheit">
      <h2>Barrierefreiheit</h2>
      <p>Diese Website ist so konzipiert, dass sie ohne JavaScript nutzbar bleibt, mit durchdachtem Farbkontrast, funktionierender Tastaturnavigation und einer stimmigen Überschriftenstruktur. Sie ist noch nicht Gegenstand einer förmlichen Barrierefreiheitserklärung im Sinne der Barrierefreie-Informationstechnik-Verordnung (BITV 2.0), Barrierefreiheit bleibt aber ein verfolgtes Ziel bei der Gestaltung der Website.</p>
      <p>Falls Sie bei der Nutzung dieser Website auf eine Barriere stoßen, können Sie uns dies über unser <a href="/contact">Kontaktformular</a> mitteilen.</p>
    </section>

    <section class="direct" id="verfuegbarkeit">
      <h2>Verfügbarkeit Und Wartung Der Website</h2>
      <p>BIGBANG MEDIA bemüht sich um einen dauerhaften Zugang zur Website, ohne absolute Garantie für ständige Verfügbarkeit. Die Website kann zeitweise für Wartungsarbeiten, technische Aktualisierungen oder aus Gründen unterbrochen werden, die außerhalb der zumutbaren Kontrolle des Anbieters liegen (Ausfall des Hosting-Anbieters, Netzwerkstörung).</p>
      <p>Die von der Website angezeigten Informationen (Scrabble-Zulässigkeit, Punktwerte, Wortlisten) werden zur Orientierung bereitgestellt und können trotz sorgfältiger Erstellung in seltenen Fällen einen Fehler oder eine Auslassung enthalten.</p>
    </section>

    <section class="direct" id="aenderungen">
      <h2>Änderungen Dieses Impressums</h2>
      <p>BIGBANG MEDIA behält sich das Recht vor, dieses Impressum jederzeit zu ändern, insbesondere um einer gesetzlichen oder regulatorischen Entwicklung zu entsprechen oder eine Änderung in der Organisation der Website widerzuspiegeln. Wir empfehlen Ihnen, diese Seite regelmäßig zu konsultieren.</p>
      <p>Letzte Aktualisierung: August 2026.</p>
    </section>

    <section class="direct" id="recht">
      <h2>Anwendbares Recht</h2>
      <p>Dieses Impressum unterliegt französischem Recht unter Ausschluss jeder anderen Rechtsordnung. Jeder Streit im Zusammenhang mit der Nutzung der Website unterliegt, mangels vorheriger gütlicher Einigung, der ausschließlichen Zuständigkeit der französischen Gerichte.</p>
    </section>

    <section class="direct" id="begriffe">
      <h2>Begriffsbestimmungen</h2>
      <p>"Anbieter" bezeichnet die juristische Person, die für den auf der Website veröffentlichten Inhalt verantwortlich ist, hier BIGBANG MEDIA.</p>
      <p>"Hosting-Anbieter" bezeichnet die Gesellschaft, die die technische Speicherung der Website auf ihren Servern gewährleistet, hier o2switch.</p>
      <p>"Cookie" oder "Tracker" bezeichnet jede Datei oder Information, die während der Nutzung auf dem Endgerät einer Nutzerin oder eines Nutzers abgelegt wird und eine spätere Wiedererkennung ermöglicht.</p>
      <p>"Nutzerin", "Nutzer" oder "Besucher" bezeichnet jede Person, die die Website unabhängig von ihrer Zugriffsart aufruft.</p>
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
    <span class="footer-links"><a href="/impressum">Impressum</a> · <a href="/datenschutz">Datenschutz</a> · <a href="/contact">Kontakt</a></span>
  </div>
</footer>
</body>
</html>
