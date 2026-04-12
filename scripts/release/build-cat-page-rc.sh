#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
MODULE_DIR="${ROOT_DIR}/modules/admin/cat-page"
RELEASE_ROOT="${ROOT_DIR}/../release/modules"

if [ ! -d "${MODULE_DIR}" ]; then
  echo "CAT-PAGE module not found: ${MODULE_DIR}" >&2
  exit 1
fi

if [ ! -f "${MODULE_DIR}/manifest.json" ]; then
  echo "CAT-PAGE manifest missing: ${MODULE_DIR}/manifest.json" >&2
  exit 1
fi

echo "[1/4] Lint CAT-PAGE PHP files..."
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
