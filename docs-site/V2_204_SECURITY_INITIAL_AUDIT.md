# V2 204 - Security Initial Audit

## Scope
Initial security baseline review of CATMIN V2 codebase before hardening tasks.

## Method
- Reviewed entry routes and middleware layering in web and internal API flows.
- Reviewed auth, permission and session handling in admin middleware and controllers.
- Reviewed upload, webhook and logging modules for data exposure or abuse vectors.
- Reviewed form surface and CSRF coverage across admin and modules views.

## Current Security Baseline

### Authentication
- Admin auth is session based and implemented in `AuthController` with static credentials from config/env.
- Session is regenerated on login and invalidated on logout.
- No brute-force throttling or lockout currently visible at login endpoint.
- No second factor in current login flow.

### Authorization
- Route-level permission middleware exists (`catmin.permission`).
- Coverage is partial and heterogeneous: some module routes enforce fine-grained permissions, others rely only on `catmin.admin`.
- Navigation visibility is permission-filtered, but route-level checks remain the real control and should be systematic.

### CSRF
- Web admin forms mostly include `@csrf` and use web middleware.
- Internal API routes are token-protected and not session/CSRF based (expected for token auth).
- No explicit standardized 419 UX policy yet.

### Upload and Files
- Media uploads validate extension and max size in controller.
- Service writes through Laravel storage and avoids raw path moves.
- SVG and archive uploads are allowed by default, increasing review requirements.

### API Exposure
- Internal API has mixed exposure:
- public read endpoints for published/pages/settings.
- protected diagnostics endpoints via `catmin.api-token`.
- Token can be passed by query parameter, which is convenient but leak-prone in logs/proxies.

### Logging and Data Exposure
- `SystemLogService` redacts several sensitive keys (`password`, `token`, `secret`, etc.).
- Admin action logging stores route/method/url/ip and input key names (not values).
- Exception reporting stores exception metadata and request context; this is useful but should stay sanitized.

## Priority Risks (Initial)
1. Inconsistent permission middleware adoption across modules routes.
2. API token accepted via query string (`?token=`) and therefore exposed in logs/history.
3. No login throttling/lockout for admin authentication.
4. Allowed upload extensions include high-risk formats (SVG, ZIP) without dedicated deep inspection.
5. CSRF error handling is not standardized from UX/support perspective (419 flow).

## Recommended V2 Security Workstreams

### Workstream A - Auth hardening
- Add login rate limiting and progressive lockout.
- Add baseline 2FA for admin accounts.

### Workstream B - Authorization hardening
- Enforce `catmin.permission` on all state-changing admin module endpoints.
- Produce a route-permission matrix and close gaps.

### Workstream C - CSRF hardening
- Re-audit all state-changing forms and async calls.
- Standardize 419 handling and operator guidance.

### Workstream D - Upload hardening
- Restrict default allowed types by business need.
- Add stricter handling for SVG and archives.
- Add malware scanning hook or quarantine pipeline in next phase.

### Workstream E - API hardening
- Remove token from query support and keep header-only auth.
- Add explicit route-level docs for public/internal boundaries.

### Workstream F - Logging hardening
- Ensure secrets redaction also covers nested/custom keys.
- Define retention and access policy for audit/application logs.

## Conclusion
The baseline is workable and already contains useful safeguards (session regeneration, CSRF in forms, token middleware, redaction). The next V2 cycle should focus first on auth/permission consistency and API token transport hardening, then on upload and operational security improvements.
