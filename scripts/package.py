"""Build only distributable theme files; never include Git metadata, scripts or credentials."""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

root = Path(__file__).resolve().parents[1]
output = root / "dist" / "black-lab.zip"
output.parent.mkdir(exist_ok=True)
files = [root / name for name in ("functions.php", "style.css", "theme.json", "readme.txt")]
files += [p for p in root.glob("*.php")]
for name in ("assets", "includes", "parts"):
    files.extend(path for path in (root / name).rglob("*") if path.is_file())
for file in files:
    relative = file.relative_to(root)
    if any(part.startswith(".") for part in relative.parts) or file.suffix.lower() in (".key", ".pem", ".pfx", ".p12", ".env"):
        raise ValueError("Unexpected sensitive file in theme distribution: " + str(relative))
with ZipFile(output, "w", ZIP_DEFLATED) as archive:
    for file in sorted(set(files)):
        archive.write(file, Path("black-lab") / file.relative_to(root))
print("Built:", output)
