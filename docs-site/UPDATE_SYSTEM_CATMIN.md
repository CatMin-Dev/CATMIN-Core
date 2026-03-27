# Systeme de Mise a Jour CATMIN (V1)

## Principes

- Pas d'auto-update SaaS.
- Workflow adapte a une installation par projet.
- Source de verite: depot GitHub + versioning modules/addons + migrations Laravel.

## Strategie

### Noyau CATMIN

- update via Git (`git fetch`, `git checkout <tag>` ou `git pull`)
- dependances via Composer
- migrations core via `php artisan migrate --force`

### Modules

- version declaree dans `module.json`
- suivi de version installee via settings
- migrations dediees par module (`modules/<module>/Migrations`)

### Addons

- version declaree dans `addon.json`
- suivi de version installee via settings
- migrations dediees par addon (`addons/<addon>/Migrations`)

## Commandes d'assistance

```bash
php artisan catmin:update:plan
php artisan catmin:update:apply --dry-run
php artisan catmin:update:apply
```

## Workflow recommande

1. Backup DB + fichiers (`storage`, uploads, `.env`).
2. Recuperer la version cible depuis GitHub.
3. `composer install --no-dev --optimize-autoloader`.
4. `php artisan catmin:update:plan`.
5. `php artisan catmin:update:apply`.
6. Verifier admin, logs, queue workers, cron.

## Securite / anti-desordre

- detection des collisions de noms de migrations avant execution
- arret en cas de collision
- execution explicite et tracable (pas de magie)
