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
- `php artisan catmin:addon:install --package=<archive.zip> [--no-enable] [--no-migrate]`
- `php artisan catmin:addon:package <slug> [--output=...] [--format=zip]`
- `php artisan catmin:addon:marketplace:index`

### Sante systeme

- `php artisan catmin:system:check`
- `php artisan catmin:system:check --json`

### Sauvegarde

- `php artisan catmin:backup:create [--with-db] [--without-media] [--without-extensions]`

### Maintenance

- `php artisan catmin:maintenance <on|off|status>`

### Deja disponibles

- `catmin:migrate:extensions`
- `catmin:migrate:safe`
- `catmin:update:plan`
- `catmin:update:apply`
- `catmin:recovery:run`
- `catmin:rbac:sync`
- `catmin:settings:export`
- `catmin:settings:import`

## Notes d'usage

- Commandes pragmatiques, sans magie.
- Toutes les operations restent compatibles avec le workflow GitHub.
- `catmin:addon:install` peut installer depuis `addons/` ou depuis un package zip du registre local.
- `catmin:addon:package` genere une archive zip distribuable d'un addon existant.
