# CATMIN Standalone ZIP - Strategie Finale (Prompt 035)

## Cible
Archive ZIP deployable sur hebergement PHP classique, sans build serveur, sans Composer obligatoire, sans Node.

## Structure officielle embarquee
- `admin/`
- `config/`
- `core/`
- `cron/`
- `database/`
- `db/`
- `front/`
- `install/`
- `modules/`
- `public/`
- `storage/`
- `docs/`
- `bootstrap.php`
- `index.php`
- `.htaccess`
- `robots.txt`
- `.env.example`
- `README.md`
- `version.json`
- `.version_history.json`

## Exclusions strictes
- `.env`
- `.git/`, `.github/`, `.vscode/`
- `node_modules/`, `tests/`
- logs/caches temporaires release

## Build
```bash
bash scripts/release/build-standalone-zip.sh
```

## Verification
```bash
bash scripts/release/verify-standalone-package.sh storage/updates/releases/catmin-<version>-standalone.zip
```

## Artefacts produits
- `catmin-<version>-standalone.zip`
- `catmin-<version>-standalone-manifest.json`
- `release-report.json`

## Tests post-package obligatoires
Voir `docs/release/post-package-tests.md`.

## Etat release-ready
- Packaging finalise: OUI
- Verification archive: OUI
- Documentation minimum embarquee: OUI
