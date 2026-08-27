"""Normalisation, score et dérivés — source unique de vérité (site allemand).

Toute règle de transformation d'un terme vit ici et nulle part ailleurs.
Le runtime PHP réimplémente strictement les mêmes règles (D-007) ; tout écart
entre les deux implémentations est un bug de correspondance, pas une variante
(voir app/Search/Normalizer.php).

Repris du site français (D-009) puis adapté pour l'allemand (recherche de
faisabilité, reports/de-site-feasibility-audit.md côté dépôt français, §5) :
contrairement au français, l'allemand a TROIS lettres supplémentaires
sémantiquement distinctes (Ä, Ö, Ü — jamais des variantes accentuées de
A/O/U à replier) et une lettre sans décomposition NFD (ß, Eszett) qui doit
être acceptée plutôt que rejetée.
"""

from __future__ import annotations

import re
import unicodedata

# Les ligatures ne sont PAS décomposées par NFD. Sans ce mapping explicite,
# des emprunts étrangers présents dans la source (ex. "œuvre" si un jour
# rencontré) seraient rejetés comme « caractère hors A-Z » — repris tel quel
# du site français, la règle reste correcte pour l'allemand (emprunts rares
# mais possibles, voir data/raw/PROVENANCE.md, 475 formes à diacritique
# étranger constatées dans la source enz).
LIGATURES = str.maketrans({"œ": "oe", "Œ": "OE", "æ": "ae", "Æ": "AE"})

# ß (Eszett, U+00DF) et ẞ (ß majuscule, U+1E9E) n'ont AUCUNE décomposition NFD :
# ce ne sont pas des lettres accentuées, mais une lettre à part entière. Sans ce
# mapping, "Straße", "groß", "Fuß", "weiß", "heißen" -- parmi les mots les plus
# courants de la langue -- échoueraient la validation (hors [A-ZÄÖÜ] après mise
# en majuscules). Repli sur SS avant NFD : règle officielle confirmée
# (scrabble-info.de/scrabbleturniere-und-das-eszett, voir rapport de
# faisabilité §1.a) -- le ß n'a pas de tuile propre au Scrabble allemand, un
# mot qui en contient un se pose avec deux tuiles S, jamais un blanc dédié.
ESZETT = str.maketrans({"ß": "SS", "ẞ": "SS"})

# Ä/Ö/Ü (et leurs formes minuscules) sont protégées de la décomposition NFD par
# une substitution temporaire vers des codepoints de la zone d'usage privé
# Unicode (jamais présents dans une entrée réelle), le temps de l'étape NFD +
# retrait des marques diacritiques (Mn) qui, sans cette protection, replierait
# Ä -> A, Ö -> O, Ü -> U exactement comme un é français perd son accent -- FAUX
# pour l'allemand, où ces trois lettres sont sémantiquement distinctes de
# A/O/U (schon != schön, Ofen != Öfen, Mahne != Mähne). Restaurées après le
# retrait des Mn, avant la mise en majuscules.
GERMAN_PROTECT = str.maketrans({
    "Ä": "", "ä": "",
    "Ö": "", "ö": "",
    "Ü": "", "ü": "",
})
GERMAN_RESTORE = str.maketrans({
    "": "Ä",
    "": "Ö",
    "": "Ü",
})

# Le plateau fait 15 cases : un mot de plus de 15 lettres ne peut jamais être
# posé. Le plafond s'applique donc aux DONNÉES, pas seulement à la saisie
# (D-010, héritée du site français). Mesuré directement sur enz/german-wordlist
# (data/raw/PROVENANCE.md) : 84 433 formes sur 685 789 (12,31 %) dépassent 15
# CARACTÈRES -- une proportion nettement plus lourde qu'en français (2,2 %),
# attendue et documentée (composition allemande), pas une anomalie.
MIN_LENGTH = 2
MAX_LENGTH = 15

# [A-ZÄÖÜ] et pas [A-Z] seul (différence avec le site français) : Ä/Ö/Ü sont
# des lettres de l'alphabet allemand du jeu, pas des variantes de A/O/U.
VALID_TERM = re.compile(r"^[A-ZÄÖÜ]{%d,%d}$" % (MIN_LENGTH, MAX_LENGTH))

# Valeurs des tuiles allemandes (102 tuiles au total : 100 lettres + 2 blancs),
# confirmées par deux sources indépendantes concordantes -- voir
# reports/de-site-feasibility-audit.md §2 côté dépôt français. ß n'a pas
# d'entrée : normalize() le convertit toujours en SS avant que score() ne soit
# appelé, conformément à la règle officielle (pas de tuile ß dédiée).
TILE_SCORES = {
    "E": 1, "N": 1, "S": 1, "I": 1, "R": 1, "T": 1, "U": 1, "A": 1, "D": 1,
    "H": 2, "G": 2, "L": 2, "O": 2,
    "M": 3, "B": 3, "W": 3, "Z": 3,
    "C": 4, "F": 4, "K": 4, "P": 4,
    "Ä": 6, "J": 6, "Ü": 6, "V": 6,
    "Ö": 8, "X": 8,
    "Q": 10, "Y": 10,
}


def normalize(form: str) -> str:
    """Eszett, protection Ä/Ö/Ü, ligatures, puis NFD, puis retrait des
    diacritiques, puis majuscules, puis restauration Ä/Ö/Ü.

    Ne valide pas : renvoie la forme normalisée telle quelle, éventuellement
    invalide. Utiliser is_valid() pour trancher.
    """
    form = form.translate(ESZETT)
    form = form.translate(GERMAN_PROTECT)
    form = form.translate(LIGATURES)
    form = unicodedata.normalize("NFD", form)
    form = "".join(ch for ch in form if unicodedata.category(ch) != "Mn")
    form = form.upper()
    return form.translate(GERMAN_RESTORE)


def is_valid(normalized: str) -> bool:
    """Un terme retenu ne contient que des A-ZÄÖÜ et fait de 2 à 15 lettres."""
    return VALID_TERM.match(normalized) is not None


def score(normalized: str) -> int:
    """Score brut, hors bonus de plateau. La somme des tuiles affichées doit
    toujours être égale à cette valeur."""
    return sum(TILE_SCORES[letter] for letter in normalized)


def signature(normalized: str) -> str:
    """Lettres triées : deux anagrammes partagent la même signature.

    Python compare les chaînes par CODEPOINT (pas par octet) : sorted() place
    Ä/Ö/Ü (U+00C4/D6/DC) après Z (U+005A), comme le fait déjà la comparaison
    BINARY par défaut de SQLite sur les colonnes TEXT côté PHP -- ordre
    cohérent entre le build et le runtime, mais qui n'est PAS l'ordre
    alphabétique allemand usuel (où Ä trie proche de A). Limite assumée et
    documentée (schema.sql), pas un bug : les plages d'index restent
    correctes, seul l'ordre de tri visuel des mots à diacritique diffère de la
    convention d'un dictionnaire papier allemand.
    """
    return "".join(sorted(normalized))


def reverse(normalized: str) -> str:
    """Terme inversé : permet de traiter un suffixe comme un préfixe indexé."""
    return normalized[::-1]
