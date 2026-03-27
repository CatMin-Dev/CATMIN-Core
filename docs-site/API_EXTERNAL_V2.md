# API Externe CATMIN v2

## Vue d'ensemble

CATMIN expose une API externe versionnee sous `/api/v2`.

- Reponses normalisees: `success`, `data`, `error`, `meta`
- Version explicite dans `meta.api_version`
- Journalisation des acces via l'evenement `api.external.access`
- Rate limiting dedie (`catmin-external-api`)

## Authentification

Certains endpoints sont publics, d'autres proteges par cle API.

Modes supportes:

- `Authorization: Bearer <api_key>`
- `X-Catmin-Key: <api_key>`

Les cles sont stockees en hash SHA-256 (`api_keys.key_hash`).

### Generer une cle API

```bash
php artisan catmin:api:key-generate "integration-client" --scope=external.read --expires-days=365
```

Important: la cle brute est affichee une seule fois.

## Endpoints publics

- `GET /api/v2/health`
- `GET /api/v2/version`
- `GET /api/v2/settings/public`
- `GET /api/v2/pages/published`
- `GET /api/v2/articles/published`
- `GET /api/v2/shop/products`

## Endpoints proteges

- `GET /api/v2/system/status` (scope `external.read`)

## Format des erreurs

Exemple 401:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "unauthorized",
    "message": "Missing API key.",
    "details": []
  },
  "meta": {
    "api_version": "v2",
    "timestamp": "2026-03-27T20:00:00+00:00"
  }
}
```

## Webhooks relies aux evenements reels

Le module Webhooks ecoute maintenant les evenements CatminEventBus:

- `user.created`
- `user.updated`
- `user.deleted`
- `page.published`
- `page.updated`
- `article.published`
- `article.updated`
- `settings.updated`

L'admin affiche l'etat de la derniere livraison (`last_delivery_status`, `last_delivery_error`, `last_delivery_at`).

## Verification manuelle rapide

```bash
# Public
curl -s http://catmin.local/api/v2/version | jq

# Protected (sans cle)
curl -s -i http://catmin.local/api/v2/system/status

# Protected (avec cle)
curl -s -H "Authorization: Bearer <API_KEY>" http://catmin.local/api/v2/system/status | jq
```
