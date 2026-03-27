# V2 234 - External API Rate Limiting (Hardcore)

## Mission Output
Implement robust external API rate limiting and abuse resistance.

## Policy
- Default limits by API key and by IP.
- Burst and sustained windows (per-minute and per-hour).
- Endpoint-specific overrides for expensive routes.

## Enforcement
- Return 429 with standard headers (`Retry-After`, remaining quota).
- Apply consistent policy across all external API endpoints.
- Keep internal API policy separate.

## Observability
- Log throttle events with key, route, IP, window.
- Expose aggregate rate-limit metrics in admin.

## Verification
- Manual and automated checks for threshold behavior.
- Confirm no false positives on normal usage.

## Result
External API usage is controlled, predictable, and resilient to burst abuse.
