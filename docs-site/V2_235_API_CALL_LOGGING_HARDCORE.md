# V2 235 - API Call Logging (Hardcore)

## Mission Output
Standardize and harden API call logging for external integration monitoring.

## Logged Data
- request id
- endpoint/method
- caller key id (not secret)
- response code
- latency
- payload size
- redacted context

## Logging Rules
- No sensitive payload values in logs.
- Keep enough detail for troubleshooting and audit.
- Distinguish auth failure, permission failure, validation failure.

## Storage And Access
- Use dedicated channel/table for API access logs.
- Support filtering by date, endpoint, key, status class.

## Verification
- Ensure each external API request produces one access log.
- Ensure secrets are always redacted.

## Result
API traffic becomes auditable and diagnosable without leaking secrets.
