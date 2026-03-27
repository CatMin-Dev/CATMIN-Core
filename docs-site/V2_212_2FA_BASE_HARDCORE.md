# V2 212 - 2FA Base (Hardcore)

## Mission Output
Hardcore-grade 2FA implementation blueprint aligned with current CATMIN architecture.

## Architecture Constraint (Critical)
Current admin login uses static config credentials, not a first-class account provider. Real per-user 2FA requires identity-backed admin authentication.

## Recommended Strategy

### Step 1 - Identity grounding
- Move admin auth source to account-backed identity (existing `users` domain or dedicated admin identity table).
- Keep backward compatibility window for emergency env admin only.

### Step 2 - 2FA primitives
- TOTP secret generation and secure storage (encrypted).
- 6-digit code verification with clock-skew tolerance.
- Recovery code generation (single-use, hashed).

### Step 3 - Login state machine
- Stage A: primary credential validated.
- Stage B: if 2FA enabled -> pending challenge state.
- Stage C: successful TOTP/recovery -> full session grant.
- Stage D: fail/timeout -> revoke pending auth state.

### Step 4 - Operational controls
- Verification throttling and temporary lock on repeated failures.
- Audit logging for enable/disable/recovery use/challenge failures.
- Admin-facing emergency reset workflow with strict permission.

## Required Screens
- 2FA status in admin profile/security section.
- Enable/setup screen (QR + manual key + confirm code).
- Challenge screen during login.
- Recovery code management (regenerate/revoke).

## Data Contract (Hardcore)
- `two_factor_enabled` (bool)
- `two_factor_secret_enc` (encrypted text)
- `two_factor_confirmed_at` (datetime)
- `two_factor_recovery_codes_hash` (json)
- `two_factor_last_used_at` (datetime, optional)
- `two_factor_failed_attempts` (int, optional)
- `two_factor_locked_until` (datetime, optional)

## Security Controls Checklist
- Secrets never appear in logs or exception traces.
- Recovery codes shown only once at generation time.
- Mandatory session regeneration on successful 2FA challenge.
- Device/session trust should be explicit and opt-in only (future).
- 2FA events auditable via logger module.

## Role/Policy Readiness
- Data model and middleware should allow mandatory 2FA by role later.
- Policy flag can be introduced after baseline user-enabled rollout.

## Final Recommendation
Implement 2FA after switching admin auth to identity-backed accounts. Under current static-credential flow, only a global/shared OTP is possible, which is below expected security quality for CATMIN V2.
