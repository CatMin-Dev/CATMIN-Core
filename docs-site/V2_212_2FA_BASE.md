# V2 212 - 2FA Base

## Objective
Define and prepare a first reliable 2FA foundation for CATMIN admin accounts.

## Current Baseline
- Current admin auth is config/env credential based (`catmin.admin.username/password`).
- No per-user admin authentication provider is currently wired in this flow.
- No existing 2FA/OTP/TOTP implementation in codebase.

## Chosen 2FA Mode (V2 Baseline)
TOTP (RFC 6238) with authenticator app, plus recovery codes.

## Why This Mode
- Offline capable and standard for admin panels.
- No dependency on SMS or external provider.
- Compatible with future per-role enforcement.

## Proposed Workflow
1. Admin logs in with username/password.
2. If 2FA enabled for account, redirect to code verification step.
3. Accept 6-digit TOTP or one recovery code.
4. Mark session as fully verified only after successful second step.

## Setup Flow (Target)
1. Generate secret key.
2. Show QR/otpauth URI + manual fallback key.
3. Require one valid code to confirm activation.
4. Generate one-time recovery codes and show/download once.

## Data Model (Target)
- `two_factor_enabled` boolean.
- `two_factor_secret` encrypted string.
- `two_factor_recovery_codes` encrypted JSON array (hashed at rest preferred).
- `two_factor_confirmed_at` datetime.
- Optional future: `two_factor_enforced` by role/policy.

## Session/Auth Impact
- Introduce intermediate auth state: password OK but 2FA pending.
- Rotate session after full auth completion.
- Invalidate pending state on logout or timeout.

## Security Requirements
- Never log secrets, OTP values or recovery codes.
- Recovery codes are single-use and regenerated on reset.
- Throttle verification attempts and lock temporarily on abuse.

## UX Requirements
- Clear setup wizard and recovery code acknowledgement step.
- Clear error handling for expired/invalid codes.
- Backup path if authenticator device lost (recovery codes).

## Outcome
A concrete 2FA baseline design is defined and aligned with CATMIN constraints. Next step is implementation once admin auth provider is moved from static credentials to account-backed flow.
