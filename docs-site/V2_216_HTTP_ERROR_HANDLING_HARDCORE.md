# V2 216 - Proper 401/403/419 Error Handling (Hardcore)

## Mission Output
Standardize and secure error responses for authentication/authorization/CSRF failures.

## Current State
- 403 errors are returned by permission middleware.
- 419 CSRF errors use Laravel default (bare error).
- 401 (Unauthorized) is not explicitly defined in current routing.

## Hardcore Implementation

### 401 Unauthorized
- When: internal API request lacks valid token or auth header.
- Response: JSON `{ "error": "Unauthorized", "message": "Authentication required" }`.
- No stack trace or internal details.
- HTTP status: 401.
- Log: audit-level log with endpoint, IP, reason.

### 403 Forbidden
- When: user lacks permission for requested resource/action.
- Response: JSON/HTML `{ "error": "Forbidden", "message": "You do not have permission to access this resource" }`.
- No mention of what permission was checked (security via obscurity).
- HTTP status: 403.
- Log: audit-level log with user, permission checked, IP.

### 419 Session Expired / CSRF
- When: CSRF token is invalid, missing, or session has expired.
- Response (web forms): redirect to login with flash message `"Session expired, please log in again"`.
- Response (AJAX): JSON `{ "error": "TokenMismatch", "message": "Session expired. Please refresh and retry." }`.
- HTTP status: 419.
- Log: audit-level log with session ID, CSRF status.

### Implementation
- Create exception handlers in `app/Exceptions/Handler.php`.
- Create Blade views for 401/403/419 (user-friendly messages).
- Add JSON response negotiation (content-type detection).
- Add test suite for all error codes and response formats.

## Test Requirements
- Test 401 returns valid JSON with no stack trace.
- Test 403 hides permission details.
- Test 419 on expired session redirects appropriately.
- Test CSRF mismatch returns correct error code and UI message.

## Result
CATMIN error handling is now consistent, secure, and operator-friendly.
