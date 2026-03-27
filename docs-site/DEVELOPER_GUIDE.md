# Guide Developpeur CATMIN V1

Ce guide explique comment CATMIN est organise aujourd'hui, avec des exemples concrets pour commencer vite.

## 1. Vue d'ensemble du noyau CATMIN

CATMIN repose sur Laravel, avec une separation simple:

- Admin: pages de gestion (dashboard, modules, settings, users, pages).
- Frontend libre: rendu public minimal, extensible.
- Modules: fonctionnalites metier chargees dynamiquement.

Dossiers importants:

- `app/` logique noyau Laravel (controllers, services, helpers)
- `modules/` modules CATMIN (Core, Users, Settings, Pages...)
- `routes/web.php` routes web globales
- `resources/views/` vues Blade (admin + frontend)
- `docs-site/` documentation developpeur

## 2. Systeme de modules

Chaque module a une structure de base:

- `module.json` metadonnees module
- `routes.php` routes du module
- `Controllers/` controleurs module
- `Services/` logique metier module
- `Models/` modeles module
- `Views/` vues Blade module
- `Migrations/` migrations module

Le chargement des routes modules est centralise dans le noyau via `ModuleLoader`.

## 3. Module Core

Role:

- socle minimal transversal
- informations systeme de base
- dependance racine des autres modules

Elements cles:

- `modules/Core/module.json`
- `modules/Core/Services/CoreFoundationService.php`
- `modules/Core/routes.php`

Exemple route core:

- `admin/core/status` renvoie un statut JSON de base.

## 4. Module Users

Role:

- gestion simple des utilisateurs admin
- roles associes (niveau base)
- activation/desactivation utilisateur

Routes utiles:

- `admin/users/manage`
- `admin/users/create`
- `admin/users/{id}/edit`
- `admin/users/{id}/toggle-active`
- `admin/roles/manage`

Fichiers cles:

- `modules/Users/Controllers/Admin/UserController.php`
- `modules/Users/Services/UsersAdminService.php`
- `modules/Users/Views/*.blade.php`

## 5. Module Settings

Role:

- administration des parametres globaux essentiels

Champs principaux manipules:

- `site.name`
- `site.url`
- `admin.path`
- `admin.theme`
- options generales frontend

Fichiers cles:

- `modules/Settings/Controllers/Admin/SettingsController.php`
- `modules/Settings/Services/SettingsAdminService.php`
- `modules/Settings/Views/index.blade.php`

## 6. Module Pages

Role:

- base de gestion des pages frontend

Champs retenus V1:

- `title`
- `slug`
- `content`
- `status` (`draft` ou `published`)
- `published_at`

Fichiers cles:

- `modules/Pages/Migrations/2026_03_27_000002_create_pages_table.php`
- `modules/Pages/Models/Page.php`
- `modules/Pages/Services/PagesAdminService.php`
- `modules/Pages/Controllers/Admin/PageController.php`
- `modules/Pages/Views/*.blade.php`

## 6.1 Module Media

Role:

- mediatheque reutilisable pour contenus editoriaux
- stockage des metadonnees fichier (nom, mime, taille, alt, caption)

Routes utiles:

- `admin/media/manage`
- `admin/media/create`
- `admin/media/{asset}/edit`

Exemple simple en Blade:

```php
$hero = media_asset(12);
$heroUrl = media_url($hero, asset('assets/img/placeholder.png'));
```

## 6.2 Module SEO

Role:

- stocker des metadonnees SEO reutilisables par module
- rattachement par `target_type` + `target_id`

Usage simple:

```php
$seo = seo_for('page', $page->id);
```

## 6.3 Module Articles (fusion News + Blog)

Role:

- unifier News et Blog dans une seule base admin
- differencier les types via `content_type` (`news` ou `article`)

Notes V1:

- modules legacy `News` et `Blog` conserves mais desactives
- les donnees existantes ont ete migrees vers `articles`
- dependances standard: `core`, `media`, `seo`

Statut legacy explicite:

- `News`: desactive, redirige fonctionnellement vers `Articles`
- `Blog`: desactive, redirige fonctionnellement vers `Articles`

Exemple helper frontend:

```php
$latestNews = news_items(5);
$latestPosts = blog_posts(5);
```

## 6.4 Module Mailer

Role:

- base d'envoi d'emails systeme
- point d'appui pour futures notifications/campagnes

Perimetre V1:

- pas de builder de campagne avance
- pas de suivi analytics email avance

## 6.5 Module Manager admin

Page admin dediee: `admin/modules`

Ce qui est affiche:

- nom, slug, version, type (systeme/optionnel)
- etat actif/desactive
- dependances
- disponibilite routes (via `ModuleLoader::getRoutesInfo()`)

Actions disponibles:

- activer un module
- desactiver un module (si securite OK)

## 6.6 Activation, dependances et securites (V1)

Regles appliquees:

- `core` est protege et non desactivable
- impossible d'activer un module si une dependance est absente/desactivee
- impossible de desactiver un module requis par un autre module actif
- seuls les modules en etat coherent sont consideres "enabled" effectivement

Dependances minimales forcees (fallback V1):

- `pages` -> `core`, `seo`
- `news` -> `core`, `media`, `seo`
- `blog` -> `core`, `media`, `seo`
- `articles` -> `core`, `media`, `seo`

Objectif:

- eviter les etats incoherents evidents
- garder une logique simple et maintenable

## 7. Navigation admin

La sidebar est generee par service, pas codee en dur partout.

- Source configuration: `config/catmin.php`
- Generation: `app/Services/AdminNavigationService.php`

Comportement:

- les items sont filtres selon modules actifs
- un item peut declarer son module via `module`, `match_module` ou `parameters.module`
- les routes inexistantes sont ignorees pour eviter les liens casses

## 8. Pattern CRUD CATMIN

Pattern unifie pour modules admin:

- listing (index)
- create
- edit
- show (optionnel)
- action de sortie (delete, archive, toggle selon besoin)

Composants UI reutilisables:

- `x-admin.crud.page-header`
- `x-admin.crud.flash-messages`
- `x-admin.crud.table-card`

Objectif: garder la meme experience visuelle entre modules.

## 9. Helpers utiles

Helpers disponibles dans `app/Helpers/CatminHelper.php`:

- `setting('cle', 'fallback')`
- `admin_url('route_name')`
- `admin_url_safe('route_name')`
- `page_by_slug('slug')`
- `frontend_context()`
- `news_items($limit = 5, $orderBy = 'published_at', $direction = 'desc')`
- `blog_posts($limit = 5, $orderBy = 'published_at', $direction = 'desc')`
- `media_asset($id)`
- `media_url($assetOrId, $fallback = null)`

Exemple simple:

```php
$siteName = setting('site.name', 'CATMIN');
$homePage = page_by_slug('home');
$adminLogin = admin_url_safe('login');
$latestNews = news_items(3);
$latestBlog = blog_posts(6, 'published_at', 'desc');
```

Limites V1:

- helpers editoriaux bases sur `articles` (`content_type = news|article`)
- uniquement les contenus publies sont retournes
- tri limite a `published_at`, `created_at`, `updated_at`, `title`
- limite bornee entre 1 et 100

## 10. Frontend libre de base

Etat V1:

- homepage frontend: `frontend.home`
- rendu d'une page par slug: route `frontend.page` (`/page/{slug}`)
- controleur dedie: `app/Http/Controllers/Frontend/PageController.php`

Principe:

- admin et frontend restent separes
- frontend sobre, sans framework front lourd
- evolutif vers theming/template plus tard

## 11. Ajouter un nouveau module (pas a pas)

1. Creer le dossier `modules/NomModule`.
2. Ajouter `module.json` (slug, depends, enabled, version, description).
3. Ajouter `routes.php` du module.
4. Ajouter `Models`, `Services`, `Controllers`, `Views`, `Migrations`.
5. Ajouter migration si besoin, puis lancer migration module.
6. Utiliser le pattern CRUD CATMIN pour les ecrans admin.
7. Ajouter les items de navigation dans `config/catmin.php`.
8. Verifier routes + rendu + erreurs.
9. Documenter le module dans `modules/NomModule/README.md`.

Exemple minimal de `module.json`:

```json
{
  "name": "Shop",
  "slug": "shop",
  "enabled": true,
  "version": "1.0.0",
  "depends": ["core", "users", "settings", "media"],
  "description": "Base module ecommerce"
}
```

## 12. Workflow recommande

Pour chaque lot de changement:

1. Implementer
2. Verifier (routes, rendu, erreurs)
3. Archiver le prompt dans `prompts/effectue/`
4. Commit propre dedie
5. Push GitHub

Ce workflow facilite la maintenance et la lecture de l'historique.
