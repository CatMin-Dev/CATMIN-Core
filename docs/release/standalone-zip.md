# CATMIN Standalone ZIP Packaging

## Objectif
Livrer un ZIP prêt à uploader en mutualisé PHP, sans build côté hébergeur, avec installateur séparé et core protégé.

## Contenu du package
- `public/`
- `core/`
- `admin/`
- `install/`
- `modules/`
- `storage/`
- `database/`
- `config/`
- `bootstrap.php`
- `version.json`

## Exclusions release
- `.env`
- `.git/`, `.vscode/`, `node_modules/`, `tests/`
- logs runtime et caches temporaires
- source maps front inutiles en release (`*.map`)

## Build officiel
```bash
cd catmin
bash scripts/release/build-standalone-zip.sh
```

## Vérification post-build
```bash
bash scripts/release/verify-standalone-package.sh storage/updates/releases/catmin-<version>-standalone.zip
```

## Post-package checks
- Le ZIP contient toutes les racines obligatoires.
- Aucun secret (`.env`) n'est présent.
- Les dossiers runtime (`storage`, `cache`, `logs`, `sessions`, `tmp`) existent et sont propres.
- `public/` reste l'entrypoint HTTP.
