# API Externe CATMIN v1

## Vue d'ensemble

L'API externe v1 expose des endpoints metier securises sous `/api/v1` pour:

- Pages
- Articles
- Media assets
- Shop products

Chaque ressource propose un CRUD complet avec pagination et filtres coherents.

## Authentification

Deux modes sont supportes:

- API Key: `Authorization: Bearer <API_KEY>` ou header `X-Catmin-Key`
- Token interne: header `X-Catmin-Token` (si `CATMIN_API_INTERNAL_TOKEN` est configure)

## Scopes

Scopes attendus par ressource:

- `pages.read`, `pages.write`
- `articles.read`, `articles.write`
- `media.read`, `media.write`
- `shop.read`, `shop.write`

Le token interne contourne les scopes (usage integration interne de confiance).

## Rate limiting

Toutes les routes v1 sont protegees par `throttle:catmin-external-api`.
La limite est configurable via `CATMIN_EXTERNAL_API_RATE_LIMIT`.

## Endpoints

### Pages

- `GET /api/v1/pages`
- `GET /api/v1/pages/{id}`
- `POST /api/v1/pages`
- `PUT|PATCH /api/v1/pages/{id}`
- `DELETE /api/v1/pages/{id}`

### Articles

- `GET /api/v1/articles`
- `GET /api/v1/articles/{id}`
- `POST /api/v1/articles`
- `PUT|PATCH /api/v1/articles/{id}`
- `DELETE /api/v1/articles/{id}`

### Media

- `GET /api/v1/media`
- `GET /api/v1/media/{id}`
- `POST /api/v1/media`
- `PUT|PATCH /api/v1/media/{id}`
- `DELETE /api/v1/media/{id}`

### Shop products

- `GET /api/v1/shop/products`
- `GET /api/v1/shop/products/{id}`
- `POST /api/v1/shop/products`
- `PUT|PATCH /api/v1/shop/products/{id}`
- `DELETE /api/v1/shop/products/{id}`

## Pagination et filtres

Parametres communs sur les endpoints listing:

- `per_page` (1..100)
- `page`
- `q` (recherche textuelle)
- `sort_by`
- `sort_dir` (`asc|desc`)

Filtres supplementaires par ressource (selon colonnes):

- pages: `status`, `slug`
- articles: `status`, `content_type`, `slug`
- media: `disk`, `mime_type`, `extension`
- shop products: `status`, `visibility`, `slug`

## Logs et audit

- Chaque requete v1 est loggee via `api.external.access`.
- Les ecritures CRUD sont auditees via:
  - `api.v1.pages.created|updated|deleted`
  - `api.v1.articles.created|updated|deleted`
  - `api.v1.media.created|updated|deleted`
  - `api.v1.shop_products.created|updated|deleted`

## Webhooks integres

Chaque write CRUD declenche un webhook sortant si abonnement actif:

- pages: `page.created|updated|deleted`
- articles: `article.created|updated|deleted`
- media: `media.created|updated|deleted`
- shop: `shop.product.created|updated|deleted`

## Exemples

```bash
# Lecture pages
curl -s \
  -H "Authorization: Bearer <API_KEY>" \
  "http://catmin.local/api/v1/pages?status=published&per_page=20"

# Creation article
curl -s -X POST \
  -H "Authorization: Bearer <API_KEY_WRITE>" \
  -H "Content-Type: application/json" \
  -d '{"title":"Nouveau","slug":"nouveau","status":"draft"}' \
  "http://catmin.local/api/v1/articles"
```
