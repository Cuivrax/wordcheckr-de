/*
 * Amelioration progressive uniquement (docs/04_UI_PAGES.md) : affiche les tuiles
 * directement dans le champ de saisie, a la place du texte tape (voir la classe
 * "tiles-active" ci-dessous et les regles correspondantes dans site.css). L'input
 * HTML texte reste la source de verite et le formulaire fonctionne deja sans ce
 * script (soumission GET native vers /verifier). Ce fichier est charge en "defer"
 * et ne bloque jamais le rendu.
 */
(function () {
  "use strict";

  // BUG CORRIGE (cette passe, trouve pendant la localisation du texte -- pas une
  // simple traduction) : ce tableau etait encore une copie inchangee des valeurs de
  // tuiles FRANCAISES (heritees du scaffold ed847d6, jamais adaptees), avec un
  // commentaire l'admettant explicitement ("doit rester identique a tile_scores dans
  // config/sites/fr.php"). L'apercu de tuiles affichait donc jusqu'ici de FAUX points
  // pendant la frappe (avant tout aller-retour serveur), silencieusement corrects
  // uniquement une fois la page reelle (server-rendue) affichee. Valeurs allemandes
  // reprises telles quelles de config/sites/de.php (D-DE-002/D-DE-006, deja verifiees
  // par tests/Search/TermLookupTest.php contre storage/dictionary_de.sqlite) -- doit
  // rester identique a 'tile_scores' la-bas. AEIOU (avec Ä/Ö/Ü) incluses (lettres
  // allemandes distinctes, jamais A/O/U) ; ß absent volontairement, cf.
  // stripDiacritics() ci-dessous (converti en SS par toUpperCase() avant meme
  // d'atteindre cette table, meme comportement que app/Search/Normalizer.php cote
  // serveur).
  var TILE_SCORES = {
    E: 1, N: 1, S: 1, I: 1, R: 1, T: 1, U: 1, A: 1, D: 1,
    H: 2, G: 2, L: 2, O: 2,
    M: 3, B: 3, W: 3, Z: 3,
    C: 4, F: 4, K: 4, P: 4,
    "Ä": 6, J: 6, "Ü": 6, V: 6,
    "Ö": 8, X: 8,
    Q: 10, Y: 10
  };

  function stripDiacritics(value) {
    if (typeof value.normalize !== "function") {
      return value;
    }

    // BUG CORRIGE (cette passe) : la version precedente appliquait NFD + retrait des
    // marques diacritiques (categorie Unicode Mn) SANS protection -- repliait donc
    // Ä -> A, Ö -> O, Ü -> U dans l'apercu de tuiles, alors que ce sont des lettres
    // allemandes distinctes (jamais confondues, voir app/Search/Normalizer.php,
    // D-DE-002 : "Ofen != Öfen, Mahne != Mähne"). Meme principe de protection ici,
    // cote client : substitution par des caracteres de la zone d'usage prive (U+E000+,
    // jamais presents dans une saisie utilisateur reelle) avant NFD, restauration
    // juste apres -- Ä/Ö/Ü (et leurs minuscules) traversent la fonction inchangees,
    // tout AUTRE diacritique (accent d'un mot emprunte, ex. un import etranger)
    // continue d'etre retire comme avant.
    var PROTECT = {
      "\u00c4": "\ue000", "\u00e4": "\ue001",
      "\u00d6": "\ue002", "\u00f6": "\ue003",
      "\u00dc": "\ue004", "\u00fc": "\ue005"
    };
    var RESTORE = {
      "\ue000": "\u00c4", "\ue001": "\u00e4",
      "\ue002": "\u00d6", "\ue003": "\u00f6",
      "\ue004": "\u00dc", "\ue005": "\u00fc"
    };

    var protectedValue = value.replace(/[\u00c4\u00e4\u00d6\u00f6\u00dc\u00fc]/g, function (ch) {
      return PROTECT[ch];
    });
    var stripped = protectedValue.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

    return stripped.replace(/[\ue000-\ue005]/g, function (ch) {
      return RESTORE[ch];
    });
  }

  function renderTiles(input, tilesBox) {
    // [A-Z?*] : ? et * valent joker (docs/01_MASTER_BRIEF.md), acceptes par le
    // champ chevalet de la home en plus du champ mot -- ce script est generique
    // aux deux (data-tile-preview), donc les deux glyphes doivent rester visibles.
    // ADAPTATION ALLEMANDE (cette passe) : classe etendue a Ä/Ö/Ü -- stripDiacritics()
    // ci-dessus les preserve desormais (au lieu de les replier sur A/O/U), il faut
    // donc aussi les laisser passer ici pour qu'elles atteignent TILE_SCORES.
    var letters = stripDiacritics(input.value || "").toUpperCase().match(/[A-ZÄÖÜ?*]/g) || [];

    if (letters.length === 0) {
      tilesBox.textContent = "";
      return;
    }

    var markup = "";
    for (var i = 0; i < letters.length; i++) {
      var letter = letters[i];
      var isJoker = letter === "?" || letter === "*";
      if (isJoker) {
        markup += '<span class="tile">?</span>';
        continue;
      }
      var value = TILE_SCORES[letter] || 0;
      markup += '<span class="tile">' + letter + "<small>" + value + "</small></span>";
    }
    tilesBox.innerHTML = markup;
  }

  var wraps = document.querySelectorAll("[data-tile-preview]");
  for (var w = 0; w < wraps.length; w++) {
    (function (wrap) {
      var input = wrap.querySelector("input");
      var tilesBox = wrap.querySelector(".tiles");

      if (!input || !tilesBox) {
        return;
      }

      // Pose la classe AVANT de brancher l'ecouteur : c'est elle qui active en CSS le
      // recouvrement du champ par les tuiles (site.css, .rack-wrap.tiles-active). Sans
      // cette classe (script absent ou desactive), .rack reste le champ texte normal,
      // visible et fonctionnel -- aucune regression sans JavaScript.
      wrap.classList.add("tiles-active");

      // Rendu immediat : une valeur deja presente au moment de l'init (restauration
      // d'historique/bfcache, autofill malgre autocomplete="off", ou simplement une
      // valeur non vide cote serveur) ne declenche aucun evenement "input" -- sans cet
      // appel, .rack serait deja transparent (classe posee ci-dessus) alors qu'aucune
      // tuile n'a ete dessinee pour ce texte, rendant le champ visuellement vide.
      renderTiles(input, tilesBox);

      input.addEventListener("input", function () {
        renderTiles(input, tilesBox);
      });
    })(wraps[w]);
  }
})();
