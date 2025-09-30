import os
from pathlib import Path

# 1) Point to the folder containing the images.
# Use a raw string r"..." or double backslashes to avoid escape-sequence issues.
FOLDER = Path(r"D:\All UIU Materials\11th Trimester\SE Lab\Project\direct-edge\include\All Images\Single-Produc-Images")

# 2) Mapping: original filename -> new base name
mapping = {
    "istockphoto-510618777-612x612.jpg": "Tomato",
    "istockphoto-2234472958-612x612.jpg": "Pasta shells",
    "istockphoto-2182314369-612x612.jpg": "Coconut",
    "istockphoto-2226910903-612x612.jpg": "Fish (whole, frozen)",
    "istockphoto-1282866808-612x612.jpg": "Whole chicken",
    "istockphoto-1346380707-612x612.jpg": "Potatoes",
    "istockphoto-2233520596-612x612.jpg": "Buckwheat (in pouch)",
    "istockphoto-695597866-612x612.jpg": "Bread assortment",
    "istockphoto-2195974298-612x612.jpg": "Red onions",
    "istockphoto-2204214171-612x612.jpg": "Garlic bulb",
    "istockphoto-1085723794-612x612.jpg": "Strawberries",
    "istockphoto-1198016565-612x612.jpg": "Red bell pepper",
    "istockphoto-510015094-612x612.jpg": "Avocado",
    "istockphoto-1036777904-612x612.jpg": "Cauliflower",
    "istockphoto-2218641198-612x612.jpg": "Chickpeas in jar",
    "istockphoto-2233520721-612x612.jpg": "Red lentils in pouch",
}

def safe_filename(name: str) -> str:
    bad = {'/': '-', '\\': '-', ':': '-', '*': '-', '?': '', '"': "'", '<': '(', '>': ')', '|': '-',}
    for k, v in bad.items():
        name = name.replace(k, v)
    return name.strip()

def unique_path(base: Path) -> Path:
    if not base.exists():
        return base
    stem, suffix = base.stem, base.suffix
    n = 1
    while True:
        candidate = base.with_name(f"{stem} ({n}){suffix}")
        if not candidate.exists():
            return candidate
        n += 1

def main():
    print(f"Working folder: {FOLDER}")
    if not FOLDER.exists():
        raise FileNotFoundError(f"Folder not found: {FOLDER}")

    # List files present to help debug mismatches
    present = {p.name for p in FOLDER.iterdir() if p.is_file()}
    print(f"Found {len(present)} files in folder.")

    renamed, missing, skipped = [], [], []

    for orig, new_base in mapping.items():
        src = FOLDER / orig
        if not src.exists():
            missing.append(orig)
            continue

        ext = src.suffix or ".jpg"
        target_name = safe_filename(new_base) + ext.lower()
        dst = unique_path(FOLDER / target_name)

        try:
            os.rename(src, dst)
            renamed.append((orig, dst.name))
        except Exception as e:
            skipped.append((orig, str(e)))

    print("\nRenamed:")
    for o, n in renamed:
        print(f"  {o} -> {n}")

    if missing:
        print("\nMissing (not found in folder):")
        for m in missing:
            print(f"  {m}")

    if skipped:
        print("\nSkipped (errors):")
        for o, msg in skipped:
            print(f"  {o}: {msg}")

if __name__ == "__main__":
    main()
