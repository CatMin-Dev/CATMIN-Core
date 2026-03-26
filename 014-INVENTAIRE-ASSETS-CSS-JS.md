# 014 — Inventaire détaillé des assets CSS/JS

## Périmètre analysé
- `dashboard/assets/css/`
- `dashboard/assets/js/`
- Images/icônes nécessaires au dashboard:
  - `dashboard/assets/img/`
  - `dashboard/assets/images/`

---

## 1) Inventaire CSS

### Fichiers principaux (actifs / structurants)
- `dashboard/assets/css/catmin.css` (surcouche CATMIN principale)
- `dashboard/assets/css/themes.css` (thèmes / tokens / conventions)
- `dashboard/assets/css/main.css` (héritage template)
- `dashboard/assets/css/custom.css` (surcharges legacy)
- `dashboard/assets/css/bootstrap-5.3.8/css/bootstrap.css`
- `dashboard/assets/css/@fortawesome/fontawesome-free/css/all.min.css`
- `dashboard/assets/css/bootstrap-icons/font/bootstrap-icons.min.css`
- `dashboard/assets/css/@eonasdan/tempus-dominus/dist/css/tempus-dominus.min.css`
- `dashboard/assets/css/leaflet/dist/leaflet.css`

### Dépendances externes (packagées localement)
- Bootstrap 5.3.8
- Font Awesome Free
- Bootstrap Icons
- Tempus Dominus
- Leaflet
- Pickr thèmes (chargés depuis `vendor/node_modules/...` via `header.php`)

### Fichiers suspects / à surveiller
- Dossier `dashboard/assets/css/backup/*` (copies historiques, dont fichiers `.php` dans l’arbre CSS).
- `dashboard/assets/css/bootstrap-5.3.8/js/*` (JS stocké sous `assets/css` → anomalie d’organisation).
- `dashboard/assets/css/leaflet/dist/*.js` (JS stocké dans un arbre CSS, même remarque).

### Fichiers critiques à préserver absolument
- `catmin.css`, `themes.css`, `main.css`, `custom.css`
- `bootstrap.css` + icons + `all.min.css`
- `tempus-dominus.min.css`, `leaflet.css`
- Assets de fonts/images associés (webfonts, marqueurs leaflet)

### Doublons potentiels (à traiter plus tard)
Noms présents en double (actif + backup):
- `catmin.css`
- `custom.css`
- `main.css`
- `themes.css`

Conclusion: doublonnage principalement dû aux sauvegardes; nettoyage différé recommandé.

---

## 2) Inventaire JS

### Bibliothèques utilisées (actives)
Principalement exposées via `dashboard/assets/js/main-minimal.js` + importmap `header.php`:
- Bootstrap
- Chart.js
- ECharts
- Leaflet
- Tempus Dominus
- Choices.js
- NoUiSlider
- DataTables (+ extensions)
- Inputmask
- Pickr
- Cropper
- JSZip
- Skycons (adapté)

### Scripts métiers réellement actifs (noyau)
- `dashboard/assets/js/main-minimal.js` (bundle orchestration principal)
- `dashboard/assets/js/chart-initializer.js`
- `dashboard/assets/js/sidebar.js`
- `dashboard/assets/js/modules/*` (charts, dashboard, forms, maps, tables, weather, ui)
- `dashboard/assets/js/utils/*` (dom, logger, security, validation, optimizer)

### Scripts plutôt démo / ciblés page
- `dashboard/assets/js/page/index3-analytics.js`
- `dashboard/assets/js/main-calendar.js`
- `dashboard/assets/js/main-inbox.js`
- `dashboard/assets/js/main-form-basic.js`
- `dashboard/assets/js/main-upload.js`
- `dashboard/assets/js/main-tables.js`
- `dashboard/assets/js/main-spa.js` (usage à confirmer selon mode runtime)

### Scripts de test (non critiques runtime)
- `dashboard/assets/js/test/setup.js`
- `dashboard/assets/js/utils/*.test.js`

### Scripts liés aux domaines demandés
- Graphiques: `chart-initializer.js`, `modules/charts.js`, `modules/chart-core.js`, `modules/echarts.js`
- Tableaux: `modules/tables.js`, `main-tables.js`, `utils/table-optimizer.js`
- Widgets/dashboard: `modules/dashboard.js`, `modules/dashboard-pages.js`, `page/index3-analytics.js`
- Formulaires/upload: `modules/forms.js`, `main-form-basic.js`, `main-upload.js`
- Cartes/météo: `modules/maps.js`, `modules/weather.js`, `vendor/skycons-adapter.js`

### Zones de refactor plus tard
- Réduire les scripts inline dans `components/footer.php`.
- Clarifier les responsabilités entre `main-minimal.js`, `init.js` et `modules/*`.
- Isoler le code test hors runtime de prod si nécessaire.
- Unifier la stratégie météo (adapter Skycons + backend proxy).

---

## 3) Images / icônes nécessaires au dashboard

### Branding critique (`dashboard/assets/img/`)
- `icon.png`
- `logo_color.png`
- `logo_white.png`
- `logo_black.png`
- `logo_social.png`

### Ressources UI (`dashboard/assets/images/`)
- Favicons SVG/ICO
- avatars, médias démo, logos de paiement
- ressources visuelles utilisées dans pages de démonstration

---

## Conclusion
L’inventaire est établi avec zones critiques, dépendances et risques de nettoyage différé. Aucun renommage/suppression/refonte n’est effectué à ce stade, conformément à la phase de fondation.
