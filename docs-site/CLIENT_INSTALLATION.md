# Installation Client CATMIN (V1)

Ce guide cible une installation CATMIN sur un projet client, de facon concrete.

## 1. Prerequis serveur

- PHP 8.2+
- Extensions PHP: pdo, pdo_mysql, mbstring, openssl, json, fileinfo, tokenizer
- MySQL/MariaDB
- Composer
- Node.js + npm (si build frontend necessaire)
- Acces shell pour commandes artisan

## 2. Recuperer le projet

```bash
git clone <repo-catmin> catmin
cd catmin
composer install
npm install
```

## 3. Configuration .env

```bash
cp .env.example .env
php artisan key:generate
```

Configurer au minimum:

- `APP_ENV`
- `APP_URL`
- `DB_*`
- `CATMIN_ADMIN_PATH`
- `CATMIN_ADMIN_USERNAME`
- `CATMIN_ADMIN_PASSWORD`
- `CATMIN_API_INTERNAL_TOKEN` (si API interne utilisee)

## 4. Base de donnees

```bash
php artisan migrate
php artisan catmin:migrate:extensions
```

Verifier ensuite:

```bash
php artisan catmin:system:check
php artisan catmin:install:check
```

## 5. Compte admin

Le login admin utilise:

- `CATMIN_ADMIN_USERNAME`
- `CATMIN_ADMIN_PASSWORD`

URL d'acces:

- `/<CATMIN_ADMIN_PATH>/login`

## 6. Modules et addons

Lister les modules:

```bash
php artisan catmin:modules:list
```

Activer/desactiver:

```bash
php artisan catmin:module:enable <slug>
php artisan catmin:module:disable <slug>
```

Lister les addons:

```bash
php artisan catmin:addons:list
```

Installer un addon deploie dans `addons/<slug>`:

```bash
php artisan catmin:addon:install <slug>
```

## 7. Build assets et publication

Selon le workflow projet:

```bash
npm run build
php artisan storage:link
php artisan optimize:clear
```

## 8. Bonnes pratiques de deploiement

- sauvegarder DB + fichiers avant chaque mise a jour
- tester migrations sur preproduction
- garder `.env` hors versioning public
- verifier permissions sur `storage/` et `bootstrap/cache/`
- utiliser `catmin:update:plan` avant `catmin:update:apply`

## 9. Points de vigilance

- RBAC: les menus admin dependent des permissions session
- Uploads: respecter whitelist extensions et taille max configuree
- Addons: valider la structure (`addon.json`, routes, migrations)
- Maintenance: distinguer maintenance CATMIN (`catmin:maintenance`) et Laravel natif (`php artisan down`)

## 10. Checklist rapide de mise en ligne

1. `php artisan catmin:install:check --json`
2. `php artisan catmin:system:check --json`
3. `php artisan catmin:maintenance on` (si fenetre technique)
4. migrations + verifications
5. `php artisan catmin:maintenance off`

