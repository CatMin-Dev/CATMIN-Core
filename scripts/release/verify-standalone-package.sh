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

has_pattern() {
  local pattern="$1"
  if command -v rg >/dev/null 2>&1; then
    rg -q "$pattern" <<<"$ZIP_ENTRIES"
  else
    grep -Eq "$pattern" <<<"$ZIP_ENTRIES"
  fi
}

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
  if ! has_pattern "^[^/]+/${path}"; then
    echo "MISSING: $path"
    exit 1
  fi
done

if has_pattern '/\.env($|[^a-zA-Z0-9_.-])'; then
  echo "FORBIDDEN FOUND: .env"
  exit 1
fi

for forbidden in ".git/" ".github/" ".vscode/" "node_modules/" "tests/"; do
  if has_pattern "/${forbidden}"; then
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
  if has_pattern "${forbiddenPath}"; then
    echo "FORBIDDEN FOUND: ${forbiddenPath}"
    exit 1
  fi
done

echo "Package verification: OK"
