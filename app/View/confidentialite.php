<?php

declare(strict_types=1);

/**
 * Vue statique /datenschutz (D-DE-021), appelee par public/index.php sans donnees de recherche
 * (page d'information pure, aucune requete SQLite). Meme gabarit que app/View/mentions-legales.php.
 * Nom de fichier interne (confidentialite.php) INCHANGE -- identifiant technique, pas une URL
 * (D-DE-020).
 *
 * CONTENU REEL EN ALLEMAND (D-DE-021, remplace le contenu francais precedent), restructure
 * selon le formalisme habituel d'une Datenschutzerklarung DSGVO plutot que traduit mot a mot
 * depuis la structure RGPD francaise -- memes faits reels que confidentialite.php cote
 * francais (D-025ter) : aucun cookie, aucune session, seul le formulaire /contact transmet une
 * donnee saisie (mail() natif, rien de stocke cote serveur), storage/dictionary_de.sqlite
 * ouvert en lecture seule au runtime. BIGBANG MEDIA est etablie en France (seul etablissement
 * dans l'UE) : la CNIL reste l'autorite de controle CHEF DE FILE au sens du "guichet unique"
 * RGPD (article 56) -- mentionnee ci-dessous comme telle, PAS remplacee par une autorite
 * allemande fictive. La rubrique reclamation (ex-#aufsichtsbehoerde) rappelle neanmoins le droit de toute
 * personne, garanti par l'article 77 RGPD, de saisir egalement l'autorite de son propre Etat de
 * residence (le BfDI pour l'Allemagne) -- exactitude juridique verifiee avant redaction, pas
 * supposee.
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
<title>Datenschutzerklärung | WORD CHECKR</title>
<meta name="description" content="Vollständige Datenschutzerklärung von WORD CHECKR: erhobene Daten, Cookies, Dienste Dritter und Ausübung Ihrer Rechte.">
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
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/">Startseite</a> › Datenschutz</nav>

  <article class="word-card">
    <section class="word-answer">
      <h1 class="word-title">Datenschutzerklärung</h1>
      <p>Welche Daten tatsächlich erhoben werden, und wie Sie Ihre Rechte ausüben.</p>
    </section>

    <section class="direct">
      <h2>Inhaltsverzeichnis</h2>
      <ul class="legal-toc">
        <li><a href="#einleitung">Einleitung</a></li>
        <li><a href="#verantwortlicher">Verantwortlicher</a></li>
        <li><a href="#erhobene-daten">Erhobene Daten</a></li>
        <li><a href="#rechtsgrundlage">Rechtsgrundlage Der Verarbeitung</a></li>
        <li><a href="#zwecke">Zwecke Der Verarbeitung</a></li>
        <li><a href="#speicherdauer">Speicherdauer</a></li>
        <li><a href="#cookies">Cookies Und Tracker</a></li>
        <li><a href="#dienste">Dienste Und Skripte Dritter</a></li>
        <li><a href="#empfaenger">Empfänger Der Daten</a></li>
        <li><a href="#uebermittlung">Übermittlung Außerhalb Der EU</a></li>
        <li><a href="#sicherheit">Datensicherheit</a></li>
        <li><a href="#rechte">Ihre Rechte</a></li>
        <li><a href="#ausuebung">Wie Sie Ihre Rechte Ausüben</a></li>
        <li><a href="#aufsichtsbehoerde">Beschwerde Bei Einer Aufsichtsbehörde</a></li>
        <li><a href="#minderjaehrige">Daten Minderjähriger</a></li>
        <li><a href="#aenderungen">Änderungen Dieser Erklärung</a></li>
        <li><a href="#glossar">Glossar</a></li>
      </ul>
    </section>

    <section class="direct" id="einleitung">
      <h2>Einleitung</h2>
      <p>BIGBANG MEDIA legt besonderen Wert auf den Schutz der Privatsphäre der Nutzerinnen und Nutzer von WORD CHECKR. Diese Erklärung beschreibt ausführlich und ohne vage Formulierungen, welche Daten bei der Nutzung der Website tatsächlich verarbeitet werden, zu welchem Zweck, wie lange, und wie Sie die Rechte ausüben können, die Ihnen die Datenschutz-Grundverordnung (DSGVO) einräumt.</p>
      <p>Diese Erklärung ergänzt unser <a href="/impressum">Impressum</a>, das den Anbieter und den Hosting-Anbieter der Website benennt.</p>
    </section>

    <section class="direct" id="verantwortlicher">
      <h2>Verantwortlicher</h2>
      <p>Verantwortlicher im Sinne der DSGVO ist die Gesellschaft BIGBANG MEDIA, eine französische EURL, eingetragen im Handelsregister (RCS) Laval unter der Nummer SIREN 917 929 382, mit Sitz in Laval (53000), Frankreich.</p>
      <p>BIGBANG MEDIA ist ausschließlich in Frankreich niedergelassen. Als in der Europäischen Union niedergelassener Verantwortlicher, der seine Dienste auch in anderen Mitgliedstaaten anbietet, ist gemäß Artikel 27 DSGVO kein zusätzlicher Vertreter in Deutschland erforderlich; diese Pflicht betrifft nur Verantwortliche ohne jede Niederlassung in der EU.</p>
    </section>

    <section class="direct" id="erhobene-daten">
      <h2>Erhobene Daten</h2>
      <p>Diese Website verfügt über kein Benutzerkonto, kein Profil, keinen Warenkorb und keine von einem Besuch zum nächsten gespeicherte Präferenz.</p>
      <p>Jede Funktion der Website (ein Wort prüfen, spielbare Wörter mit einem Buchstabensatz finden, Wörter nach Länge, Buchstaben oder Position auflisten) funktioniert über eine einfache, lesend abgerufene Adresse, ohne Formular zur Datenspeicherung und ohne Nutzungsdatenbank. Die Suche wird vom Server unmittelbar verarbeitet und sofort nach dem Senden der Antwort vergessen; sie wird in keiner Anwendungsdatenbank gespeichert.</p>
      <p>Das einzige Formular der Website, das eine von Ihnen eingegebene Information überträgt, ist das <a href="/contact">Kontaktformular</a>. Es fragt nach einer Nachricht, Ihrer E-Mail-Adresse (damit wir Ihnen antworten können) und, falls gewünscht, Ihrem Namen. Diese Nachricht wird per E-Mail an den Anbieter der Website übermittelt und anschließend nirgendwo auf unseren Servern gespeichert; es existiert keine Datenbank gesendeter Nachrichten.</p>
      <p>Abgesehen von diesem Kontaktformular ist die einzige technisch mit Ihrem Besuch verbundene Angabe die in der Rubrik "Vom Hosting-Anbieter erhobene Daten" unten beschriebene, die nicht von einer bewussten Handlung Ihrerseits abhängt.</p>
    </section>

    <section class="direct" id="rechtsgrundlage">
      <h2>Rechtsgrundlage Der Verarbeitung</h2>
      <p>Die Verarbeitung der über das Kontaktformular gesendeten Nachricht beruht auf Ihrer ausdrücklichen Einwilligung, die durch das freiwillige Absenden des Formulars zum Ausdruck kommt (Artikel 6 Abs. 1 lit. a DSGVO).</p>
      <p>Die weiter unten beschriebene vorübergehende Speicherung technischer Verbindungsdaten durch den Hosting-Anbieter beruht auf der Erfüllung einer gesetzlichen Verpflichtung, der der Hosting-Anbieter unterliegt (Artikel 6 Abs. 1 lit. c DSGVO, in Verbindung mit dem französischen Gesetz über das Vertrauen in die digitale Wirtschaft), sowie auf dem berechtigten Interesse des Anbieters und des Hosting-Anbieters an der Sicherheit der Website (Artikel 6 Abs. 1 lit. f DSGVO).</p>
    </section>

    <section class="direct" id="zwecke">
      <h2>Zwecke Der Verarbeitung</h2>
      <p>Die Daten aus dem Kontaktformular werden ausschließlich zur Beantwortung Ihrer Nachricht verwendet. Sie dienen keinem anderen Zweck, insbesondere weder der kommerziellen Ansprache noch dem Profiling noch irgendeiner Form von Marketing-Segmentierung.</p>
      <p>Die vom Hosting-Anbieter gespeicherten technischen Verbindungsdaten dienen ausschließlich der Sicherheit des Dienstes (Missbrauchserkennung, Beantwortung einer möglichen gerichtlichen Anordnung) und werden vom Anbieter der Website niemals zur Reichweitenanalyse oder individuellen Nachverfolgung genutzt.</p>
    </section>

    <section class="direct" id="speicherdauer">
      <h2>Speicherdauer</h2>
      <p>Über das Kontaktformular eingegangene Nachrichten werden im E-Mail-Postfach des Anbieters so lange aufbewahrt, wie zur Bearbeitung Ihrer Anfrage erforderlich, und anschließend nach den üblichen Praktiken der Korrespondenzverwaltung archiviert oder gelöscht, ohne systematisch vorab festgelegte Speicherdauer über das hinaus, was für eine angemessene Nachverfolgung sinnvoll ist.</p>
      <p>Die vom Hosting-Anbieter gespeicherten technischen Verbindungsdaten werden für die nach französischem Recht für Hosting-Anbieter vorgesehene Dauer aufbewahrt, derzeit ein Jahr gemäß den geltenden Vorschriften zur Vorratsspeicherung von Verbindungsdaten.</p>
    </section>

    <section class="direct" id="cookies">
      <h2>Cookies Und Tracker</h2>
      <p>Es werden mehrere Kategorien von Cookies unterschieden: technisch notwendige Cookies (etwa ein Sitzungs-Cookie für einen Warenkorb oder eine Anmeldung), Präferenz-Cookies, Reichweitenmess-Cookies und Werbe- oder Targeting-Cookies.</p>
      <p>Diese Website verwendet keine dieser Kategorien. Es ist kein technisch notwendiges Cookie erforderlich, da die Website weder Konto noch Warenkorb noch seitenübergreifende Anmeldung anbietet. Es werden auch keine Präferenz-, Reichweitenmess- oder Werbe-Cookies gesetzt.</p>
      <p>Es wird keine mit einem Cookie vergleichbare Technologie eingesetzt (lokaler Browser-Speicher zu Tracking-Zwecken, clientseitig erzeugte Kennung, Geräte-Fingerprinting).</p>
      <p>Da kein Cookie und kein Tracker gesetzt wird, wird kein Einwilligungsbanner angezeigt: Dieser wäre gegenstandslos, da nach § 25 TTDSG und der ePrivacy-Richtlinie eine Einwilligung nur dann erforderlich ist, wenn tatsächlich ein nicht zwingend erforderliches Cookie gesetzt wird.</p>
    </section>

    <section class="direct" id="dienste">
      <h2>Dienste Und Skripte Dritter</h2>
      <p>Es wird kein Skript und kein Dienst Dritter zu Tracking- oder Profiling-Zwecken auf dieser Website geladen. Konkret bindet die Website weder Google Analytics, Matomo noch ein anderes Reichweitenmess-Tool ein; weder Google Fonts noch eine andere extern gehostete Schriftart; kein Werbeskript, keinen Conversion-Pixel, kein Retargeting-Netzwerk; keinen Social-Media-Button oder ein entsprechendes Widget; kein von einem Drittdienst gehostetes Video oder eine gehostete Karte; kein Chat- oder Kundensupport-Tool eines Drittanbieters; keinen Single-Sign-on-Dienst ("Anmelden mit" einem Drittkonto).</p>
      <p>Der einzige technische Drittakteur, der am Betrieb der Website beteiligt ist, ist der Hosting-Anbieter o2switch, beschrieben in unserem <a href="/impressum">Impressum</a>, sowie der Mailversanddienst, der zur Übermittlung der Nachrichten aus dem Kontaktformular verwendet wird.</p>
      <p>Diese Liste spiegelt den Stand der Website zum unten angegebenen Aktualisierungsdatum dieser Erklärung wider. Jede künftige Entwicklung, die einen Drittdienst hinzufügt, würde vor dessen Inbetriebnahme zu einer Aktualisierung dieses Abschnitts führen.</p>
    </section>

    <section class="direct" id="empfaenger">
      <h2>Empfänger Der Daten</h2>
      <p>Über das Kontaktformular gesendete Nachrichten gehen ausschließlich beim Anbieter der Website, BIGBANG MEDIA, ein. Es werden keine Daten zu kommerziellen, werblichen oder statistischen Zwecken verkauft, vermietet, abgetreten oder an Dritte weitergegeben.</p>
      <p>Die vom Hosting-Anbieter gespeicherten technischen Daten sind nur für den Hosting-Anbieter selbst und gegebenenfalls für eine gesetzlich berechtigte Justiz- oder Verwaltungsbehörde zugänglich, die deren Herausgabe verlangt.</p>
    </section>

    <section class="direct" id="uebermittlung">
      <h2>Übermittlung Außerhalb Der EU</h2>
      <p>Sämtliche in dieser Erklärung beschriebenen Verarbeitungen finden in Frankreich statt. Die Website wird in Frankreich von o2switch gehostet, und es werden keine Daten an einen Dienstleister außerhalb der Europäischen Union übermittelt. Bei der Nutzung dieser Website findet somit keine Datenübermittlung außerhalb der Europäischen Union statt.</p>
    </section>

    <section class="direct" id="sicherheit">
      <h2>Datensicherheit</h2>
      <p>Die Website ist nach dem Prinzip der Datenminimierung durch Technikgestaltung konzipiert: Die Wortdatenbank wird zur Laufzeit ausschließlich lesend geöffnet, was jedes versehentliche oder böswillige Schreiben auf diese Datenbank vom öffentlichen Bereich der Website aus technisch verhindert. Die Website speichert außerdem keine Benutzer- oder Nachrichtendatenbank, was die im Falle eines Sicherheitsvorfalls betroffene Angriffsfläche entsprechend verringert.</p>
      <p>Die Kommunikation zwischen Ihrem Browser und dem Server ist durch das HTTPS-Protokoll gesichert. Der Hosting-Anbieter o2switch wendet auf seiner Infrastruktur eigene physische und logische Sicherheitsmaßnahmen an, die auf seiner offiziellen Website beschrieben sind.</p>
    </section>

    <section class="direct" id="rechte">
      <h2>Ihre Rechte</h2>
      <p>Gemäß der DSGVO verfügen Sie über folgende Rechte hinsichtlich Ihrer personenbezogenen Daten.</p>
      <ul class="legal-list">
        <li>Auskunftsrecht: Bestätigung erhalten, dass Sie betreffende Daten verarbeitet werden, und eine Kopie davon erhalten.</li>
        <li>Recht auf Berichtigung: unrichtige oder unvollständige Sie betreffende Daten berichtigen lassen.</li>
        <li>Recht auf Löschung ("Recht auf Vergessenwerden"): Löschung Ihrer Daten in den von der DSGVO vorgesehenen Fällen verlangen.</li>
        <li>Recht auf Einschränkung der Verarbeitung: vorübergehende Aussetzung einer Verarbeitung in bestimmten von der DSGVO vorgesehenen Fällen verlangen.</li>
        <li>Widerspruchsrecht: einer auf berechtigtem Interesse beruhenden Verarbeitung aus Gründen widersprechen, die sich aus Ihrer besonderen Situation ergeben.</li>
        <li>Recht auf Datenübertragbarkeit: die von Ihnen bereitgestellten Daten in einem strukturierten, gängigen Format erhalten, soweit dieses Recht anwendbar ist.</li>
        <li>Recht, Ihre Einwilligung jederzeit zu widerrufen, wenn die Verarbeitung auf dieser Einwilligung beruht, ohne dass dies die Rechtmäßigkeit der vor diesem Widerruf erfolgten Verarbeitung berührt.</li>
      </ul>
      <p>Da die Website außerhalb des von Ihnen freiwillig ausgefüllten Kontaktformulars keine identifizierbaren personenbezogenen Daten verarbeitet, betrifft die Ausübung dieser Rechte in der Praxis im Wesentlichen die Nachrichten, die Sie uns gegebenenfalls gesendet haben.</p>
    </section>

    <section class="direct" id="ausuebung">
      <h2>Wie Sie Ihre Rechte Ausüben</h2>
      <p>Sie können sämtliche oben beschriebenen Rechte ausüben, indem Sie uns über unser <a href="/contact">Kontaktformular</a> schreiben und dabei den Gegenstand Ihrer Anfrage sowie das gewünschte Recht angeben.</p>
      <p>Zum Schutz Ihrer Daten vor einer in Ihrem Namen missbräuchlich gestellten Anfrage können wir Sie bitten, Ihre Identität über die bei einem früheren Austausch verwendete E-Mail-Adresse zu bestätigen, bevor wir Ihrer Anfrage nachkommen.</p>
    </section>

    <section class="direct" id="aufsichtsbehoerde">
      <h2>Beschwerde Bei Einer Aufsichtsbehörde</h2>
      <p>BIGBANG MEDIA ist ausschließlich in Frankreich niedergelassen; die zuständige federführende Aufsichtsbehörde im Sinne des DSGVO-Kohärenzverfahrens (Artikel 56) ist daher die französische Commission Nationale de l'Informatique et des Libertés (CNIL). Wenn Sie der Ansicht sind, dass Ihre Rechte nach Kontaktaufnahme mit uns nicht gewahrt wurden, können Sie bei der CNIL Beschwerde einlegen.</p>
      <p>Offizielle Website der CNIL: <a href="https://www.cnil.fr">cnil.fr</a>. Postanschrift: CNIL, 3 Place de Fontenoy, TSA 80715, 75334 Paris Cedex 07, Frankreich.</p>
      <p>Unabhängig davon räumt Artikel 77 DSGVO Ihnen das Recht ein, eine Beschwerde auch bei der Aufsichtsbehörde Ihres eigenen Wohnsitzstaats einzureichen. Für Deutschland ist dies, je nach betroffenem Bundesland oder für nicht-öffentliche Stellen ohne Landeszuständigkeit die Bundesbeauftragte für den Datenschutz und die Informationsfreiheit (BfDI), Graurheindorfer Str. 153, 53117 Bonn, <a href="https://www.bfdi.bund.de">bfdi.bund.de</a>.</p>
    </section>

    <section class="direct" id="minderjaehrige">
      <h2>Daten Minderjähriger</h2>
      <p>Diese Website ist ein für die breite Öffentlichkeit bestimmtes Werkzeug, das sich nicht gezielt an Minderjährige richtet und niemals Angaben zum Alter der Besucherinnen und Besucher abfragt. Das Kontaktformular bleibt dennoch für jede Person zugänglich, einschließlich Minderjähriger, die uns schreiben möchte; in diesem Fall gelten dieselben in dieser Erklärung beschriebenen Grundsätze der Datenminimierung.</p>
    </section>

    <section class="direct" id="aenderungen">
      <h2>Änderungen Dieser Erklärung</h2>
      <p>Diese Datenschutzerklärung kann aktualisiert werden, um eine Entwicklung der Website, ihrer Funktionen oder der geltenden Rechtsvorschriften widerzuspiegeln. Maßgeblich ist stets die auf dieser Seite veröffentlichte Fassung.</p>
      <p>Letzte Aktualisierung: August 2026.</p>
    </section>

    <section class="direct" id="glossar">
      <h2>Glossar</h2>
      <p>"Personenbezogene Daten" bezeichnet jede Information über eine identifizierte oder identifizierbare natürliche Person.</p>
      <p>"Verarbeitung" bezeichnet jeden Vorgang im Zusammenhang mit personenbezogenen Daten, wie deren Erhebung, Speicherung oder Löschung.</p>
      <p>"Verantwortlicher" bezeichnet die Person oder Stelle, die über die Zwecke und Mittel einer Verarbeitung personenbezogener Daten entscheidet.</p>
      <p>"DSGVO" bezeichnet die Datenschutz-Grundverordnung, die am 25. Mai 2018 wirksam gewordene europäische Verordnung.</p>
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
