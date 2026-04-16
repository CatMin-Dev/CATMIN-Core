#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT/.."

if ! command -v rg >/dev/null 2>&1; then
  echo "[FAIL] rg is required for guard-no-json-pseudo-bdd.sh" >&2
  exit 1
fi

patterns=(
  'trusted-devices\.json'
  'media\.variant_presets_json'
  'media\.preset_fallback_key'
  'media\.preset_fallback_json'
)

status=0
for pattern in "${patterns[@]}"; do
  if rg -n "$pattern" catmin modules \
    -g '!catmin/docs/anti-json-persistence-policy.md' \
    -g '!catmin/scripts/guard-no-json-pseudo-bdd.sh' \
    -g '!catmin/modules/admin/cat-media/database/migrations/004_harden_variant_presets_relational.php' \
    -g '!modules/admin/cat-media/database/migrations/004_harden_variant_presets_relational.php' \
    -g '!catmin/modules/admin/cat-media/src/Shared/MediaRepository.php' \
    -g '!modules/admin/cat-media/src/Shared/MediaRepository.php' \
    -g '!catmin/modules/admin/cat-media/tests/Feature/MediaRoutesSmokeTest.php' \
    -g '!modules/admin/cat-media/tests/Feature/MediaRoutesSmokeTest.php'; then
    status=1
  fi
done

if rg -n 'crop_json' catmin modules \
  -g '!catmin/docs/anti-json-persistence-policy.md' \
  -g '!catmin/scripts/guard-no-json-pseudo-bdd.sh' \
  -g '!catmin/modules/admin/cat-media/database/migrations/005_replace_crop_json_with_relational_columns.php' \
  -g '!modules/admin/cat-media/database/migrations/005_replace_crop_json_with_relational_columns.php' \
  -g '!catmin/modules/admin/cat-media/src/Shared/MediaRepository.php' \
  -g '!modules/admin/cat-media/src/Shared/MediaRepository.php'; then
  status=1
fi

if [[ "$status" -ne 0 ]]; then
  echo "[FAIL] banned pseudo-BDD JSON markers detected" >&2
  exit 1
fi

echo "[OK] no banned pseudo-BDD JSON markers outside approved migration cleanup paths"