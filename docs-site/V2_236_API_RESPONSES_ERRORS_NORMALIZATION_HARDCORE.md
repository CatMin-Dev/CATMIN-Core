# V2 236 - API Responses And Errors Normalization (Hardcore)

## Mission Output
Normalize external API payload contracts for success and errors.

## Success Contract
- `success: true`
- `data: ...`
- `meta: ...` (pagination, version)
- `request_id`

## Error Contract
- `success: false`
- `error.code`
- `error.message`
- `error.details` (optional, safe)
- `request_id`

## Status Mapping
- 400 malformed request
- 401 authentication required/invalid
- 403 forbidden by scope/permission
- 404 resource not found
- 409 conflict
- 422 validation
- 429 rate limit
- 500 internal error (generic message)

## Governance
- Apply one formatter/exception mapping layer across external API.
- Keep backward-compatible payload fields within same version.

## Result
Consumers get stable, predictable, and well-structured API behavior.
