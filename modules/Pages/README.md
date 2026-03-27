# Pages Module

Module CATMIN de base pour gerer des pages frontend simples.

## Champs retenus (v1)

- `title` (string, requis)
- `slug` (string unique, genere depuis le titre si absent)
- `content` (longtext, optionnel)
- `status` (draft|published)
- `published_at` (datetime, optionnel)

## Interface admin livree

- listing des pages
- creation
- edition
- publication/depublication rapide

## Helpers futurs prepares (sans surcharge)

Le service `PagesAdminService` expose une base `publicPath(Page $page)` qui prepare un helper URL frontend futur sans ajouter de couche complexe pour l'instant.

## Notes

- Pattern CRUD CATMIN respecte via composants `x-admin.crud.*`.
- Base volontairement simple et extensible pour futures evolutions SEO, templates, blocs.
