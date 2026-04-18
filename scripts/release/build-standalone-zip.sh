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

# Trust/integrity guard for module payload packaged in standalone.
# - strict mode: signing key is mandatory
# - other modes: checksums are still regenerated systematically
MODULE_TRUST_MODE="$(php -r '$cfg=require $argv[1]; echo (string)($cfg["mode"] ?? "recommended");' "${ROOT_DIR}/config/module-trust.php" 2>/dev/null || echo "recommended")"
if [ -x "${ROOT_DIR}/scripts/release/sign-all-modules.sh" ] && [ -d "${ROOT_DIR}/modules/admin" ]; then
  if [ "${MODULE_TRUST_MODE}" = "strict" ]; then
    if [ -n "${RELEASE_SIGNING_KEY:-}" ] && [ -n "${RELEASE_SIGNING_KEY_ID:-}" ] && [ -f "${RELEASE_SIGNING_KEY}" ]; then
      "${ROOT_DIR}/scripts/release/sign-all-modules.sh" "${ROOT_DIR}/modules/admin" "${RELEASE_SIGNING_KEY}" "${RELEASE_SIGNING_KEY_ID}"
    else
      for m in "${ROOT_DIR}"/modules/admin/*; do
        [ -d "${m}" ] || continue
        php -r '
        $root = $argv[1];
        $moduleDir = $argv[2];
        require_once $root . "/bootstrap.php";
        require_once CATMIN_CORE . "/module-checksum-validator.php";
        require_once CATMIN_CORE . "/module-signature-validator.php";
        $manifestPath = $moduleDir . "/manifest.json";
        $manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
        if (!is_array($manifest)) {
            fwrite(STDERR, "Invalid manifest for strict module verification: {$moduleDir}\n");
            exit(1);
        }
        $release = is_array($manifest["release"] ?? null) ? $manifest["release"] : [];
        $checksumState = (new CoreModuleChecksumValidator())->validate($moduleDir, (string) ($release["checksums"] ?? ""));
        if (!((bool) ($checksumState["valid"] ?? false))) {
            fwrite(STDERR, "Strict module checksum verification failed for {$moduleDir}: " . implode(" | ", (array) ($checksumState["errors"] ?? [])) . "\n");
            exit(1);
        }
        $checksums = is_array($checksumState["checksums"] ?? null) ? $checksumState["checksums"] : [];
        $signatureState = (new CoreModuleSignatureValidator())->validate($moduleDir, (string) ($release["signature"] ?? ""), $checksums);
        if (!((bool) ($signatureState["valid"] ?? false))) {
            fwrite(STDERR, "Strict module signature verification failed for {$moduleDir}: " . implode(" | ", (array) ($signatureState["errors"] ?? [])) . "\n");
            exit(1);
        }
        ' "${ROOT_DIR}" "${m}"
      done
    fi
  else
    if [ -n "${RELEASE_SIGNING_KEY:-}" ] && [ -n "${RELEASE_SIGNING_KEY_ID:-}" ] && [ -f "${RELEASE_SIGNING_KEY}" ]; then
      "${ROOT_DIR}/scripts/release/sign-all-modules.sh" "${ROOT_DIR}/modules/admin" "${RELEASE_SIGNING_KEY}" "${RELEASE_SIGNING_KEY_ID}"
    else
      for m in "${ROOT_DIR}"/modules/admin/*; do
        [ -d "${m}" ] || continue
        php "${ROOT_DIR}/scripts/release/generate-module-checksums.php" "${m}" "${m}/checksums.json"
      done
    fi
  fi
fi

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

CURRENT_COMMIT="$(git -C "${ROOT_DIR}" rev-parse --short=12 HEAD 2>/dev/null || true)"
CURRENT_BRANCH="$(git -C "${ROOT_DIR}" rev-parse --abbrev-ref HEAD 2>/dev/null || true)"
PUBLIC_COMMIT="${CATMIN_RELEASE_PUBLIC_COMMIT:-${CURRENT_COMMIT}}"
PUBLIC_REPO="${CATMIN_PUBLIC_REPO_URL:-https://github.com/CatMin-Dev/CATMIN-Core}"
DEV_REPO="${CATMIN_DEV_REPO_URL:-https://github.com/CatMin-Dev/core}"

if [ -f "${STAGE_DIR}/version.json" ]; then
  php -r '
  $file = $argv[1];
  $buildCommit = (string) $argv[2];
  $buildBranch = (string) $argv[3];
  $publicCommit = (string) $argv[4];
  $publicRepo = (string) $argv[5];
  $devRepo = (string) $argv[6];
  $raw = is_file($file) ? (string) file_get_contents($file) : "{}";
  $json = json_decode($raw, true);
  if (!is_array($json)) {
      $json = [];
  }
  $json["build"] = [
      "channel" => "standalone",
      "commit" => $buildCommit,
      "branch" => $buildBranch,
      "public_commit" => $publicCommit,
      "dev_commit" => $buildCommit,
      "built_at" => gmdate("c"),
  ];
  $links = is_array($json["links"] ?? null) ? $json["links"] : [];
  $links["github_public"] = $publicRepo;
  $links["github_dev"] = $devRepo;
  $json["links"] = $links;
  file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
  ' "${STAGE_DIR}/version.json" "${CURRENT_COMMIT}" "${CURRENT_BRANCH}" "${PUBLIC_COMMIT}" "${PUBLIC_REPO}" "${DEV_REPO}"
fi

# Remove release-excluded content.
find "${STAGE_DIR}" -type d \( -name '.git' -o -name '.vscode' -o -name 'node_modules' -o -name 'tests' \) -prune -exec rm -rf {} +
find "${STAGE_DIR}" -type f \( -name '*.log' -o -name '*.map' -o -name '*.tmp' \) -delete
rm -f "${STAGE_DIR}/.env" "${STAGE_DIR}/.env.local" "${STAGE_DIR}/.env.production" || true
rm -f "${STAGE_DIR}/storage/install.lock" || true
rm -f "${STAGE_DIR}/storage/logs/"*.log || true
rm -f "${STAGE_DIR}/logs/"*.log || true
rm -rf "${STAGE_DIR}/storage/logs" || true
rm -rf "${STAGE_DIR}/storage/backups" || true
rm -rf "${STAGE_DIR}/storage/install" || true
rm -rf "${STAGE_DIR}/storage/updates/releases" || true
rm -rf "${STAGE_DIR}/storage/updates/reports" || true
rm -rf "${STAGE_DIR}/storage/modules" || true
rm -rf "${STAGE_DIR}/storage/trust/local-signing" || true
find "${STAGE_DIR}" -type f \( -name '*.key' -o -name '*.pem' -o -name '*.p12' -o -name '*.pfx' \) -delete
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
  "${STAGE_DIR}/storage/modules" \
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
  "${STAGE_DIR}/storage/modules" \
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
