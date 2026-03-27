# V2 231 - Secured External API

## Objective
Evolve CATMIN from internal-only API to a clean, secure external API baseline.

## Target Architecture
- Keep current internal API for private diagnostics.
- Add external API namespace with explicit versioning: `/api/v1/...`.
- Separate public resources from authenticated resources.

## Authentication Strategy
- External auth via API keys or bearer tokens scoped per integration.
- Keys stored hashed, never logged in clear text.
- Per-key scopes define accessible endpoint families.

## Endpoint Baseline
- Public filtered settings.
- Published content (pages/articles/blog).
- Optional shop read endpoints.
- Integration utility endpoints where relevant.

## Security Baseline
- TLS-only usage.
- Standardized 401/403/429 error behavior.
- Request validation and pagination guards.
- Audit logging of external calls.

## Versioning
- URI versioning (`/api/v1`).
- Backward compatibility policy for deprecations.
- Changelog per API version.

## Result
CATMIN obtains a practical and secure external API foundation ready for partner integrations.
