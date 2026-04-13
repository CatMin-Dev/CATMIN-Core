# CAT SEO Meta Bridge

CAT SEO Meta est un bridge transversal pour centraliser les metadonnees SEO, le score, les alertes et la previsualisation sociale.

## Capacites

- stockage SQL dans `mod_cat_seo_meta`
- score SEO explicable
- audit rapide (champs manquants + signaux)
- social preview
- widgets/snippets reutilisables
- integration prevue pour PAGE, BLOG, DIRECTORY

## Endpoints admin

- `GET /admin/modules/seo-meta`
- `POST /admin/modules/seo-meta/save`
- `POST /admin/modules/seo-meta/audit`

## Notes

Le bridge n automatise pas magiquement la SEO: il detecte, score, alerte et assiste.

## Integration future modules maitres

- Hook prepare: `content.editor.panels`
- Modules cibles: `cat-page`, `cat-blog`, `cat-directory`
- Policy obligatoire: ces modules doivent fonctionner avec `cat-slug` + `cat-seo-meta`
- Quand les modules maitres existeront et appliqueront le hook, le panneau SEO embarque sera ajoute automatiquement.
