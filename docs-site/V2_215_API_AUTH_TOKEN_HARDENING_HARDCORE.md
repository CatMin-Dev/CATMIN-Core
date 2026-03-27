# V2 215 - API Auth And Token Hardening (Hardcore)

## Mission Output
Harden authentication methods for internal and external API flows.

## Current Baseline
- Internal API token support: both header (`X-Catmin-Token`) and query parameter (`?token=`).
- Token is passed in config and validated with `hash_equals` (timing-attack safe).
- Public/protected endpoints are marked but not consistently enforced.

## Hardcore Implementation

### Internal API Token
- REMOVE query parameter support; header-only (`X-Catmin-Token`).
- Add token rotation support: generate new token, deprecate old one with 7-day grace period.
- Add token audit log: every API call authenticated logs token source, endpoint, user context.
- Add token expiration policy: tokens never expire by default but can be manually revoked.

### Public vs. Protected Boundary
- Document and enforce: public endpoints do NOT require tokens; protected endpoints MUST require tokens.
- Add CI check: ensure new routes explicitly declare authorization policy.
- Add middleware: `api.public` (optional token), `api.protected` (mandatory token).

### Token Transport Hardening
- Validate: token header is present and well-formed.
- Validate: token value contains only safe characters (alphanumeric + underscore/dash).
- Reject: tokens passed in body, URL path, or other vectors.

### Webhook Outgoing Auth
- Add support for HMAC-SHA256 signing (already partially present).
- Validate: signature header matches body hash with webhook secret.
- Log: all webhook deliveries with signature validation result.

## Test Requirements
- Test internal API rejects requests without token header.
- Test internal API rejects requests with invalid token.
- Test query-string token is rejected (migration).
- Test public endpoint works without authentication.
- Test HMAC signature verification on webhook payloads.

## Result
CATMIN API authentication is now hardened against token leakage and misuse.
