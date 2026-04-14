#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
MODULE_DIR="${1:-}"
RELEASE_ROOT="${ROOT_DIR}/../release/modules"

if [ "${MODULE_DIR}" = "" ]; then
  echo "Usage: $0 <module-dir>" >&2
  echo "Example: $0 ${ROOT_DIR}/modules/admin/my-module" >&2
  exit 1
fi

if [ ! -d "${MODULE_DIR}" ]; then
  echo "Module directory not found: ${MODULE_DIR}" >&2
  exit 1
fi

if [ ! -f "${MODULE_DIR}/manifest.json" ]; then
  echo "Manifest missing: ${MODULE_DIR}/manifest.json" >&2
  exit 1
fi

MODULE_LABEL="$(basename "${MODULE_DIR}")"

echo "[1/4] Lint PHP files for ${MODULE_LABEL}..."
while IFS= read -r -d '' file; do
  php -l "${file}" >/dev/null
done < <(find "${MODULE_DIR}" -type f -name '*.php' -print0)

echo "[2/4] Refresh module checksums..."
php "${ROOT_DIR}/scripts/release/generate-module-checksums.php" "${MODULE_DIR}" "${MODULE_DIR}/checksums.json"

echo "[3/4] Build private release artifact..."
bash "${ROOT_DIR}/scripts/release/build-module-release.sh" "${MODULE_DIR}" "${RELEASE_ROOT}"

echo "[4/4] Done."
echo "Release output root: ${RELEASE_ROOT}"
echo "Use verify command:"
echo "php ${ROOT_DIR}/scripts/release/verify-module-release.php <release-dir>/module.zip"
