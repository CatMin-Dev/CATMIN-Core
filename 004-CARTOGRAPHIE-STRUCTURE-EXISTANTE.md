# 004 — Cartographie de la structure existante (CATMIN Dashboard)

## Périmètre analysé
- Répertoire analysé: `catmin/dashboard`
- Fichiers de référence vérifiés: `index.php`, `components/*`, `content/*`, `assets/*`, `api/*`, pages techniques (`login.html`, `page_403.html`, `page_404.html`, `page_500.html`)

## Rôle de `index.php`
- Point d’entrée applicatif actuel du dashboard.
- Contrôle la page active via `$_GET['page']` avec une whitelist (`$allowedPages`).
- Sécurise la valeur avec regex puis fallback vers `dashboard`.
- Monte la page via includes structurés:
  1. `components/header.php`
  2. `components/aside.php`
  3. `components/topnav.php`
  4. `content/<page>.html` (si lisible)
  5. `components/footer.php`
- Si le fichier de contenu est absent: code HTTP 404 + message d’alerte rendu dans `<main>`.

## `index.html`
- Dans ce périmètre `catmin/dashboard`, aucun `index.html` n’est présent.
- Conformément aux prompts, la base active est `index.php` (et non une entrée HTML statique).

## Rôle des dossiers

### `components/`
- Fournit les briques communes de layout:
  - `header.php`: `<head>`, CSS/JS globaux, favicon, importmap, ouverture du layout principal.
  - `aside.php`: sidebar gauche, branding, menu de navigation.
  - `topnav.php`: barre haute (messages, profil, actions).
  - `footer.php`: footer + scripts d’initialisation dashboard.

### `content/`
- Cœur des vues admin actuelles.
- Pages de contenu HTML injectées dynamiquement par `index.php` selon `page`.

### `assets/css`
- Feuilles de style du dashboard (Bootstrap/vendor + thèmes + surcouche CATMIN).

### `assets/js`
- Scripts front/dashboard (modules, initialisation, adaptations vendor).

### `assets/images`
- Images historiques du template (avatars, médias génériques).

### `api/`
- Endpoints backend légers côté dashboard.
- Présence actuelle: `weather.php` (proxy météo côté serveur).

## Rôle des fichiers communs

### `header.php`
- Construit le `<head>`, applique les classes de page/body, charge styles/scripts globaux.
- Définit CSP/meta sécurité et l’environnement JS de base.

### `aside.php`
- Définit navigation latérale et branding.
- Porte la structure menu hiérarchique vers les pages `index.php?page=...`.

### `topnav.php`
- Définit navigation supérieure (notifications/messages/profil).

### `footer.php`
- Ferme la structure HTML globale.
- Centralise une partie des scripts d’initialisation (widgets/charts/comportements).

## Pages de contenu réellement présentes dans `content/`
- `calendar.html`
- `chartjs.html`
- `contacts.html`
- `dashboard.html`
- `e_commerce.html`
- `echarts.html`
- `form.html`
- `form_advanced.html`
- `form_buttons.html`
- `form_upload.html`
- `form_validation.html`
- `form_wizards.html`
- `general_elements.html`
- `icons.html`
- `inbox.html`
- `invoice.html`
- `map.html`
- `media_gallery.html`
- `other_charts.html`
- `plain_page.html`
- `pricing_tables.html`
- `profile.html`
- `project_detail.html`
- `projects.html`
- `tables.html`
- `tables_dynamic.html`
- `typography.html`
- `widgets.html`

## Écart structurel observé (important pour la suite)
- `index.php` autorise aussi: `fixed_footer`, `fixed_sidebar`, `level2`.
- Ces 3 fichiers ne sont pas présents dans `content/` actuellement.
- Effet actuel: requête possible mais rendu fallback 404 interne dans `<main>`.

## Pages techniques réutilisables
- `login.html`
- `page_403.html`
- `page_404.html`
- `page_500.html`

## Conclusion opérationnelle
- Structure existante valide pour intégration progressive Laravel autour de `index.php` + `components/` + `content/`.
- Approche recommandée inchangée: conserver la logique d’includes, encapsuler progressivement, éviter toute refonte brutale visuelle/structurelle.
