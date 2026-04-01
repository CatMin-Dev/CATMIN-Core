#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'USAGE'
CATMIN release builder

Usage:
  bash scripts/release/build-release.sh [--version <version>] [--skip-build] [--skip-tag]

Options:
  --version <version>  Explicit release version (ex: v3-dev-20260401)
  --skip-build         Skip npm build and composer --no-dev install in staging
  --skip-tag           Do not create git tag
  -h, --help           Show this help
USAGE
}

version=""
skip_build=0
skip_tag=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --version)
      version="${2:-}"
      shift 2
      ;;
    --skip-build)
      skip_build=1
      shift
      ;;
    --skip-tag)
      skip_tag=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage
      exit 1
      ;;
  esac
done

project_root="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$project_root"

if [[ -z "$version" ]]; then
  raw_version="$(php -r "echo getenv('DASHBOARD_VERSION') ?: 'V3-dev';" | tr '[:upper:]' '[:lower:]')"
  safe_version="$(echo "$raw_version" | tr -cs 'a-z0-9._-' '-')"
  safe_version="${safe_version#-}"
  safe_version="${safe_version%-}"
  version="${safe_version:-v3-dev}-$(date +%Y%m%d%H%M%S)"
fi

release_name="catmin-${version}"
release_dir="$project_root/runtime/releases"
stage_dir="$release_dir/.stage-${release_name}"
archive_path="$release_dir/${release_name}.zip"
tag_name="release/${version}"

if ! command -v zip >/dev/null 2>&1; then
  echo "Missing required command: zip" >&2
  echo "Install it first (Ubuntu/Debian: apt install zip)." >&2
  exit 1
fi

mkdir -p "$release_dir"
rm -rf "$stage_dir" "$archive_path"

if [[ $skip_build -eq 0 ]]; then
  if [[ -f package-lock.json ]]; then
    npm ci
  else
    npm install
  fi
  npm run build
fi

rsync -a \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude 'node_modules/' \
  --exclude 'tests/' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude '.editorconfig' \
  --exclude '.gitattributes' \
  --exclude '.gitignore' \
  --exclude 'phpunit.xml' \
  --exclude 'scripts/dev/' \
  --exclude 'storage/logs/' \
  --exclude 'runtime/releases/' \
  --exclude 'prompts/' \
  ./ "$stage_dir/"

if [[ $skip_build -eq 0 ]]; then
  (
    cd "$stage_dir"
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
  )
fi

(
  cd "$stage_dir"
  build_revision="$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
  cat > update-manifest.json <<EOF
{
  "name": "CATMIN Update Package",
  "version": "${version}",
  "built_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "revision": "${build_revision}",
  "format": "catmin.update.v1"
}
EOF
  zip -qr "$archive_path" .
)

checksum_path="${archive_path}.sha256"
sha256sum "$archive_path" | awk '{print $1}' > "$checksum_path"

rm -rf "$stage_dir"

if [[ $skip_tag -eq 0 ]]; then
  if git rev-parse "$tag_name" >/dev/null 2>&1; then
    echo "Tag already exists: $tag_name" >&2
    exit 1
  fi

  git tag -a "$tag_name" -m "CATMIN release ${version}"
fi

echo "Release archive: $archive_path"
echo "SHA-256: $(cat "$checksum_path")"
echo "Checksum file: $checksum_path"
if [[ $skip_tag -eq 0 ]]; then
  echo "Git tag created: $tag_name"
fi
