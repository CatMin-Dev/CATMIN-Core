# CATMIN Addons (V1)

Ce dossier est reserve aux addons externes/optionnels installables projet par projet.

## Frontiere claire

- `modules/` = noyau CATMIN (core + modules metier du projet)
- `addons/` = extensions externes distribuables, optionnelles, independantes du noyau

## Convention minimale addon

Chaque addon doit respecter la structure suivante:

```text
addons/<nom-addon>/
  addon.json
  routes.php
  Controllers/
  Views/
  Services/
  Migrations/
  Assets/
```

## addon.json minimal

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

## Notes V1

- Pas de marketplace.
- Pas de multi-instance/multisite.
- Detection + chargement routes seulement (base stable, evolutive).
- Un addon desactive n'est pas charge.
