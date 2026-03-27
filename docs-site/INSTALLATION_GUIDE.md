# Guide d'installation CATMIN (V1)

## Prerequis

- PHP `8.2+`
- Extensions PHP: `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `fileinfo`
- Base de donnees MySQL/MariaDB accessible
- Dossiers accessibles en ecriture:
  - `storage/`
  - `storage/logs/`
  - `storage/framework/cache/`
  - `storage/framework/views/`
  - `bootstrap/cache/`

## Variables d'environnement minimales

- `APP_KEY`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## Installation projet par projet

1. Recuperer le code
2. Installer dependances

```bash
composer install
```

3. Configurer `.env`
4. Lancer migrations

```bash
php artisan migrate --force
```

5. Verifier prerequis CATMIN

```bash
php artisan catmin:install:check
```

6. Initialiser roles RBAC (optionnel recommande)

```bash
php artisan catmin:rbac:sync
```

## Verification assistee

Commande de checks explicites:

```bash
php artisan catmin:install:check --json
```

En cas d'echec, la commande retourne `KO` avec message lisible par check.

## Note V1

Pas de wizard lourd a ce stade. La base est volontairement simple, reproductible et maintenable.
