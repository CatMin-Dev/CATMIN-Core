# 006 — Note technique d’intégration admin CATMIN

## Référence fonctionnelle actuelle
- La référence reste `dashboard/index.php`.
- Cette entrée assemble le dashboard via `components/` puis injecte `content/<page>.html`.

## Principe d’intégration retenu (progressif)
1. Conserver `dashboard/` intact (sources legacy toujours utilisables).
2. Introduire une couche Laravel/Blade de prévisualisation et de transition.
3. Réutiliser la logique de sélection de page (whitelist + fallback) côté Laravel.
4. Charger les contenus legacy `content/*.html` dans un shell Blade compatible visuel existant.
5. Continuer la migration par paliers (partiels Blade pertinents uniquement).

## Première base implémentée
- Contrôleur: `app/Http/Controllers/Admin/LegacyPreviewController.php`
  - whitelist des pages admin
  - validation/sanitation du paramètre page
  - lecture de `dashboard/content/<page>.html`
  - rendu dans une vue Blade
- Layout Blade admin: `resources/views/admin/layouts/catmin.blade.php`
- Partiels Blade initiaux:
  - `resources/views/admin/partials/head.blade.php`
  - `resources/views/admin/partials/aside.blade.php`
  - `resources/views/admin/partials/topnav.blade.php`
  - `resources/views/admin/partials/footer.blade.php`
- Vue de rendu du contenu legacy:
  - `resources/views/admin/pages/legacy-preview.blade.php`

## Routing préparé
- Nouvelle route de preview progressive:
  - `/admin/preview/{page?}`
- Coexistence conservée:
  - `/dashboard` et `/dashboard/{page}` redirigent vers `dashboard/index.php?page=...`

## Garanties de non-régression
- Aucun fichier legacy supprimé (`index.php`, `components/`, `content/`, CSS/JS existants).
- Aucun déplacement du dashboard historique.
- Aucune conversion massive page-par-page en Blade.

## Prochaines étapes recommandées
- Aligner les menus Blade preview sur la navigation réelle legacy.
- Remplacer progressivement les partiels Blade simplifiés par des versions plus fidèles.
- Isoler ensuite les pages à forte valeur pour migration ciblée.
