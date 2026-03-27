# Base Frontend Rendering (Prompt 039)

## Logique de rendu

- Route frontend simple: `/page/{slug}`.
- Controleur dedie frontend: `App\\Http\\Controllers\\Frontend\\PageController`.
- Recuperation de la page via helper `page_by_slug($slug, true)`.
- Si page absente ou non publiee: 404.
- Si page presente: rendu Blade `resources/views/frontend/page.blade.php`.

## Separation admin / frontend

- Admin CRUD reste dans le module Pages (`modules/Pages/...`).
- Frontend de lecture reste dans `app/Http/Controllers/Frontend` + `resources/views/frontend`.
- Aucun couplage a un framework frontend lourd.

## Extension future preparee

- Le helper de lecture par slug isole la source de donnees.
- Le rendu peut evoluer vers templates, themes, ou cache sans casser les routes admin.
