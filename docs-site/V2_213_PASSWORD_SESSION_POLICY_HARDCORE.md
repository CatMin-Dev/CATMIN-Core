# V2 213 - Password And Session Policy (Hardcore)

## Mission Output
Comprehensive password and session security implementation with strict policy enforcement and audit coverage.

## Implementation Requirements

### Password Strength Enforcement
- User-facing password creation/change must enforce: 
  - Minimum 8 characters.
  - At least one uppercase letter.
  - At least one lowercase letter.
  - At least one number.
  - At least one symbol from: `!@#$%^&*()_+-=[]{}|;:'",.<>?/`
- Validation must happen on server-side (no client-side only trust).
- Common passwords must be rejected (use Laravel's `current_password` rule or custom blacklist).

### Password Hashing
- Algorithm: Argon2id (Laravel default, do not change).
- Parameters: Laravel defaults acceptable (cost=12, memory=19456, parallelism=2).
- Add feature test asserting password verification and timing-attack resistance.

### Session Hardening
- Extend session table with:
  - `user_ip` (capture at login to detect anomalies).
  - `user_agent` (capture user-agent fingerprint).
  - `last_activity` (already exists in sessions table).
- Add session fixation protection:
  - Invalidate all other sessions on sensitive password change.
  - Offer "logout all other sessions" option in security settings UI.

### Session Timeout
- Hard timeout: 120 minutes.
- Soft warning: 110 minutes (optional notify user, ask for confirmation).
- On timeout: clear session, redirect to login with flash message "Session expired, please log in again".

### Audit Trail
- Log password changes with: user_id, changed_by_id, timestamp, IP, user-agent.
- Log session creation: user_id, IP, user-agent, timestamp, login method.
- Log session termination: user_id, reason (logout/timeout/invalidate), timestamp.
- Log anomalous session detection: same user_id, different IP/user-agent, action taken.

### Infrastructure
- Create migration for session enhancements (user_ip, user_agent).
- Create migration for password audit log table.
- Add policies in session middleware to detect hijacking attempts.
- Add CLI command to clean expired sessions and old audit records.

## Data Model (Concrete)
```
passwords_audit:
  - id
  - user_id
  - changed_by_id
  - ip_address
  - user_agent
  - timestamp
  - reason (manual change, forced reset, etc.)

sessions (extended):
  - id (existing)
  - user_id (existing)
  - ip_address (NEW)
  - user_agent (NEW)
  - payload (existing)
  - last_activity (existing)
```

## Test Requirements
- Test password strength validation rejects weak passwords.
- Test password verification succeeds with correct password.
- Test session invalidation on logout clears all stored data.
- Test session regeneration changes session ID.
- Test concurrent session cleanup (all sessions invalidated on forced password change).
- Test audit log entries are created for all password/session events.
- Test session timeout and redirect on expired session.

## Rollout Readiness
- Feature flag or gradual rollout not needed (core security).
- All operators must be trained on new session/password policies.
- Documentation must include edge cases and troubleshooting (lost password, session bugs, etc.).

## Result
A robust, auditable, and compliant password/session foundation ready for enterprise deployment in V2.
