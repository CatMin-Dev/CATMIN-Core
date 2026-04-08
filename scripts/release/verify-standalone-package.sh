#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage: $0 /path/to/catmin-standalone.zip"
  exit 1
fi

ZIP_FILE="$1"
if [ ! -f "$ZIP_FILE" ]; then
  echo "ZIP not found: $ZIP_FILE"
  exit 1
fi

ZIP_ENTRIES="$(unzip -Z1 "$ZIP_FILE")"

required=(
  "admin/"
  "config/"
  "core/"
  "install/"
  "public/"
  "modules/"
  "storage/"
  "database/"
  "docs/"
  "bootstrap.php"
  "index.php"
  ".htaccess"
  "robots.txt"
  ".env.example"
  "README.md"
  "version.json"
  ".version_history.json"
)

for path in "${required[@]}"; do
  if ! rg -q "^[^/]+/${path}" <<<"$ZIP_ENTRIES"; then
    echo "MISSING: $path"
    exit 1
  fi
done

if rg -q '/\.env($|[^a-zA-Z0-9_.-])' <<<"$ZIP_ENTRIES"; then
  echo "FORBIDDEN FOUND: .env"
  exit 1
fi

for forbidden in ".git/" ".github/" ".vscode/" "node_modules/" "tests/"; do
  if rg -q "/${forbidden}" <<<"$ZIP_ENTRIES"; then
    echo "FORBIDDEN FOUND: $forbidden"
    exit 1
  fi
done

for forbiddenPath in \
  '/storage/install/installed.lock$' \
  '/storage/install/recovery-codes.json$' \
  '/storage/install/reports/' \
  '/storage/updates/releases/.*-standalone\.zip$' \
  '/db/database.sqlite$'; do
  if rg -q "${forbiddenPath}" <<<"$ZIP_ENTRIES"; then
    echo "FORBIDDEN FOUND: ${forbiddenPath}"
    exit 1
  fi
done

echo "Package verification: OK"
