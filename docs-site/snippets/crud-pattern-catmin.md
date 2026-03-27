# Pattern CRUD de Base CATMIN (Prompt 036)

## Objectif

Unifier les ecrans CRUD (index, create, edit, show optionnel, action de sortie) sans casser l'UI admin existante.

## Conventions retenues

- Layout: toujours `admin.layouts.catmin`.
- Header CRUD: composant `x-admin.crud.page-header`.
- Messages flash: composant `x-admin.crud.flash-messages`.
- Listing tabulaire: composant `x-admin.crud.table-card` avec:
  - `head` pour les colonnes
  - `rows` pour les lignes
  - fallback vide standardise.
- Formulaires: bloc card standard (`card` + `card-header` + `card-body`), grille Bootstrap `row g-3`, boutons de validation/annulation coherents.

## Routage CRUD recommande

Pour une ressource `resource` dans un module `module`:

- `admin.module.resource.index` (GET listing)
- `admin.module.resource.create` (GET create)
- `admin.module.resource.store` (POST create)
- `admin.module.resource.show` (GET show, optionnel)
- `admin.module.resource.edit` (GET edit)
- `admin.module.resource.update` (PUT/PATCH edit)
- `admin.module.resource.destroy` (DELETE)
- alternative soft-action: `toggle_active` / `archive` / `restore` selon le metier

## Application concrete

Le pattern est applique sur les vues des modules Users et Settings pour servir de base reutilisable aux prochains modules (Pages, News, Blog, etc.).
