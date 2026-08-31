#!/usr/bin/env python3
"""Construit storage/dictionary_de.sqlite depuis enz/german-wordlist + hippler/german-wordlist
+ kaikki.org/dewiktionary (D-DE-029).

Hors ligne uniquement (D-007). La base est recreee integralement a chaque execution :
elle n'est jamais mise a jour en place. Deux executions successives produisent des
rapports au sha256 identique.

Pipeline (D-DE-006 : fusion a deux sources Scrabble avec provenance par mot, voir schema.sql
et data/raw/PROVENANCE.md pour la justification complete ; D-DE-029 : troisieme source,
"allemand reel non necessairement admis", equivalent direct de is_spanish/kaikki_es cote
espagnol) :

    1. lecture de data/raw/enz_german_wordlist/words (685 789 formes, une par ligne), de
       data/raw/hippler_de/scrabble-german-DE-HIPPLER.json (336 208 formes, {"words": [...]})
       et de data/raw/kaikki_de/kaikki-dictionary-deutsch.jsonl.gz (extrait Wiktionnaire
       allemand natif, filtre par pos -- KAIKKI_POS_EXCLUDED)
    2. normalisation (scripts/lib/normalize.py, Eszett -> SS, Ä/Ö/Ü preservees) et filtrage
       (bornes de longueur 2-15 CARACTERES apres normalisation, D-010) -- IDENTIQUE pour les
       trois sources ; enz/hippler n'ont ni espace/trait d'union/apostrophe/chiffre (verifie
       directement, voir PROVENANCE.md), kaikki_de si (formes multi-mots, locutions) --
       rejection_rule() filtre desormais aussi sur la forme BRUTE, pas seulement la normalisee
    3. fusion : chaque forme normalisee retenue par au moins une source devient un terme, avec
       is_enz/is_hippler/is_german poses independamment -- une forme brute differente par
       source qui se rejoint apres normalisation (ex. Eszett vs "ss") compte comme presente
       dans LES DEUX sources, pas une collision a departager. is_admitted reste DERIVEE de
       is_enz/is_hippler UNIQUEMENT (jamais is_german, D-DE-029) : une forme is_german=1 seule
       est reelle mais non admise, troisieme statut du modele CLAUDE.md.
    4. score, length, signature, reversed
    5. ecriture, index, ANALYZE, VACUUM, integrity_check
    6. rapports

Usage :
    python scripts/import_de.py [--dry-run]
"""

from __future__ import annotations

import argparse
import csv
import gzip
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
ENZ_PATH = ROOT / "data" / "raw" / "enz_german_wordlist" / "words"
HIPPLER_PATH = ROOT / "data" / "raw" / "hippler_de" / "scrabble-german-DE-HIPPLER.json"
KAIKKI_DE_PATH = ROOT / "data" / "raw" / "kaikki_de" / "kaikki-dictionary-deutsch.jsonl.gz"
SCHEMA_PATH = ROOT / "schema.sql"
TARGET_PATH = ROOT / "storage" / "dictionary_de.sqlite"
REPORTS = ROOT / "reports"

# commit enz au moment du telechargement (data/raw/PROVENANCE.md) -- fige ici pour que
# build_metadata reste reproductible sans re-interroger GitHub a chaque build.
ENZ_COMMIT = "e8618fbd2a996780d60005b7d3f04e4431b864fd"

# pos kaikki.org ecartes pour la couche "mot allemand reel" (is_german) : noms propres,
# locutions (deja ecartees par le filtre espace, exclusion redondante mais explicite),
# caracteres isoles, abreviations/acronymes (mesure sur kaikki_de : "HI", "UTC", "PKW", "WHO",
# "lol" -- PAS des mots reels, categorie absente du filtre equivalent espagnol/francais mais
# necessaire ici, mesuree explicitement, voir data/raw/PROVENANCE.md), pos non resolu.
KAIKKI_POS_EXCLUDED = frozenset({"name", "phrase", "character", "symbol", "unknown", "suffix", "prefix", "infix", "abbrev"})


def sha256_of(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1 << 20), b""):
            digest.update(chunk)
    return digest.hexdigest()


def rejection_rule(form: str, normalized: str) -> str | None:
    """Renvoie la regle de rejet, ou None si la forme est retenue.

    Les deux sources Scrabble (enz/german-wordlist, hippler/german-wordlist) sont deja tres
    propres -- verifie directement dans data/raw/PROVENANCE.md : 0 espace, 0 trait d'union,
    0 apostrophe, 0 chiffre sur les lignes brutes des deux fichiers. Les controles sur la
    forme BRUTE (espace/trait d'union/apostrophe/chiffre, D-DE-029) ne declenchent donc jamais
    sur enz/hippler -- ils existent pour filtrer REELLEMENT kaikki_de (locutions, formes
    composees avec trait d'union frequentes en allemand, ex. "Ich-Erzahler"), meme patron que
    scripts/import_es.py (site espagnol, kaikki_es).
    """
    if " " in form:
        return "espace"
    if "-" in form:
        return "trait d'union"
    if "'" in form or "’" in form:
        return "apostrophe"
    if any(ch.isdigit() for ch in form):
        return "chiffre"
    if len(normalized) < MIN_LENGTH:
        return "moins de %d lettres" % MIN_LENGTH
    if len(normalized) > MAX_LENGTH:
        return "plus de %d lettres (injouable sur un plateau)" % MAX_LENGTH
    if not is_valid(normalized):
        return "caractere hors A-ZÄÖÜ apres normalisation"
    return None


def read_enz_raw_words() -> list[str]:
    """Une forme brute par ligne, deja UTF-8/LF (verifie a la reception, voir PROVENANCE.md)."""
    with ENZ_PATH.open(encoding="utf-8") as handle:
        return [line.rstrip("\n") for line in handle if line.rstrip("\n")]


def read_hippler_raw_words() -> list[str]:
    """{"words": [...]} -- meme forme que data/raw/ods8.json cote depot francais."""
    payload = json.loads(HIPPLER_PATH.read_text(encoding="utf-8"))
    return list(payload["words"])


def load_source(raw_words: list[str]) -> tuple[
    dict[str, set[str]], Counter, list[tuple[str, str]], dict[str, int]
]:
    """Normalise, filtre et deduplique une source. Retourne (formes retenues, rejets,
    echantillon, volumetrie) -- meme forme de retour pour les deux sources, fusionnees
    ensuite par build_terms().

    kept : normalized -> ensemble des formes BRUTES distinctes de CETTE source qui y menent.
    """
    kept: dict[str, set[str]] = defaultdict(set)
    rejected: Counter = Counter()
    samples: list[tuple[str, str]] = []
    seen_rejected: set[tuple[str, str]] = set()
    seen_raw: set[str] = set()
    source_rows = 0

    for form in raw_words:
        if not form:
            continue
        source_rows += 1
        seen_raw.add(form)

        normalized = normalize(form)
        rule = rejection_rule(form, normalized)
        if rule is not None:
            rejected[rule] += 1
            key = (rule, form)
            if key not in seen_rejected:
                seen_rejected.add(key)
                samples.append((rule, form))
            continue
        kept[normalized].add(form)

    samples.sort()
    stats = {"source_rows": source_rows, "source_distinct_raw": len(seen_raw)}
    return kept, rejected, samples, stats


def load_kaikki_de() -> tuple[dict[str, set[str]], dict[str, int], Counter, list[tuple[str, str]]]:
    """Charge l'extrait Wiktionnaire allemand de kaikki.org, filtre par pos puis par
    rejection_rule(). Renvoie (formes brutes groupees par normalisee, volumetrie, rejets,
    echantillon de formes rejetees) -- meme contrat que load_kaikki_es() (site espagnol).

    dict[normalized -> set(formes brutes)], pas un simple set(normalized) : necessaire pour
    detecter les COLLISIONS de normalisation (docs/03 §6-equivalent, meme discipline que
    scripts/import_fr.py/import_es.py) -- une collision (ex. Eszett vs "ss" deja vu sur
    enz/hippler, ou deux graphies kaikki distinctes convergeant vers la meme forme normalisee)
    est une fusion de provenance attendue, jamais silencieuse.
    """
    kept: dict[str, set[str]] = defaultdict(set)
    rejected: Counter = Counter()
    seen_rejected: set[tuple[str, str]] = set()
    samples: list[tuple[str, str]] = []
    stats = {
        "kaikki_source_lines": 0,
        "kaikki_non_german_skipped": 0,
        "kaikki_pos_excluded": 0,
        "kaikki_candidates_after_pos_filter": 0,
    }

    with gzip.open(KAIKKI_DE_PATH, "rt", encoding="utf-8") as handle:
        for line in handle:
            line = line.strip()
            if not line:
                continue
            stats["kaikki_source_lines"] += 1
            entry = json.loads(line)
            if entry.get("lang_code") != "de":
                stats["kaikki_non_german_skipped"] += 1
                continue
            pos = entry.get("pos")
            if pos in KAIKKI_POS_EXCLUDED:
                stats["kaikki_pos_excluded"] += 1
                continue
            word = entry.get("word")
            if not word:
                continue
            stats["kaikki_candidates_after_pos_filter"] += 1
            normalized = normalize(word)
            rule = rejection_rule(word, normalized)
            if rule is not None:
                rejected[rule] += 1
                key = (rule, word)
                if key not in seen_rejected:
                    seen_rejected.add(key)
                    samples.append((rule, word))
                continue
            kept[normalized].add(word)

    stats["kaikki_distinct_normalized_retained"] = len(kept)
    samples.sort()
    return kept, stats, rejected, samples


def build_terms(
    enz: dict[str, set[str]], hippler: dict[str, set[str]], kaikki_de: dict[str, set[str]]
) -> tuple[dict[str, dict], dict[str, list[str]], dict[str, int]]:
    """Fusionne les trois sources deja normalisees/filtrees : chaque forme normalisee retenue
    par AU MOINS UNE devient un terme, avec is_enz/is_hippler/is_german poses independamment
    les uns des autres -- jamais un OR qui efface la provenance individuelle.

    Ordre de fusion (D-DE-029) : enz/hippler (inchange depuis D-DE-006) PUIS kaikki_de --
    is_admitted reste calcule UNIQUEMENT depuis is_enz/is_hippler, jamais is_german : une
    forme kaikki_de qui cree une NOUVELLE ligne (absente d'enz/hippler) est donc is_admitted=0
    par construction, troisieme statut du modele (CLAUDE.md).

    Renvoie aussi merged_forms (normalized -> TOUTES les formes brutes des trois sources
    confondues) pour le rapport de collisions, et kaikki_effects (nouvelles lignes creees par
    kaikki_de, jamais vues par enz/hippler) -- meme convention que scripts/import_es.py.
    """
    terms: dict[str, dict] = {}
    merged_forms: dict[str, set[str]] = defaultdict(set)

    for normalized, forms in enz.items():
        terms[normalized] = {"is_enz": 1, "is_hippler": 0, "is_german": 0}
        merged_forms[normalized] |= forms

    for normalized, forms in hippler.items():
        entry = terms.get(normalized)
        if entry is None:
            terms[normalized] = {"is_enz": 0, "is_hippler": 1, "is_german": 0}
        else:
            entry["is_hippler"] = 1
        merged_forms[normalized] |= forms

    kaikki_new_rows = 0
    for normalized, forms in kaikki_de.items():
        entry = terms.get(normalized)
        if entry is None:
            terms[normalized] = {"is_enz": 0, "is_hippler": 0, "is_german": 1}
            kaikki_new_rows += 1
        else:
            entry["is_german"] = 1
        merged_forms[normalized] |= forms

    for entry in terms.values():
        entry["is_admitted"] = 1 if (entry["is_enz"] or entry["is_hippler"]) else 0

    kaikki_effects = {"kaikki_de_rows_absent_from_enz_hippler": kaikki_new_rows}
    return terms, merged_forms, kaikki_effects


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
                entry["is_enz"],
                entry["is_hippler"],
                entry["is_german"],
                entry["is_admitted"],
                score(normalized),
                len(normalized),
                signature(normalized),
                reverse(normalized),
            )
            for index, (normalized, entry) in enumerate(sorted(terms.items()), start=1)
        )
        connection.executemany(
            "INSERT INTO terms (id, display_term, normalized, is_enz, is_hippler, is_german,"
            " is_admitted, score, length, signature, reversed)"
            " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
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
        help="analyse les sources et affiche le resume, sans rien ecrire",
    )
    args = parser.parse_args()

    required = [ENZ_PATH, HIPPLER_PATH, KAIKKI_DE_PATH, SCHEMA_PATH]
    for path in required:
        if not path.exists():
            raise SystemExit("source manquante : %s" % path)

    enz, enz_rejected, enz_samples, enz_stats = load_source(read_enz_raw_words())
    hippler, hippler_rejected, hippler_samples, hippler_stats = load_source(read_hippler_raw_words())
    kaikki_de, kaikki_stats, kaikki_rejected, kaikki_samples = load_kaikki_de()

    terms, merged_forms, kaikki_effects = build_terms(enz, hippler, kaikki_de)

    collisions = {
        normalized: sorted(forms)
        for normalized, forms in merged_forms.items()
        if len(forms) > 1
    }

    status = Counter()
    for entry in terms.values():
        if entry["is_enz"] and entry["is_hippler"]:
            status["enz_and_hippler"] += 1
        elif entry["is_enz"]:
            status["enz_only"] += 1
        elif entry["is_hippler"]:
            status["hippler_only"] += 1
        else:
            status["kaikki_de_only"] += 1

    summary = {
        "enz_source_rows": enz_stats["source_rows"],
        "enz_distinct_raw": enz_stats["source_distinct_raw"],
        "enz_distinct_normalized": len(enz),
        "enz_rejected": {
            "total": sum(enz_rejected.values()),
            "by_rule": dict(sorted(enz_rejected.items())),
        },
        "hippler_source_rows": hippler_stats["source_rows"],
        "hippler_distinct_raw": hippler_stats["source_distinct_raw"],
        "hippler_distinct_normalized": len(hippler),
        "hippler_rejected": {
            "total": sum(hippler_rejected.values()),
            "by_rule": dict(sorted(hippler_rejected.items())),
        },
        "kaikki_de": dict(sorted(kaikki_stats.items())),
        "kaikki_de_rejected": {
            "total": sum(kaikki_rejected.values()),
            "by_rule": dict(sorted(kaikki_rejected.items())),
        },
        "kaikki_de_rows_absent_from_enz_hippler": kaikki_effects["kaikki_de_rows_absent_from_enz_hippler"],
        "normalization_collisions": len(collisions),
        "terms_total": len(terms),
        "enz_only": status["enz_only"],
        "hippler_only": status["hippler_only"],
        "enz_and_hippler": status["enz_and_hippler"],
        "kaikki_de_only": status["kaikki_de_only"],
        "admitted_total": sum(1 for e in terms.values() if e["is_admitted"] == 1),
        "german_total": sum(1 for e in terms.values() if e["is_german"] == 1),
        "max_term_length": MAX_LENGTH,
        "min_term_length": MIN_LENGTH,
    }

    if args.dry_run:
        print(json.dumps(summary, ensure_ascii=False, indent=2, sort_keys=True))
        print("\n--dry-run : aucune ecriture", file=sys.stderr)
        return 0

    metadata = {
        "language": "de",
        "schema": "terms v3-de (D-DE-006 : is_enz/is_hippler ; D-DE-029 : is_german/kaikki_de, is_admitted derivee de is_enz/is_hippler uniquement -- pas de pos/gender/word_senses/verb_forms)",
        "source_enz_sha256": sha256_of(ENZ_PATH),
        "source_enz_commit": ENZ_COMMIT,
        "source_hippler_sha256": sha256_of(HIPPLER_PATH),
        "source_kaikki_de_sha256": sha256_of(KAIKKI_DE_PATH),
        "terms_total": str(len(terms)),
    }
    write_database(terms, metadata)

    REPORTS.mkdir(parents=True, exist_ok=True)
    write_json(REPORTS / "import-summary.json", summary)
    write_json(
        REPORTS / "source-status-counts.json",
        {
            "enz_only": status["enz_only"],
            "hippler_only": status["hippler_only"],
            "enz_and_hippler": status["enz_and_hippler"],
            "kaikki_de_only": status["kaikki_de_only"],
            "enz_total": status["enz_only"] + status["enz_and_hippler"],
            "hippler_total": status["hippler_only"] + status["enz_and_hippler"],
            "admitted_total": summary["admitted_total"],
            "german_total": summary["german_total"],
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
        REPORTS / "hippler-only-terms.csv",
        ["normalized"],
        ((normalized,) for normalized in sorted(terms) if terms[normalized]["is_hippler"] and not terms[normalized]["is_enz"]),
    )
    write_csv(
        REPORTS / "kaikki-de-not-admitted-terms.csv",
        ["normalized"],
        (
            (normalized,)
            for normalized in sorted(terms)
            if terms[normalized]["is_german"] and not terms[normalized]["is_admitted"]
        ),
    )
    write_csv(
        REPORTS / "rejected-forms.csv",
        ["source", "rule", "form"],
        (
            [("enz",) + row for row in enz_samples]
            + [("hippler",) + row for row in hippler_samples]
            + [("kaikki_de",) + row for row in kaikki_samples]
        ),
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
