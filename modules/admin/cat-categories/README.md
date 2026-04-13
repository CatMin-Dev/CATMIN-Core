# CAT Categories Bridge

Bridge transversal pour categories structurees, hierarchiques et reutilisables cross-modules.

## Capacites

- stockage SQL categories (`mod_cat_categories_categories`)
- stockage SQL liaisons (`mod_cat_categories_links`)
- arbre parent/enfant
- selecteur hierarchique embarquable
- usage count
- snippets/widgets categories

## Endpoints admin

- `GET /admin/modules/categories-bridge`
- `POST /admin/modules/categories-bridge/create`
- `POST /admin/modules/categories-bridge/sync`
- `GET /admin/modules/categories-bridge/tree`

## Dependances

- Dependances fortes: `cat-seo-meta`, `cat-tags`
- `cat-slug` reste optionnel pour ce bridge
