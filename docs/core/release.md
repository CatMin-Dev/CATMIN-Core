# Release Core

## Versioning
- version applicative: `version.json`
- version DB: `db_versions`

## Upgrade
- Preflight via `CoreUpdateStrategy::preflight()`.
- Upgrade via `CoreUpdateStrategy::upgrade()`.
- Rapport dans `storage/updates/reports/`.

## Packaging
- Script officiel: `scripts/release/build-standalone-zip.sh`
- Vérification package: `scripts/release/verify-standalone-package.sh`
