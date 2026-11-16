#!/usr/bin/env python3
"""Rebuild the Persian font catalog as small, correct WOFF2 files.

Why this exists
---------------
The first pass subset the fonts with unicode ranges and kept whatever format
the source used (ttf/otf/woff). That left ~3.9 MB on disk and still shipped
tables the card never needs. This script:

  * collects every character the project can actually display,
  * instantiates variable fonts at a static weight (one face, not 30 axes),
  * subsets to that character set with only the shaping features Arabic needs,
  * always writes WOFF2,
  * regenerates fonts/fonts.json and css/fonts-catalog.css from the result.

Run from the project root:  python tools/build-fonts.py
"""

from __future__ import annotations

import json
import os
import re
import shutil
import sys

from fontTools import subset as ftsubset
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FONTS = os.path.join(ROOT, "fonts")
CHARSET_FILE = os.path.join(ROOT, "tools", "font-charset.txt")

# --- Character set ---------------------------------------------------------
# Base repertoire: full Persian/Arabic alphabet, both digit sets, ZWNJ/ZWJ,
# bidi marks, Persian and Latin punctuation, common symbols. Everything found
# in the project's own content is added on top so nothing on the card falls
# back to a system font.
PERSIAN = "ابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهی"
ARABIC = "آأإؤئءۀةىيكﻻﻷﻹﻫ"
DIGITS = "0123456789۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩"
MARKS = "\u200c\u200d\u200e\u200f\u061f\u060c\u061b\u0640\u064b\u064c\u064d\u064e\u064f\u0650\u0651\u0652\u0670"
LATIN = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
PUNCT = ".,!?;:'\"()[]{}<>/\\|@#$%^&*_+=~`-–—…«»‹›؛،؟٪٫٬"
SYMBOLS = "©®™°€£¥₽←→↑↓↔★☆✓✔✕✖•·×÷±≤≥∞≈"

TEXT_FILES = [
    "data/card.json",
    "data/content-site.json",
    "index.html",
    "js/card.js",
    "admin/card.php",
    "README.md",
]

# Shaping features Arabic/Persian needs; dropping everything else saves bytes
# without breaking joined letters.
KEEP_FEATURES = [
    "ccmp", "locl", "rlig", "rclt", "calt", "liga", "clig", "curs",
    "kern", "mark", "mkmk", "init", "medi", "fina", "isol", "fin2",
    "fin3", "med2", "ss01", "ss02",
]

DROP_TABLES = [
    "DSIG", "EBDT", "EBLC", "EBSC", "SVG ", "MATH", "BASE", "JSTF",
    "vhea", "vmtx", "VVAR", "MVAR", "STAT", "avar", "cvar", "fvar", "gvar",
]

WEIGHT_WORDS = [
    ("thin", 100), ("hairline", 100), ("ultralight", 200), ("extralight", 200),
    ("light", 300), ("regular", 400), ("normal", 400), ("book", 400),
    ("medium", 500), ("semibold", 600), ("demibold", 600), ("semi", 600),
    ("demi", 600), ("extrabold", 800), ("ultrabold", 800), ("heavy", 900),
    ("black", 900), ("extrablack", 950), ("bold", 700),
]

# A few folders carry many weights with names the generic matcher can't
# resolve reliably (Dana ships "fat", "hairline", "heavy", ...). Pin them.
FORCED = {
    "dana": {400: "Dana-Regular.woff2", 700: "Dana-Bold.woff2"},
}


def existing_names() -> dict[str, str]:
    path = os.path.join(FONTS, "fonts.json")
    if not os.path.isfile(path):
        return {}
    try:
        with open(path, "r", encoding="utf-8") as fh:
            data = json.load(fh)
        return {str(f.get("slug")): str(f.get("name")) for f in data if f.get("slug") and f.get("name")}
    except Exception:
        return {}


def build_charset() -> str:
    chars = set(PERSIAN + ARABIC + DIGITS + MARKS + LATIN + PUNCT + SYMBOLS)
    chars.update("\u200b\u200c\u200d\u200e\u200f")  # zero-width + bidi
    chars.update(" \t\n")
    for rel in TEXT_FILES:
        path = os.path.join(ROOT, rel)
        if not os.path.isfile(path):
            continue
        try:
            with open(path, "r", encoding="utf-8") as fh:
                chars.update(fh.read())
        except UnicodeDecodeError:
            continue
    # Drop control characters, keep tab/newline only.
    cleaned = sorted(c for c in chars if ord(c) >= 0x20 or c in "\t\n")
    text = "".join(cleaned)
    os.makedirs(os.path.dirname(CHARSET_FILE), exist_ok=True)
    with open(CHARSET_FILE, "w", encoding="utf-8", newline="") as fh:
        fh.write(text)
    return text


def weight_of(filename: str, fallback: int = 400) -> int:
    stem = os.path.splitext(os.path.basename(filename))[0].lower()
    stem = re.sub(r"[^a-z0-9]", "", stem)
    for word, weight in WEIGHT_WORDS:
        if word in stem:
            return weight
    return fallback


def is_variable(path: str) -> bool:
    try:
        with TTFont(path, fontNumber=0, lazy=True) as font:
            return "fvar" in font
    except Exception:
        return False


def instance(path: str, weight: int, tmpdir: str) -> str:
    """Return a path to a static, single-weight copy of `path`."""
    if not is_variable(path):
        return path
    out = os.path.join(tmpdir, f"{os.path.basename(path)}.{weight}.ttf")
    font = TTFont(path, fontNumber=0)
    if "fvar" in font:
        axes = {a.axisTag for a in font["fvar"].axes}
        if "wght" in axes:
            instancer.instantiateVariableFont(font, {"wght": weight}, inplace=True)
    font.save(out)
    font.close()
    return out


def subset(src: str, dest: str, text: str) -> bool:
    try:
        font = TTFont(src, fontNumber=0)
        options = ftsubset.Options()
        options.flavor = "woff2"
        options.layout_features = KEEP_FEATURES
        options.name_IDs = ["*"]
        options.name_languages = ["*"]
        options.notdef_outline = True
        options.recalc_bounds = True
        options.recalc_timestamp = False
        options.canonical_order = True
        options.drop_tables += DROP_TABLES
        subsetter = ftsubset.Subsetter(options=options)
        subsetter.populate(text=text)
        subsetter.subset(font)
        font.flavor = "woff2"
        font.save(dest)
        font.close()
        return True
    except Exception as exc:  # keep the build going for one broken source
        print(f"  ! failed {os.path.basename(src)}: {exc}")
        return False


def slugify(name: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", name.lower()).strip("-")
    return slug or "font"


def main() -> int:
    charset = build_charset()
    print(f"charset: {len(charset)} characters")
    if not os.path.isdir(FONTS):
        print("fonts/ not found")
        return 1

    tmpdir = os.path.join(os.environ.get("TEMP", "/tmp"), "card-font-build")
    os.makedirs(tmpdir, exist_ok=True)
    known_names = existing_names()

    families = []
    for entry in sorted(os.listdir(FONTS)):
        family_dir = os.path.join(FONTS, entry)
        if not os.path.isdir(family_dir) or entry.startswith("_"):
            continue
        sources = [
            os.path.join(family_dir, f)
            for f in sorted(os.listdir(family_dir))
            if f.lower().endswith((".woff2", ".woff", ".ttf", ".otf"))
        ]
        if not sources:
            continue
        # Prefer real static weights over a duplicate variable file.
        sources = [s for s in sources if not is_variable(s)] or sources
        by_weight: dict[int, str] = {}
        for src in sources:
            w = weight_of(src)
            if w in by_weight:
                continue
            by_weight[w] = src

        wanted = [400]
        if entry in FORCED:
            by_weight = {}
            for weight, filename in FORCED[entry].items():
                candidate = os.path.join(family_dir, filename)
                if os.path.isfile(candidate):
                    by_weight[weight] = candidate
            wanted = [w for w in (400, 700) if w in by_weight]
        elif 700 in by_weight and len(by_weight) > 1:
            # A real bold face when the family ships one; browser-synthesised
            # bold otherwise (cheaper, and visually fine for display fonts).
            wanted = [400, 700]
        elif len(by_weight) == 1 and 400 not in by_weight:
            wanted = [list(by_weight)[0]]

        faces = []
        for weight in wanted:
            src = by_weight.get(weight)
            if not src:
                continue
            static = instance(src, weight, tmpdir)
            out_name = re.sub(r"\.(ttf|otf|woff2?)$", "", os.path.basename(src), flags=re.I)
            out_name = f"{out_name}-{weight}.woff2"
            dest = os.path.join(family_dir, out_name)
            if subset(static, dest, charset):
                faces.append({
                    "file": f"../fonts/{entry}/{out_name}",
                    "weight": weight,
                    "style": "normal",
                    "format": "woff2",
                })
            if static != src and os.path.exists(static):
                os.remove(static)

        if not faces:
            continue
        # Remove every file the new catalog does not reference.
        keep = {os.path.basename(f["file"]) for f in faces}
        for old in os.listdir(family_dir):
            if old not in keep and old.lower().endswith((".woff2", ".woff", ".ttf", ".otf")):
                os.remove(os.path.join(family_dir, old))

        display = entry
        families.append({"name": display, "slug": entry, "faces": faces})
        sizes = ", ".join(
            f"{f['weight']}:{os.path.getsize(os.path.join(family_dir, os.path.basename(f['file']))) // 1024}KB"
            for f in faces
        )
        print(f"  {entry}: {sizes}")

    # Pretty display names: keep the existing catalog names where possible,
    # otherwise derive them from the folder slug.
    for fam in families:
        if fam["slug"] == "dana":
            fam["name"] = "Dana"
        else:
            fam["name"] = known_names.get(fam["slug"]) or fam["slug"].replace("-", " ").title()

    families.sort(key=lambda f: (f["slug"] != "dana", f["name"].lower()))

    with open(os.path.join(FONTS, "fonts.json"), "w", encoding="utf-8") as fh:
        json.dump(families, fh, ensure_ascii=False, indent=2)

    css = ["/* Auto-generated by tools/build-fonts.py. Do not edit by hand. */", ""]
    for fam in families:
        for face in fam["faces"]:
            css.append("@font-face{")
            css.append(f"  font-family:'{fam['name']}';")
            css.append(f"  src:url('{face['file']}') format('woff2');")
            css.append(f"  font-weight:{face['weight']};")
            css.append("  font-style:normal;")
            css.append("  font-display:swap;")
            css.append("}")
            css.append("")
    with open(os.path.join(ROOT, "css", "fonts-catalog.css"), "w", encoding="utf-8") as fh:
        fh.write("\n".join(css))

    total = 0
    for dirpath, _, filenames in os.walk(FONTS):
        for name in filenames:
            total += os.path.getsize(os.path.join(dirpath, name))
    print(f"families: {len(families)}")
    print(f"total: {total / 1024 / 1024:.2f} MB")
    return 0


if __name__ == "__main__":
    sys.exit(main())
