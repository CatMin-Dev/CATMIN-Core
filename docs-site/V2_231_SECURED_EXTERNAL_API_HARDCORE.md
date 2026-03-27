# V2 231 - Secured External API (Hardcore)

## Mission Output
Deliver production-grade external API architecture, auth model, and standards.

## Architecture
- Split surfaces:
  - Internal: `/api/internal/*` (ops diagnostics).
  - External: `/api/v1/*` (integrations/clients).
- Explicit contract per endpoint: public, authenticated, privileged.

## External Authentication
- API key model:
  - key id + secret,
  - secret displayed once then hashed at rest,
  - scopes and optional expiration,
  - revocation support.
- Optional OAuth2/JWT later, not required for baseline.

## Endpoint Families
- `GET /api/v1/settings/public`
- `GET /api/v1/pages`
- `GET /api/v1/articles`
- `GET /api/v1/shop/*` (if enabled)
- `POST /api/v1/webhooks/test` (scoped)

## Response Standard
- Success envelope: `success`, `data`, `meta`, `request_id`.
- Error envelope: `success=false`, `error.code`, `error.message`, `request_id`.
- Stable pagination schema.

## Security Controls
- Header-only credentials.
- Rate limiting by key + IP.
- Input validation and output filtering.
- Full request audit with redaction.

## Documentation
- Endpoint catalog with scopes, params, examples.
- Versioning and deprecation policy.

## Result
A complete external API baseline that is secure, versioned, and integration-ready.
