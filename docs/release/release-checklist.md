# CATMIN Release Checklist

## Avant build
- Version `version.json` à jour.
- Migrations DB validées.
- Installer validé de bout en bout.
- Cron de base présents et désactivés par défaut.

## Build
- Exécuter `scripts/release/build-standalone-zip.sh`.
- Vérifier le ZIP avec `scripts/release/verify-standalone-package.sh`.

## Validation fonctionnelle
- Installation vierge sur environnement test.
- Login admin ok.
- Vérification routes front/admin/install.
- Vérification écriture runtime (`storage`, `cache`, `logs`, `sessions`, `tmp`).

## Validation sécurité
- Aucun fichier `.env` dans package.
- `install/` verrouillé après installation.
- CSP et headers actifs.

## Release
- Publier ZIP final.
- Publier notes de version.
- Archiver rapport de build.

## Références V1
- Checklist finale complète: `docs/release/final-release-checklist.md`
- Recovery et limites connues: `docs/release/recovery-known-limits.md`
- Backup initial install: `docs/release/installer-initial-backup.md`
- Topbar / i18n / notifications / apps: `docs/release/topbar-bridges-i18n-notifications-apps.md`
