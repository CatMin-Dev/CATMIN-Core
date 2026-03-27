# Packaging Addons CATMIN (V1)

## Objectif

Definir une convention simple pour distribuer un addon CATMIN sans marketplace.

## Format de distribution recommande

Format V1: archive `zip` d'un addon unique.

Nommage conseille:

`<slug>-<version>-<timestamp>.zip`

Exemple:

`example-addon-1.2.0-20260327-153000.zip`

## Structure minimale attendue

Le contenu archive doit representer directement le dossier addon:

- `addon.json`
- `routes.php`
- `Controllers/`
- `Views/`
- `Services/`
- `Migrations/`
- `Assets/`

`addon.json` doit rester coherent avec le package distribue (`slug`, `version`, `enabled`).

## Creation d'un package

Commande:

```bash
php artisan catmin:addon:package <slug>
```

Options utiles:

- `--output=/chemin/sortie`
- `--format=zip`

Sortie par defaut:

`storage/app/addons/packages/`

## Installation manuelle d'un addon package

1. Decompresser l'archive dans `addons/<slug>`.
2. Verifier `addon.json`.
3. Installer proprement:

```bash
php artisan catmin:addon:install <slug>
```

4. Si necessaire, forcer etapes manuelles:

- activer (`enabled=true`) dans `addon.json`
- relancer migrations extensions

## Compatibilite GitHub / projet par projet

Deux strategies conseillees:

- depot Git dedie par addon (release zip)
- archive interne partagee dans un canal equipe

En V1, CATMIN ne gere pas de store central. La distribution est explicite et controlee.

## Limites V1

- pas de signature cryptographique des packages
- pas de verification checksum integree
- pas de resolution automatique de dependances inter-addons

Ces points peuvent etre traites en V2.
