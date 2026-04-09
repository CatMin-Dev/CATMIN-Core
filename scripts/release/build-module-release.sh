#!/usr/bin/env bash
set -euo pipefail

if [ "${1:-}" = "" ]; then
  echo "Usage: bash scripts/release/build-module-release.sh <module-source-dir> [release-root-dir]"
  echo "Env: MODULE_SIGNING_KEY=/path/private.pem MODULE_SIGNING_KEY_ID=catmin-key-001"
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SOURCE_DIR="$(cd "$(dirname "$1")" && pwd)/$(basename "$1")"
RELEASE_ROOT="${2:-${ROOT_DIR}/../release/modules}"

if [ ! -d "${SOURCE_DIR}" ]; then
  echo "Source module directory not found: ${SOURCE_DIR}" >&2
  exit 1
fi

if [ ! -f "${SOURCE_DIR}/manifest.json" ]; then
  echo "manifest.json missing in source module" >&2
  exit 1
fi

MODULE_JSON="$(php -r '
$m = json_decode((string) file_get_contents($argv[1]), true);
if (!is_array($m)) { exit(2); }
$slug = strtolower(trim((string)($m["slug"] ?? "")));
$version = trim((string)($m["version"] ?? ""));
if ($slug === "" || $version === "") { exit(3); }
echo $slug . "|" . $version;
' "${SOURCE_DIR}/manifest.json")" || {
  echo "Invalid manifest.json (missing slug/version)" >&2
  exit 1
}
MODULE_SLUG="${MODULE_JSON%%|*}"
MODULE_VERSION="${MODULE_JSON##*|}"

RELEASE_DIR="${RELEASE_ROOT}/${MODULE_SLUG}-${MODULE_VERSION}"
STAGE_ROOT="${RELEASE_DIR}/_stage"
STAGE_MODULE="${STAGE_ROOT}/${MODULE_SLUG}"
TMP_EXTRACT="${RELEASE_DIR}/_tmp_extract"
MODULE_ZIP="${RELEASE_DIR}/module.zip"
UNSIGNED_ZIP="${RELEASE_DIR}/module-unsigned.zip"

mkdir -p "${RELEASE_DIR}"
rm -rf "${STAGE_ROOT}" "${TMP_EXTRACT}" "${MODULE_ZIP}" "${UNSIGNED_ZIP}"
mkdir -p "${STAGE_MODULE}"

rsync -a \
  --delete \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude '.vscode/' \
  --exclude 'tests/' \
  --exclude '__tests__/' \
  --exclude 'tmp/' \
  --exclude 'cache/' \
  --exclude 'backups/' \
  --exclude '*.log' \
  --exclude '*.tmp' \
  --exclude '*.swp' \
  --exclude '*.bak' \
  --exclude '*.key' \
  --exclude '*.pem' \
  --exclude '*.p12' \
  --exclude '*.pfx' \
  --exclude 'checksums.json' \
  --exclude 'signature.json' \
  --exclude 'release-metadata.json' \
  "${SOURCE_DIR}/" "${STAGE_MODULE}/"

php -r '
$root = realpath($argv[1]);
if (!is_string($root) || $root === "") { fwrite(STDERR, "root not found\n"); exit(1); }
require_once $root . "/bootstrap.php";
require_once CATMIN_CORE . "/module-manifest-standard.php";
$manifestPath = $argv[2] . "/manifest.json";
$decoded = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($decoded)) { fwrite(STDERR, "manifest invalid\n"); exit(2); }
$std = new CoreModuleManifestStandard();
$normalized = $std->normalize($decoded);
$valid = $std->validate($normalized);
if (!(bool)($valid["valid"] ?? false)) {
  fwrite(STDERR, "manifest validation failed: " . implode(" | ", (array)($valid["errors"] ?? [])) . "\n");
  exit(3);
}
' "${ROOT_DIR}" "${STAGE_MODULE}"

(
  cd "${RELEASE_DIR}"
  zip -rq "${UNSIGNED_ZIP}" "_stage/${MODULE_SLUG}"
)

mkdir -p "${TMP_EXTRACT}"
unzip -q "${UNSIGNED_ZIP}" -d "${TMP_EXTRACT}"

FINAL_MODULE_DIR="${TMP_EXTRACT}/_stage/${MODULE_SLUG}"
if [ ! -d "${FINAL_MODULE_DIR}" ]; then
  echo "Unable to resolve module path inside unsigned package" >&2
  exit 1
fi

php "${ROOT_DIR}/scripts/release/generate-module-checksums.php" "${FINAL_MODULE_DIR}" "${FINAL_MODULE_DIR}/checksums.json"

if [ -n "${MODULE_SIGNING_KEY:-}" ]; then
  if [ ! -f "${MODULE_SIGNING_KEY}" ]; then
    echo "MODULE_SIGNING_KEY file not found: ${MODULE_SIGNING_KEY}" >&2
    exit 1
  fi
  if [ -z "${MODULE_SIGNING_KEY_ID:-}" ]; then
    echo "MODULE_SIGNING_KEY_ID is required when MODULE_SIGNING_KEY is set" >&2
    exit 1
  fi
  php "${ROOT_DIR}/scripts/release/generate-module-signature.php" \
    "${FINAL_MODULE_DIR}/checksums.json" \
    "${MODULE_SIGNING_KEY}" \
    "${MODULE_SIGNING_KEY_ID}" \
    "${FINAL_MODULE_DIR}/signature.json"
fi

cp -a "${FINAL_MODULE_DIR}" "${STAGE_ROOT}/"

(
  cd "${STAGE_ROOT}"
  zip -rq "${MODULE_ZIP}" "${MODULE_SLUG}"
)

cp "${STAGE_MODULE}/manifest.json" "${RELEASE_DIR}/manifest.json"
cp "${STAGE_MODULE}/checksums.json" "${RELEASE_DIR}/checksums.json"
if [ -f "${STAGE_MODULE}/signature.json" ]; then
  cp "${STAGE_MODULE}/signature.json" "${RELEASE_DIR}/signature.json"
fi

php "${ROOT_DIR}/scripts/release/generate-module-release-metadata.php" "${RELEASE_DIR}"

VERIFY_ARGS=("${ROOT_DIR}/scripts/release/verify-module-release.php" "${MODULE_ZIP}")
if [ -f "${RELEASE_DIR}/signature.json" ]; then
  VERIFY_PUBLIC_KEY=""
  if [ -n "${MODULE_SIGNING_PUBLIC_KEY:-}" ] && [ -f "${MODULE_SIGNING_PUBLIC_KEY}" ]; then
    VERIFY_PUBLIC_KEY="${MODULE_SIGNING_PUBLIC_KEY}"
  elif [ -n "${MODULE_SIGNING_KEY:-}" ] && [ -f "${MODULE_SIGNING_KEY}" ]; then
    VERIFY_PUBLIC_KEY="${RELEASE_DIR}/_tmp_public.pem"
    php -r '
$privatePem = (string) file_get_contents($argv[1]);
$private = openssl_pkey_get_private($privatePem);
if ($private === false) {
    fwrite(STDERR, "Unable to read private key\n");
    exit(1);
}
$details = openssl_pkey_get_details($private);
openssl_pkey_free($private);
$pub = is_array($details) ? (string) ($details["key"] ?? "") : "";
if ($pub === "") {
    fwrite(STDERR, "Unable to extract public key\n");
    exit(1);
}
file_put_contents($argv[2], $pub);
' "${MODULE_SIGNING_KEY}" "${VERIFY_PUBLIC_KEY}"
  fi
  VERIFY_ARGS+=("--require-signature")
  if [ -n "${VERIFY_PUBLIC_KEY}" ]; then
    VERIFY_ARGS+=("--public-key=${VERIFY_PUBLIC_KEY}")
  fi
fi
php "${VERIFY_ARGS[@]}"

rm -rf "${TMP_EXTRACT}" "${UNSIGNED_ZIP}" "${STAGE_ROOT}" "${RELEASE_DIR}/_tmp_public.pem"

echo "Module release built: ${RELEASE_DIR}"
echo "- ${RELEASE_DIR}/module.zip"
echo "- ${RELEASE_DIR}/manifest.json"
echo "- ${RELEASE_DIR}/checksums.json"
if [ -f "${RELEASE_DIR}/signature.json" ]; then
  echo "- ${RELEASE_DIR}/signature.json"
fi
echo "- ${RELEASE_DIR}/release-metadata.json"
