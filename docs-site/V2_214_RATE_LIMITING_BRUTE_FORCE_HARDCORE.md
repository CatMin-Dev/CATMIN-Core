# V2 214 - Rate Limiting And Anti-Brute Force (Hardcore)

## Mission Output
Implement rate limiting and brute-force protection across CATMIN entry points.

## Current Baseline
- No explicit brute-force throttling detected at login endpoint.
- No rate limiting middleware on admin or internal API endpoints.
- Public webhook endpoint has token-based protection but no throttling.

## Hardcore Implementation

### Login Endpoint Protection
- Throttle: max 5 attempts per minute per IP.
- Lockout: temporary account lock 15 minutes after 5 failed attempts.
- Response: return 429 Too Many Requests with retry-after header.
- Log: every throttled attempt with IP, username attempted, timestamp.

### Internal API Protection
- Throttle: max 100 requests per minute per token.
- Throttle: max 1000 requests per hour per token.
- Response: return 429 with rate-limit headers.
- Log: API throttle events for audit.

### Public Webhook Endpoint Protection
- Throttle: max 10 webhook deliveries per minute per URL destination.
- Throttle: max 100 webhooks per hour per origin.
- Response: return 429 on threshold.
- Log: throttle and backoff events.

### Implementation
- Create middleware `throttle.login`, `throttle.api`, `throttle.webhook`.
- Use Laravel's `Illuminate\Cache\RateLimiter` service.
- Store throttle state in cache (Redis recommended for production).
- Add cache cleanup for expired throttle keys.

## Test Requirements
- Test login endpoint rejects 6th attempt within throttle window.
- Test lockout message is shown after 5 consecutive failures.
- Test lockout automatically expires after 15 minutes.
- Test API endpoints return 429 when quota exceeded.
- Test rate-limit headers are present in responses.

## Result
CATMIN now has production-grade brute-force and rate-limiting protection in place.
