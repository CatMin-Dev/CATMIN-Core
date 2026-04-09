#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BUILD_ROOT="${ROOT_DIR}/../release"
VERSION="$(php -r '$j=json_decode(file_get_contents("'"${ROOT_DIR}"'/version.json"),true); echo $j["version"] ?? "0.0.0-dev.0";')"
PACKAGE_NAME="catmin-${VERSION}-standalone"
STAGE_DIR="${BUILD_ROOT}/${PACKAGE_NAME}"
ZIP_FILE="${BUILD_ROOT}/${PACKAGE_NAME}.zip"

mkdir -p "${BUILD_ROOT}"
rm -rf "${STAGE_DIR}" "${ZIP_FILE}"
mkdir -p "${STAGE_DIR}"

INCLUDE_ITEMS=(
  admin
  config
  core
  cron
  database
  db
  front
  install
  modules
  public
  storage
  logs
  cache
  sessions
  tmp
  docs
  bootstrap.php
  index.php
  .htaccess
  robots.txt
  .env.example
  README.md
  version.json
  .version_history.json
)

for item in "${INCLUDE_ITEMS[@]}"; do
  if [ -e "${ROOT_DIR}/${item}" ]; then
    rsync -a "${ROOT_DIR}/${item}" "${STAGE_DIR}/"
  fi
done

# Remove release-excluded content.
find "${STAGE_DIR}" -type d \( -name '.git' -o -name '.vscode' -o -name 'node_modules' -o -name 'tests' \) -prune -exec rm -rf {} +
find "${STAGE_DIR}" -type f \( -name '*.log' -o -name '*.map' -o -name '*.tmp' \) -delete
rm -f "${STAGE_DIR}/.env" "${STAGE_DIR}/.env.local" "${STAGE_DIR}/.env.production" || true
rm -f "${STAGE_DIR}/storage/logs/"*.log || true
rm -f "${STAGE_DIR}/logs/"*.log || true
rm -rf "${STAGE_DIR}/storage/install" || true
rm -rf "${STAGE_DIR}/storage/updates/releases" || true
rm -rf "${STAGE_DIR}/storage/updates/reports" || true
rm -f "${STAGE_DIR}/db/database.sqlite" || true
rm -f "${STAGE_DIR}/database.sqlite" || true

# Cleanup previously generated release artifacts in stage.
find "${STAGE_DIR}" -type f \( -name '*-standalone.zip' -o -name '*-manifest.json' -o -name 'release-report.json' \) -delete

# Ensure writable runtime directories exist but are clean.
mkdir -p \
  "${STAGE_DIR}/storage/install/sessions" \
  "${STAGE_DIR}/storage/cache" \
  "${STAGE_DIR}/storage/logs" \
  "${STAGE_DIR}/storage/backups" \
  "${STAGE_DIR}/storage/updates/releases" \
  "${STAGE_DIR}/storage/updates/reports" \
  "${STAGE_DIR}/db" \
  "${STAGE_DIR}/cache" \
  "${STAGE_DIR}/logs" \
  "${STAGE_DIR}/sessions" \
  "${STAGE_DIR}/tmp"

for d in \
  "${STAGE_DIR}/storage/install/sessions" \
  "${STAGE_DIR}/storage/cache" \
  "${STAGE_DIR}/storage/logs" \
  "${STAGE_DIR}/storage/backups" \
  "${STAGE_DIR}/storage/updates/releases" \
  "${STAGE_DIR}/storage/updates/reports" \
  "${STAGE_DIR}/db" \
  "${STAGE_DIR}/cache" \
  "${STAGE_DIR}/logs" \
  "${STAGE_DIR}/sessions" \
  "${STAGE_DIR}/tmp"; do
  touch "${d}/.gitkeep"
done

(
  cd "${BUILD_ROOT}"
  zip -rq "${ZIP_FILE}" "${PACKAGE_NAME}"
)

# Generate checksums/signature metadata (signature optional with RELEASE_SIGNING_KEY path).
if [ -f "${ROOT_DIR}/scripts/release/generate-release-metadata.php" ]; then
  if [ -n "${RELEASE_SIGNING_KEY:-}" ] && [ -f "${RELEASE_SIGNING_KEY}" ]; then
    php "${ROOT_DIR}/scripts/release/generate-release-metadata.php" "${ZIP_FILE}" "${RELEASE_SIGNING_KEY}"
  else
    php "${ROOT_DIR}/scripts/release/generate-release-metadata.php" "${ZIP_FILE}"
  fi
fi

# Build lightweight manifest + report.
php -r '
$zipFile = $argv[1];
$packageName = $argv[2];
$reportFile = $argv[3];
$versionFile = $argv[4];
$version = "0.0.0-dev.0";
if (is_file($versionFile)) {
  $decoded = json_decode((string) file_get_contents($versionFile), true);
  if (is_array($decoded) && isset($decoded["version"])) {
    $version = (string) $decoded["version"];
  }
}
$zipSize = is_file($zipFile) ? filesize($zipFile) : 0;
$lines = [];
exec("unzip -Z1 " . escapeshellarg($zipFile), $lines);
$manifest = [
  "name" => $packageName,
  "version" => $version,
  "built_at" => date("c"),
  "zip_file" => basename($zipFile),
  "zip_size_bytes" => (int) $zipSize,
  "entries_count" => count($lines),
  "entries" => $lines,
];
$manifestPath = dirname($zipFile) . "/" . $packageName . "-manifest.json";
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

$report = [
  "status" => "ok",
  "package" => basename($zipFile),
  "manifest" => basename($manifestPath),
  "version" => $version,
  "entries_count" => count($lines),
  "generated_at" => date("c"),
];
file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
' "${ZIP_FILE}" "${PACKAGE_NAME}" "${BUILD_ROOT}/release-report.json" "${ROOT_DIR}/version.json"

echo "ZIP built: ${ZIP_FILE}"
