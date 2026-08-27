#!/usr/bin/env python3
"""Construit storage/dictionary_de.sqlite depuis enz/german-wordlist.

Hors ligne uniquement (D-007). La base est recreee integralement a chaque execution :
elle n'est jamais mise a jour en place. Deux executions successives produisent des
rapports au sha256 identique.

Pipeline, beaucoup plus simple que scripts/import_fr.py (une seule source, pas de
fusion ODS8/ODS9, pas de dictionnaire general independant -- voir schema.sql et
data/raw/PROVENANCE.md pour la justification complete de chaque simplification) :

    1. lecture de data/raw/enz_german_wordlist/words (685 789 formes, une par ligne)
    2. normalisation (scripts/lib/normalize.py, Eszett -> SS, Ä/Ö/Ü preservees)
    3. filtrage : bornes de longueur 2-15 CARACTERES apres normalisation (D-010) --
       seul filtre applique, la source elle-meme ne contient ni espace, ni trait
       d'union, ni apostrophe, ni chiffre (verifie directement, voir PROVENANCE.md)
    4. fusion des collisions de normalisation (plusieurs formes brutes -> meme forme
       normalisee, ex. casse ou diacritique different)
    5. score, length, signature, reversed
    6. ecriture, index, ANALYZE, VACUUM, integrity_check
    7. rapports

Usage :
    python scripts/import_de.py [--dry-run]
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import json
import sqlite3
import sys
from collections import Counter, defaultdict
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from lib.normalize import (  # noqa: E402
    MAX_LENGTH,
    MIN_LENGTH,
    is_valid,
    normalize,
    reverse,
    score,
    signature,
)

ROOT = Path(__file__).resolve().parents[1]
WORDLIST_PATH = ROOT / "data" / "raw" / "enz_german_wordlist" / "words"
SCHEMA_PATH = ROOT / "schema.sql"
TARGET_PATH = ROOT / "storage" / "dictionary_de.sqlite"
REPORTS = ROOT / "reports"


def sha256_of(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1 << 20), b""):
            digest.update(chunk)
    return digest.hexdigest()


def rejection_rule(normalized: str) -> str | None:
    """Renvoie la regle de rejet, ou None si la forme est retenue.

    La source (enz/german-wordlist) est deja tres propre -- verifie directement dans
    data/raw/PROVENANCE.md : 0 espace, 0 trait d'union, 0 apostrophe, 0 chiffre sur les
    685 789 lignes brutes. Le seul filtre reellement necessaire est la borne de longueur
    (D-010) apres normalisation -- le test de caractere hors [A-ZÄÖÜ] reste une defense
    en profondeur (ne devrait jamais declencher sur cette source), pas un filtre actif.
    """
    if len(normalized) < MIN_LENGTH:
        return "moins de %d lettres" % MIN_LENGTH
    if len(normalized) > MAX_LENGTH:
        return "plus de %d lettres (injouable sur un plateau)" % MAX_LENGTH
    if not is_valid(normalized):
        return "caractere hors A-ZÄÖÜ apres normalisation"
    return None


def load_wordlist() -> tuple[dict[str, set[str]], Counter, list[tuple[str, str]], dict[str, int]]:
    """Retourne (formes retenues, rejets, echantillon, volumetrie de la source).

    kept : normalized -> ensemble des formes BRUTES distinctes qui y menent (une
    collision de normalisation a plus d'une forme brute -- ex. casse ou diacritique
    different).
    """
    kept: dict[str, set[str]] = defaultdict(set)
    rejected: Counter = Counter()
    samples: list[tuple[str, str]] = []
    seen_rejected: set[tuple[str, str]] = set()
    source_rows = 0
    source_distinct_raw = 0
    seen_raw: set[str] = set()

    with WORDLIST_PATH.open(encoding="utf-8") as handle:
        for line in handle:
            form = line.rstrip("\n")
            if not form:
                continue
            source_rows += 1
            seen_raw.add(form)

            normalized = normalize(form)
            rule = rejection_rule(normalized)
            if rule is not None:
                rejected[rule] += 1
                key = (rule, form)
                if key not in seen_rejected:
                    seen_rejected.add(key)
                    samples.append((rule, form))
                continue
            kept[normalized].add(form)

    source_distinct_raw = len(seen_raw)
    samples.sort()
    stats = {"source_rows": source_rows, "source_distinct_raw": source_distinct_raw}
    return kept, rejected, samples, stats


def write_database(terms: dict[str, dict], metadata: dict[str, str]) -> None:
    TARGET_PATH.parent.mkdir(parents=True, exist_ok=True)
    if TARGET_PATH.exists():
        TARGET_PATH.unlink()

    connection = sqlite3.connect(TARGET_PATH)
    try:
        connection.executescript(SCHEMA_PATH.read_text(encoding="utf-8"))
        rows = (
            (
                index,
                normalized,  # display_term = normalized, sans exception (D-013, herite)
                normalized,
                entry["is_admitted"],
                score(normalized),
                len(normalized),
                signature(normalized),
                reverse(normalized),
            )
            for index, (normalized, entry) in enumerate(sorted(terms.items()), start=1)
        )
        connection.executemany(
            "INSERT INTO terms (id, display_term, normalized, is_admitted, score, length,"
            " signature, reversed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            rows,
        )
        # list_counts delibrement VIDE dans cette passe -- voir schema.sql, note en tete.
        connection.executemany(
            'INSERT INTO build_metadata ("key", "value") VALUES (?, ?)',
            sorted(metadata.items()),
        )
        connection.commit()
        connection.execute("ANALYZE")
        connection.commit()
        connection.execute("VACUUM")
    finally:
        connection.close()


def write_csv(path: Path, header: list[str], rows) -> None:
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle, lineterminator="\n")
        writer.writerow(header)
        writer.writerows(rows)


def write_json(path: Path, payload: dict) -> None:
    path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
    )


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="analyse la source et affiche le resume, sans rien ecrire",
    )
    args = parser.parse_args()

    required = [WORDLIST_PATH, SCHEMA_PATH]
    for path in required:
        if not path.exists():
            raise SystemExit("source manquante : %s" % path)

    kept, rejected, rejected_samples, src_stats = load_wordlist()

    collisions = {
        normalized: sorted(forms)
        for normalized, forms in kept.items()
        if len(forms) > 1
    }

    terms: dict[str, dict] = {
        normalized: {"is_admitted": 1}
        for normalized in kept
    }

    summary = {
        "source_rows": src_stats["source_rows"],
        "source_distinct_raw": src_stats["source_distinct_raw"],
        "distinct_normalized": len(kept),
        "normalization_collisions": len(collisions),
        "rejected_total": sum(rejected.values()),
        "rejections_by_rule": dict(sorted(rejected.items())),
        "terms_total": len(terms),
        "admitted_total": sum(1 for e in terms.values() if e["is_admitted"] == 1),
        "max_term_length": MAX_LENGTH,
        "min_term_length": MIN_LENGTH,
    }

    if args.dry_run:
        print(json.dumps(summary, ensure_ascii=False, indent=2, sort_keys=True))
        print("\n--dry-run : aucune ecriture", file=sys.stderr)
        return 0

    metadata = {
        "language": "de",
        "schema": "terms v1-de (source unique, pas de pos/gender/word_senses/verb_forms)",
        "source_wordlist_sha256": sha256_of(WORDLIST_PATH),
        "source_wordlist_commit": "e8618fbd2a996780d60005b7d3f04e4431b864fd",
        "terms_total": str(len(terms)),
    }
    write_database(terms, metadata)

    REPORTS.mkdir(parents=True, exist_ok=True)
    write_json(REPORTS / "import-summary.json", summary)
    write_json(
        REPORTS / "admitted-status-counts.json",
        {
            "admitted_total": summary["admitted_total"],
            "terms_total": summary["terms_total"],
        },
    )
    write_csv(
        REPORTS / "normalization-collisions.csv",
        ["normalized", "source_forms_count", "source_forms"],
        (
            (normalized, len(forms), " | ".join(forms))
            for normalized, forms in sorted(collisions.items())
        ),
    )
    write_csv(
        REPORTS / "duplicates.csv",
        ["normalized", "kept_display_term", "merged_source_forms"],
        (
            (normalized, normalized, " | ".join(forms))
            for normalized, forms in sorted(collisions.items())
        ),
    )
    write_csv(
        REPORTS / "rejected-forms.csv",
        ["source", "rule", "form"],
        (("enz_german_wordlist",) + row for row in rejected_samples),
    )

    connection = sqlite3.connect("file:%s?mode=ro" % TARGET_PATH.as_posix(), uri=True)
    try:
        integrity = connection.execute("PRAGMA integrity_check").fetchone()[0]
        quick = connection.execute("PRAGMA quick_check").fetchone()[0]
    finally:
        connection.close()
    (REPORTS / "sqlite-integrity.txt").write_text(
        "integrity_check: %s\nquick_check: %s\nbytes: %d\n"
        % (integrity, quick, TARGET_PATH.stat().st_size),
        encoding="utf-8",
    )

    print(json.dumps(summary, ensure_ascii=False, indent=2, sort_keys=True))
    print(
        "\nbase : %s (%.1f Mo)\nintegrity_check : %s"
        % (TARGET_PATH, TARGET_PATH.stat().st_size / 1e6, integrity),
        file=sys.stderr,
    )
    return 0 if integrity == "ok" else 1


if __name__ == "__main__":
    raise SystemExit(main())
