# Admin Sessions, 2FA, And Access Policies

## Overview

Prompt 327 introduces account-level 2FA and active-session governance for CATMIN admin users.

## Per-Account 2FA

2FA is now configured per admin account using database-backed fields:

- `admin_users.two_factor_enabled`
- `admin_users.two_factor_secret` (encrypted)
- `admin_users.two_factor_recovery_codes` (SHA-256 hashes)

### Setup Flow

1. Open `Admin > 2FA` (`admin.2fa.setup`).
2. Scan QR code in a TOTP app.
3. Confirm with OTP to activate.
4. Save generated recovery codes.

### Verify Flow

At login, if account 2FA is enabled, CATMIN sets a pending 2FA state and redirects to `admin.2fa.verify`.

Accepted second factors:

- OTP (6 digits)
- Recovery code (single-use, consumed on success)

## Admin Session Tracking

CATMIN now persists active admin sessions in `admin_sessions`:

- Session id
- Admin user id
- IP address
- User agent
- Last activity timestamp
- Revocation timestamp

Session lifecycle:

- Registered on successful full login.
- Touched on each authenticated request.
- Revoked on logout.
- Forced logout if revoked from another session.

## Session Access Policies

Configured in `config/catmin.php`:

- Absolute timeout remains enforced in middleware.
- Optional idle timeout via `CATMIN_ADMIN_SESSION_IDLE_TIMEOUT`.

If idle timeout is exceeded, current session is revoked and user is redirected to login.

## Admin Session Management UI

`admin.sessions.index` lists active sessions for the current account.

Available actions:

- Revoke a specific session
- Revoke all other sessions

Routes:

- `GET admin/sessions`
- `POST admin/sessions/revoke`
- `POST admin/sessions/revoke-others`

## Security Logging

Audit events include:

- 2FA challenge/failed/verified
- Recovery code used/regenerated
- Session revoked/revoke-others

## Notes

- Existing global `.env` 2FA keys are no longer required for normal operation.
- Recovery codes are shown once and never stored in plaintext.
