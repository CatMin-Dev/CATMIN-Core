# 010 — Base auth + pages système admin

## Analyse des pages de référence
Pages étudiées et conservées comme référence visuelle:
- `dashboard/login.html`
- `dashboard/page_403.html`
- `dashboard/page_404.html`
- `dashboard/page_500.html`

Ces pages sont traitées comme base UI existante (rendu conservé au maximum).

## Base technique Laravel mise en place

### 1) Accès admin simple (premier socle)
- Contrôleur: `app/Http/Controllers/Admin/AuthController.php`
- Middleware session: `app/Http/Middleware/EnsureCatminAdminAuthenticated.php`
- Alias middleware: `catmin.admin` enregistré dans `bootstrap/app.php`
- Configuration initiale: `config/catmin.php`
- Variables `.env`:
  - `CATMIN_ADMIN_USERNAME`
  - `CATMIN_ADMIN_PASSWORD`

### 2) Routes de connexion et d’accès
- `GET /admin/login` → page login existante (`dashboard/login.html`)
- `POST /admin/login` → validation simple user/password + ouverture session
- `POST /admin/logout` → fermeture session admin
- `GET /admin/access` (protégée par `catmin.admin`) → redirection dashboard admin

### 3) Routes pages système
- `GET /admin/errors/403` → `dashboard/page_403.html`
- `GET /admin/errors/404` → `dashboard/page_404.html`
- `GET /admin/errors/500` → `dashboard/page_500.html`

## Principes respectés
- Pas de refonte du design des pages système.
- Pas de conversion brutale des pages HTML en vues Laravel complètes.
- Préparation d’une base Laravel évolutive (auth/middleware/routes) compatible avec la transition progressive.

## Prochaine évolution recommandée
- Connecter le formulaire login legacy au `POST /admin/login` (ou créer une variante Blade strictement fidèle visuellement).
- Remplacer ensuite le couple username/password statique par table users + hash sécurisé.
