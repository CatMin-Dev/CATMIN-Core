# Sauvegarde et Restauration CATMIN (V1)

## Objectif

Fournir une strategie simple, locale et comprehensible pour sauvegarder CATMIN.

## Ce qui est sauvegarde

Par la commande `catmin:backup:create`:

- `manifest.json` (etat systeme, modules, addons, options)
- export settings (`settings-export.json`)
- medias (`storage/app/public/media`) sauf si `--without-media`
- addons projet (`addons/`) sauf si `--without-extensions`
- dump SQL optionnel (`--with-db`, MySQL uniquement V1)

## Commandes

Sauvegarde standard:

```bash
php artisan catmin:backup:create
```

Sauvegarde complete (avec DB):

```bash
php artisan catmin:backup:create --with-db --name=pre-release
```

Sortie:

`storage/app/backups/<timestamp>-<name>/`

## Restauration (processus manuel V1)

1. Restaurer la base depuis `database.sql` si present.
2. Restaurer medias vers `storage/app/public/media`.
3. Restaurer addons vers `addons/`.
4. Reimporter settings si besoin:

```bash
php artisan catmin:settings:import <backup>/settings-export.json --overwrite
```

5. Rejouer migrations extensions:

```bash
php artisan catmin:migrate:extensions
```

6. Verifier l'etat:

```bash
php artisan catmin:system:check --json
php artisan catmin:install:check --json
```

## Precautions

- verifier les permissions dossiers avant et apres copie
- tester une restauration sur environnement non production
- conserver plusieurs points de sauvegarde (rotation simple)

## Limites V1

- restauration non automatisee en une commande
- DB dump automatique supporte seulement MySQL
- pas de chiffrement natif des sauvegardes
