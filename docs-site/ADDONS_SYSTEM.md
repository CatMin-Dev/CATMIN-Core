# Systeme Addons (V1)

## Objectif

Etablir une base simple, maintenable et distribuable pour des addons installables,
sans confondre addons externes et modules du noyau.

## Separation des responsabilites

- Modules (`modules/`): composants du noyau et du projet CATMIN.
- Addons (`addons/`): extensions externes optionnelles, installables par projet.

Cette separation evite de melanger le cycle de vie du core avec des paquets externes.

## Convention minimum addon

Un addon doit contenir au minimum:

- `addon.json`
- `routes.php`
- `Controllers/`
- `Views/`
- `Services/`
- `Migrations/`
- `Assets/`

## Runtime V1

- Detection automatique des addons dans `addons/`.
- Validation minimale de structure.
- Chargement des routes des addons `enabled=true` uniquement.
- Journalisation d'un warning si un addon actif ne peut pas charger ses routes.

## Format addon.json (minimum)

```json
{
  "name": "Example Addon",
  "slug": "example-addon",
  "version": "1.0.0",
  "enabled": false,
  "requires_core": true,
  "description": "Addon externe optionnel"
}
```

Version attendue: semver simple `major.minor.patch` (ex: `1.0.0`).

## Compatibilite distribution

Le systeme est pense pour une installation projet par projet:

- copier/deployer un addon dans `addons/<slug>`
- regler `enabled` dans `addon.json`
- le runtime detecte et charge automatiquement

Pas de registre central ni marketplace en V1.

## Packaging et distribution

Une commande de packaging est disponible:

- `php artisan catmin:addon:package <slug>`

Voir le guide detaille: `ADDONS_PACKAGING.md`.
