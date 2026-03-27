# V2 209 - Existing API And External Exposure Audit (Hardcore)

## Scope
Targeted audit of API exposure boundaries and external attack surface.

## Evidence Snapshot
- Internal API prefix: `/api/internal`.
- Public read endpoints: settings/public, pages/published, articles/published.
- Protected diagnostics endpoints use `catmin.api-token` middleware.
- Token middleware accepts header and query parameter fallback.
- Public webhook incoming endpoint exists at `/webhooks/incoming/{token}`.

## Safe Zones
- Protected internal diagnostics are isolated behind token middleware.
- Webhook incoming endpoint uses token verification with `hash_equals`.
- Admin CRUD surfaces are mostly grouped behind `web + catmin.admin`.

## Incomplete Zones
- Query-string token support increases leakage risk (logs, browser history, proxies).
- Public endpoints do not show explicit per-endpoint rate limiting in current routing.
- Endpoint inventory and boundary policy are not codified in dedicated security docs.

## Risks
1. Secret exposure via URL token transport.
2. Brute-force/noise risk on public webhook endpoint without explicit throttling.
3. Endpoint sprawl risk as modules/addons add routes dynamically.

## Route/Module Links
- `routes/api.php`.
- `app/Http/Middleware/EnsureCatminApiToken.php`.
- `modules/Webhooks/routes.php`.
- `modules/Webhooks/Controllers/WebhookIncomingController.php`.

## Corrective Plan (V2)
- Make API token header-only (`X-Catmin-Token`), remove `?token=` fallback.
- Add throttle middleware for public/internal externally reachable routes.
- Add route inventory CI check for middleware expectations by route class.
- Document public vs protected endpoint contract in security appendix.

## Immediate Priority
Header-only token auth and route throttling should be handled first.
