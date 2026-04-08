# Architecture Core

## Couches principales
- `bootstrap.php` et `core/boot.php`: démarrage, constantes, handlers.
- `core/router.php` + `core/route-dispatcher.php`: routing HTTP.
- `core/security/*`: CSRF, headers, whitelist IP, maintenance.
- `core/auth/*`: authentification admin, session, lockout.
- `core/settings-*`: settings centralisés DB.
- `core/module-*`: chargement/validation modules.
- `core/update/*` + `core/versioning/*`: versioning, preflight, upgrade.

## Entrypoints
- `public/index.php` (front)
- `public/admin.php` (admin)
- `public/install.php` (installer)

## Runtime
- fichiers d'état et logs dans `storage/` + `logs/`.
