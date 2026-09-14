#!/usr/bin/env bash
# Build a release bundle for shared hosting (no SSH on the host).
#
#   tools/build-release.sh            → release/skillos-<date>-<sha>.zip
#
# The zip contains:
#   skillos/       the app (committed files only, vendor/ with --no-dev, no tests/course media)
#   public_html/   contents of public/ with a front controller that finds ../skillos
#
# Upload the zip with the host's file manager to your home directory and extract:
# files in the zip overwrite the previous release; .env, database/, storage/ on the
# host are NOT in the zip and survive. Then open /_ops/migrate, /_ops/import and
# /_ops/optimize (see README).
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "working tree has uncommitted changes — commit first (the bundle is built from HEAD)" >&2
    exit 1
fi

sha="$(git rev-parse --short HEAD)"
stamp="$(date +%Y%m%d-%H%M)"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

echo "→ exporting HEAD ($sha)"
mkdir -p "$work/skillos"
git archive HEAD | tar -x -C "$work/skillos"

echo "→ composer install --no-dev"
(cd "$work/skillos" && composer install --no-dev --optimize-autoloader --no-interaction --quiet)

echo "→ trimming"
(cd "$work/skillos" && rm -rf tests node_modules .github .ai deploy docs/*.docx 2>/dev/null; rm -f phpunit.xml .env.example package.json package-lock.json vite.config.js tailwind.config.js postcss.config.js .editorconfig .gitattributes .gitignore)
cp deploy/app.htaccess "$work/skillos/.htaccess"
cp .env.production.example "$work/skillos/.env.production.example"

echo "→ public_html"
mkdir -p "$work/public_html"
cp -R "$work/skillos/public/." "$work/public_html/"
cp deploy/index.php "$work/public_html/index.php"

mkdir -p release
out="release/skillos-$stamp-$sha.zip"
(cd "$work" && zip -qr "$root/$out" skillos public_html)
echo "✓ $out ($(du -h "$out" | cut -f1))"
echo
echo "Next: upload + extract in the host's file manager, then visit"
echo "  https://YOUR-DOMAIN/_ops/status?token=OPS_TOKEN"
echo "  https://YOUR-DOMAIN/_ops/migrate?token=OPS_TOKEN"
echo "  https://YOUR-DOMAIN/_ops/import?token=OPS_TOKEN"
echo "  https://YOUR-DOMAIN/_ops/optimize?token=OPS_TOKEN"
