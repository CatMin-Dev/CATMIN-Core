# V2 217 - Error And Sensitive Message Hardening (Hardcore)

## Mission Output
Prevent sensitive information leakage via error messages and logs.

## Current Baseline
- Exception handler logs errors with context (query, method, IP).
- No explicit scrubbing of sensitive values from error output.
- Stack traces may be visible in development/debug modes.

## Hardcore Implementation

### Production Error Pages
- In production (APP_DEBUG=false): show generic "Something went wrong" message, no technical details.
- Log full error details to file/logging service (not visible to user).
- Include request ID in user-facing message, let operator look up details via logs.

### Error Message Redaction
- Never expose:
  - Database query contents.
  - SQL schema/table names.
  - File system paths.
  - API credentials, tokens, or secrets.
  - Third-party service details.
- Always scrub exception messages before output.

### Validation Error Messages
- Custom validation messages must not reveal system internals.
- Example (bad): "Email validation failed because SMTP server 192.168.1.50 is down".
- Example (good): "Email validation failed. Please try again or contact support".

### Log Entry Redaction
- Redact password, token, api_key, secret, authorization fields in all logged payloads.
- Redact full URLs if they contain query parameters with sensitive values.
- Test redaction on nested/complex JSON structures.

### Implementation
- Extend exception handler with sanitization logic.
- Create SensitiveDataRedactor service.
- Add tests asserting no secrets appear in error responses.
- Configure APP_DEBUG=false in staging/production environments.

## Test Requirements
- Test development env shows detailed errors; prod env shows generic message.
- Test passwords are redacted in logs.
- Test URLs with tokens are sanitized in error output.
- Test custom validation messages contain no internal details.

## Result
CATMIN error handling now protects sensitive data from both users and attackers.
