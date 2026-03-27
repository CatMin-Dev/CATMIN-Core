# V2 211 - CSRF Hardening

## Objective
Validate and harden CSRF coverage for CATMIN admin workflows.

## Audit Result
- State-changing Blade forms across admin/modules include `@csrf` in current baseline.
- No JS `fetch`/XHR mutation path detected in current resources/app/modules scripts.
- Web admin routes are under `web` middleware stack, so framework CSRF protection is active.
- Logger search form (`GET`) does not require CSRF and is expected.

## Exceptions / Non-Applicable Cases
- Internal token API (`/api/internal/*`) is stateless and not CSRF-based by design.
- Public incoming webhook endpoint is token-authenticated and not session CSRF-based.

## Hardening Decisions
1. Keep all state-changing admin endpoints strictly in `web` middleware context.
2. Document CSRF non-applicable cases (internal token API and incoming webhook).
3. Standardize operator message for expired sessions / invalid token situations.

## 419 Handling Policy
- User-facing message: session expired or invalid token, ask refresh and retry.
- Do not expose stack traces or internal token details in UI.
- Log event with route/method metadata (without payload secrets).

## Regression Checklist
- POST/PUT/PATCH/DELETE forms in modules remain with `@csrf`.
- Admin logout form keeps `@csrf`.
- New async write calls must include CSRF token header if introduced.

## Outcome
CSRF baseline is currently clean for existing server-rendered admin flows. Remaining work is governance and standardized 419 behavior as part of broader hardening.
