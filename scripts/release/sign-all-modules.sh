#!/usr/bin/env bash
set -euo pipefail

if [ "${1:-}" = "" ] || [ "${2:-}" = "" ] || [ "${3:-}" = "" ]; then
  echo "Usage: $0 <modules-root> <private-key.pem> <key-id>" >&2
  echo "Example: $0 catmin/modules/admin release/keys/catmin-official-main-2026-private.pem catmin-official-main-2026" >&2
  exit 1
fi

MODULES_ROOT="$1"
PRIVATE_KEY="$2"
KEY_ID="$3"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
CHECKSUM_SCRIPT="${ROOT_DIR}/catmin/scripts/release/generate-module-checksums.php"
SIGN_SCRIPT="${ROOT_DIR}/catmin/scripts/release/generate-module-signature.php"

if [ ! -d "${MODULES_ROOT}" ]; then
  echo "Modules root not found: ${MODULES_ROOT}" >&2
  exit 1
fi
if [ ! -f "${PRIVATE_KEY}" ]; then
  echo "Private key not found: ${PRIVATE_KEY}" >&2
  exit 1
fi
if [ ! -f "${CHECKSUM_SCRIPT}" ] || [ ! -f "${SIGN_SCRIPT}" ]; then
  echo "Release scripts not found in catmin/scripts/release" >&2
  exit 1
fi

count=0
while IFS= read -r -d '' module_dir; do
  if [ ! -f "${module_dir}/manifest.json" ] && [ ! -f "${module_dir}/module.json" ]; then
    continue
  fi

  php "${CHECKSUM_SCRIPT}" "${module_dir}" "${module_dir}/checksums.json"
  php "${SIGN_SCRIPT}" "${module_dir}/checksums.json" "${PRIVATE_KEY}" "${KEY_ID}" "${module_dir}/signature.json"
  count=$((count + 1))
done < <(find "${MODULES_ROOT}" -mindepth 1 -maxdepth 1 -type d -print0 | sort -z)

echo "Signed modules: ${count}"
