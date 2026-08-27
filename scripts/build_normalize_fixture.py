#!/usr/bin/env python3
"""Genere tests/fixtures/normalize_samples.json depuis scripts/lib/normalize.py.

Fixture de reference pour tests/Search/NormalizerTest.php (PHP), qui compare sa
reimplementation a la sortie reelle du script Python -- normalize.py reste la source
unique de la regle (D-009). Script de developpement : ne tourne jamais en production
(D-007), a relancer a la main si normalize.py change.

Usage :
    python scripts/build_normalize_fixture.py
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts" / "lib"))

import normalize as n  # noqa: E402

OUT = ROOT / "tests" / "fixtures" / "normalize_samples.json"

# Cas adversariaux specifiques au site allemand : Eszett (ß/ẞ -> SS, sans decomposition
# NFD), Ä/Ö/Ü distinctes de A/O/U (protegees du retrait des marques combinantes NFD,
# contrairement aux accents francais), ligatures/emprunts etrangers (pipeline generique
# repris du site francais, toujours exerce -- 475 formes a diacritique etranger
# constatees dans la source enz, voir data/raw/PROVENANCE.md), casse, espaces, chiffres,
# ponctuation, bornes de longueur (2, 15, 16, et le cas ou l'expansion ß -> SS fait
# depasser 15 caracteres apres normalisation alors que la forme brute y tenait), chaine
# vide, mots allemands reels.
RAW_SAMPLES = [
    # Eszett -- accepte, jamais rejete, converti en SS (regle officielle : pas de tuile
    # ß dediee, un mot qui en contient un se pose avec deux tuiles S).
    "Straße", "STRASSE", "straße", "groß", "Fuß", "weiß", "heißen", "ẞUSSE",
    # Ä/Ö/Ü distinctes de A/O/U -- paires minimales, jamais repliees l'une sur l'autre.
    "schon", "schön", "SCHÖN", "Ofen", "Öfen", "Mahne", "Mähne",
    "Bar", "Bär", "Hute", "Hüte", "ÄÖÜ", "äöü", "Übel", "übel",
    # Emprunts etrangers avec diacritique hors ÄÖÜäöüß -- pliage NFD generique (repris du
    # site francais), doit rester accepte apres retrait de l'accent.
    "Abbé", "Ångström", "Aperçu", "naïve", "café",
    # Ligatures rares mais possibles dans une source generique (pipeline partage).
    "œuf", "Œdipe",
    # Casse mixte, espaces, ponctuation -- toujours rejetes ou repliés selon la regle.
    "spielen", "SPIELEN", "SpIeLeN", "  spielen  ", "spielen3", "12spielen",
    "wort-zusammensetzung", "mot'apostrophe",
    # Bornes de longueur : 2 (minimum), 15 (maximum), 16 (rejete), et le cas Eszett qui
    # fait passer une forme de 15 a 16 caracteres APRES expansion en SS (doit etre
    # rejetee malgre une forme brute a 15 caracteres).
    "", "a", "ab", "abcdefghijklmno", "abcdefghijklmnop",
    "abcdefghijklmnß",  # 15 caracteres bruts (14 lettres + ß), 16 apres expansion SS
    # Mots allemands reels courants (enz/german-wordlist), dont un compose long (dans la
    # limite de 15) et une forme au pluriel avec Umlaut.
    "Aachener", "Zytozym", "Wortschatz", "Baum", "Bäume", "Haus", "Häuser",
    "aalähnlich",
    # Entrees clairement invalides.
    "123", "wort mit leerzeichen", "wort-mit-trait", "wort'apostrophe",
]


def main() -> int:
    cases = []
    for raw in RAW_SAMPLES:
        normalized = n.normalize(raw)
        valid = n.is_valid(normalized)
        cases.append(
            {
                "raw": raw,
                "normalized": normalized,
                "valid": valid,
                "score": n.score(normalized) if valid else None,
                "signature": n.signature(normalized) if valid else None,
                "reversed": n.reverse(normalized) if valid else None,
            }
        )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(cases, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print("ecrit :", OUT, "(%d cas)" % len(cases))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
