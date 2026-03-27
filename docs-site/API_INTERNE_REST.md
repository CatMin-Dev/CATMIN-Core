# API Interne REST CATMIN (V1)

## Objectif

Exposer une API interne sobre pour frontend/intégrations futures, sans ouvrir toute la base.

## Base URL

`/api/internal`

## Endpoints publics

### GET /api/internal/settings/public

Retourne uniquement les settings marqués `is_public=true`.

### GET /api/internal/pages/published

Retourne la liste des pages publiées (max 100).

### GET /api/internal/articles/published

Retourne la liste des articles publiés (max 100).

## Endpoints protégés (token)

Protection via middleware `catmin.api-token`.

Token attendu:

- Header `X-Catmin-Token: <token>`
- ou query `?token=<token>`
- Config: `CATMIN_API_INTERNAL_TOKEN` (env)

### GET /api/internal/system/status

Etat applicatif minimal (env, versions, heure).

### GET /api/internal/system/version

Version système CATMIN + version Laravel.

## Réponses

Format JSON sobre:

```json
{
  "success": true,
  "data": [...],
  "meta": {"count": 12}
}
```

## Notes sécurité V1

- Pas d'écriture (read-only).
- Pas d'exposition brute de tables sensibles.
- Endpoints système protégés.
- Extensible ensuite vers auth plus forte (Sanctum/JWT) si besoin.
