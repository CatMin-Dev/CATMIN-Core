# V2 213 - Password And Session Policy

## Objective
Define and operationalize password and session management policies for CATMIN admin/user environments.

## Current Baseline

### Password Management
- Admin authentication currently uses static credentials from `CATMIN_ADMIN_USERNAME` and `CATMIN_ADMIN_PASSWORD` env variables.
- Frontend `User` model has password hashing implemented via Laravel's 'hashed' cast (Argon2id by default).
- No explicit password strength rules enforced for admin or user passwords.
- No password expiration or rotation policy currently in place.
- Password comparison uses `hash_equals()` for timing-attack resistance (good practice).

### Session Management
- Session driver: `database` (configurable via `SESSION_DRIVER`).
- Session lifetime: 120 minutes (configurable via `SESSION_LIFETIME`).
- Session cookies are HTTPOnly, SameSite=lax (secure defaults).
- Session lifecycle:
  - On login: session is regenerated and populated with auth flags (catmin_admin_authenticated, RBAC context).
  - On logout: session is invalidated and token is regenerated.
- CSRF token lifecycle is separate but aligned (CSRF middleware validates token per request).

## Decisions for V2

### Password Policy
- **Admin credentials:** remain static/env-based in current phase. Real per-user admin auth is deferred to future work.
- **User credentials (frontend):** enforce minimum 8 characters, require mix of uppercase/lowercase/numbers/symbols.
- **Hashing:** continue Argon2id (Laravel default, modern and strong).
- **No password rotation required** for V2 baseline, but leave door open for future policy.

### Session Policy
- **Lifetime:** keep 120 minutes as default; increase to 240 on production/enterprise request.
- **Concurrent sessions:** allow multiple active sessions per user (not enforced).
- **Idle timeout:** 120 minutes hard timeout; no soft inactivity warning in baseline.
- **Cookie flags:** maintain HTTPOnly=true, SameSite=lax (no change).
- **Regeneration:** continue regenerating on login and invalidating on logout.

## Audit Logging
- Password changes should log who changed what (via existing audit service).
- Session creations and destructions should be logged (already happening for admin login/logout).

## Documentation
- Add password policy rules to developer and operator docs.
- Document session lifecycle and timeout behavior.

## Result
A clear, pragmatic password and session policy baseline set for V2, ready for implementation.
