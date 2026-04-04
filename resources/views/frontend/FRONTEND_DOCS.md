# CATMIN — Frontend public — Documentation

## Architecture

Le frontend public CATMIN est une couche de rendu légère, directement intégrée dans l'application Laravel principale. Il n'utilise **aucune dépendance frontend lourde** et ne partage aucun asset avec l'administration.

### Isolation CSS/JS

| Couche | CSS | JS |
|--------|-----|----|
| Admin | `resources/css/admin.css` (Vite) | `resources/js/admin.js` (Vite) |
| Public | Bootstrap 5 **CDN** + `resources/css/frontend.css` (Vite) | Bootstrap Bundle **CDN** + `resources/js/frontend.js` (Vite) |

Le layout `resources/views/frontend/layouts/base.blade.php` charge exclusivement le CSS/JS public. Le bundle admin n'est jamais inclus dans les pages publiques.

---

## Routes publiques

| Route | URL | Nom |
|-------|-----|-----|
| Accueil | `/` et `/site` | `frontend.root`, `frontend.home` |
| Page statique | `/page/{slug}` | `frontend.page` |
| Listing articles | `/articles` | `frontend.articles.index` |
| Détail article | `/articles/{slug}` | `frontend.articles.show` |
| Contact | `/contact` | `frontend.contact` |
| Envoi contact | POST `/contact` | `frontend.contact.send` |
| Carte | `/carte` | `frontend.map` |

Toutes ces routes passent par le middleware `catmin.frontend.available` qui redirige vers la page de maintenance si le site est en mode maintenance.

---

## Services

### `FrontendResolverService`

Centralise la résolution du contexte public :

```php
$resolver = app(FrontendResolverService::class);

$resolver->context();               // array: site_name, site_url, ...
$resolver->menu('primary');         // Collection du menu principal
$resolver->seo('pages', $page->id, ['title' => '...']); // payload SEO
$resolver->homePage();              // Page slug=home publiée ou null
$resolver->siteName();              // string
$resolver->siteUrl();               // string
```

### `PublicContentRenderService`

Rendu sécurisé du contenu HTML CMS :

```php
$renderer = app(PublicContentRenderService::class);

$renderer->render($htmlContent);      // injecte les blocs {{ block:slug }}
$renderer->excerpt($content, 200);    // texte court sans balises
$renderer->readingTime($content);     // estimation en minutes
```

---

## Sécurité

- **Seuls les contenus publiés sont accessibles** : `status = 'published'` ET `published_at <= now()`
- Aucun contenu draft ou schedulé ne peut être atteint via les routes publiques
- Le formulaire de contact est protégé par CSRF et rate-limiting (`catmin-contact`: 3 req/min/IP)
- Les emails de contact sont validés par `email:filter` (RFC 5321 strict)
- Le rendu HTML CMS utilise `{!! !!}` Blade avec un contenu issu de l'admin authentifié uniquement (pas d'input utilisateur)

---

## Étendre le rendu — ajouter un addon

Pour qu'un addon ajoute sa propre route publique, il suffit de définir dans son `routes.php` :

```php
use Illuminate\Support\Facades\Route;

Route::middleware('catmin.frontend.available')->group(function (): void {
    Route::get('/events', [YourEventController::class, 'index'])
        ->name('frontend.events.index');
});
```

L'addon peut réutiliser le layout :

```blade
@extends('frontend.layouts.base')
@section('content')
    ...
@endsection
```

---

## Preview admin vs page publique

| Contexte | Cible | Contenu |
|----------|-------|---------|
| Admin preview | `/admin/pages/{id}/preview` | Peut afficher les drafts (session admin requise) |
| Frontend public | `/page/{slug}` | Uniquement les contenus `published` + `published_at <= now()` |

---

## Layout custom

Pour brancher un layout différent (thème personnalisé), remplacer le template :

```
resources/views/frontend/layouts/base.blade.php
```

Ou surcharger via un addon en enregistrant un namespace de vue prioritaire dans son `AppServiceProvider`.

---

## Configuration

Fichier : `config/catmin_frontend.php`

| Clé | Défaut | Description |
|-----|--------|-------------|
| `bootstrap_css` | Bootstrap 5.3.3 CDN | URL du CSS Bootstrap externe |
| `bootstrap_js` | Bootstrap Bundle 5.3.3 CDN | URL du JS Bootstrap externe |
| `articles_per_page` | `12` | Articles par page dans le listing |
| `articles_prefix` | `articles` | Préfixe URL des articles |
| `contact_enabled` | `true` | Afficher le bouton contact dans la nav |
| `contact_to_email` | `CATMIN_CONTACT_EMAIL` env | Email de destination du formulaire |
| `contact_max_chars` | `2000` | Longueur max du message de contact |
| `map_enabled` | `true` | Activer la page `/carte` |
| `leaflet_css` / `leaflet_js` | Leaflet 1.9.4 CDN | Assets Leaflet |
| `map_default_lat/lng/zoom` | Paris, zoom 6 | Centre par défaut de la carte |
| `home_page_slug` | `home` | Slug de la page d'accueil |
