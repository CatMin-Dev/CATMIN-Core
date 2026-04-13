# CAT Tags Bridge

Bridge transversal de tags pour BLOG, PAGE, DIRECTORY et futurs contenus.

## Capacites

- stockage SQL des tags (`mod_cat_tags_tags`)
- stockage SQL des liaisons tags <-> entites (`mod_cat_tags_links`)
- autocompletion AJAX
- saisie moderne tags via input badges (virgule ou espace)
- usage count automatique
- snippets/widgets tags

## Endpoints admin

- `GET /admin/modules/tags-bridge`
- `POST /admin/modules/tags-bridge/sync`
- `GET /admin/modules/tags-bridge/suggest?q=...`

## Dependances

- Dependance forte: `cat-seo-meta`
- `cat-slug` reste optionnel
