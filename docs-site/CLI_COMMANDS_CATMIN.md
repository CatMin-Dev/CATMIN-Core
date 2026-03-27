# Commandes CLI CATMIN (V1)

## Nomenclature

Prefixe unique: `catmin:`

## Commandes principales

### Modules

- `php artisan catmin:modules:list`
- `php artisan catmin:module:enable <slug>`
- `php artisan catmin:module:disable <slug>`

### Addons

- `php artisan catmin:addons:list`
- `php artisan catmin:addon:install <slug> [--no-enable] [--no-migrate]`
- `php artisan catmin:addon:package <slug> [--output=...] [--format=zip]`

### Sante systeme

- `php artisan catmin:system:check`
- `php artisan catmin:system:check --json`

### Sauvegarde

- `php artisan catmin:backup:create [--with-db] [--without-media] [--without-extensions]`

### Maintenance

- `php artisan catmin:maintenance <on|off|status>`

### Deja disponibles

- `catmin:migrate:extensions`
- `catmin:update:plan`
- `catmin:update:apply`
- `catmin:rbac:sync`
- `catmin:settings:export`
- `catmin:settings:import`

## Notes d'usage

- Commandes pragmatiques, sans magie.
- Toutes les operations restent compatibles avec le workflow GitHub.
- `catmin:addon:install` suppose un addon deja present dans `addons/`.
- `catmin:addon:package` genere une archive zip distribuable d'un addon existant.
