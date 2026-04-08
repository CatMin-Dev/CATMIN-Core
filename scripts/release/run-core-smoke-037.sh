#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BASE_URL="${1:-https://catmin.local}"
ADMIN_PATH="$(php -r 'require "'"${ROOT_DIR}"'/bootstrap.php"; echo trim((string)config("security.admin_path","admin"),"/");')"
[ -n "$ADMIN_PATH" ] || ADMIN_PATH=admin

run_curl() {
  local name="$1"; shift
  local code
  code=$(curl -k -s -o /dev/null -w '%{http_code}' "$@")
  printf '%s=%s\n' "$name" "$code"
}

echo "base_url=$BASE_URL"
echo "admin_path=$ADMIN_PATH"
run_curl front "$BASE_URL/"
run_curl admin_login "$BASE_URL/$ADMIN_PATH/login"
run_curl admin_legacy "$BASE_URL/admin/login"
run_curl install "$BASE_URL/install/"
run_curl core_forbidden "$BASE_URL/core/"
run_curl storage_forbidden "$BASE_URL/storage/"
run_curl database_forbidden "$BASE_URL/database/"
