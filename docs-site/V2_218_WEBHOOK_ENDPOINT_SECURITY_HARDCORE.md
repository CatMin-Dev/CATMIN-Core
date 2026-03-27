# V2 218 - Webhook Endpoint Security (Hardcore)

## Mission Output
Harden webhook incoming and outgoing endpoints against abuse and manipulation.

## Current Baseline
- Incoming webhook endpoint uses token-based verification (`hash_equals`).
- Outgoing webhooks include HMAC-SHA256 signatures.
- Rate limiting not yet applied to webhook endpoints.

## Hardcore Implementation

### Incoming Webhook Protection
- Token validation: token is checked via `hash_equals` (good).
- IP allowlisting: optional per-webhook (capture webhook origin IP, allow-list setting).
- Payload size limits: max 10MB per incoming webhook (prevent memory exhaustion).
- Timeout: webhook processing timeout 30 seconds (async dispatch to queue preferred).
- Request signature: validate X-Webhook-Signature header against payload hash.

### Outgoing Webhook Protection
- Signing: all outgoing webhooks are HMAC-SHA256 signed.
- Validation: receiving endpoint MUST validate signature before processing.
- Retries: exponential backoff 1s → 2s → 5s → 10s (max 4 retries).
- Timeout: 10 second timeout per delivery attempt.
- TLS only: reject http:// webhook URLs, require https:// (configurable for dev/test).

### Audit Trail
- Log every incoming webhook: timestamp, IP, token, event type, HTTP status, response time.
- Log every outgoing webhook delivery: destination, signature, HTTP status, latency, retry count.
- Log webhook failures with full error context for troubleshooting.

### Implementation
- Add middleware `webhook.validate-signature` for incoming endpoints.
- Add IP allowlist table to webhooks database model.
- Implement async queue job for webhook delivery with retries (already using Laravel queue).
- Add webhook debug/test endpoint (development only).

## Test Requirements
- Test incoming webhook rejects invalid token.
- Test incoming webhook rejects oversized payload.
- Test outgoing webhook includes valid HMAC signature.
- Test rejected HTTP URLs are not processed.
- Test audit logs are created for all webhook events.

## Result
CATMIN webhook infrastructure is now hardened against abuse and injection attacks.
