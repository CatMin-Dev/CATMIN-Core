# V2 226 - CRUD Action Permission Matrix (Hardcore)

## Mission Output
Define and enforce action-level permissions for all CRUD operations across modules.

## Matrix Convention
- `module.<slug>.list`
- `module.<slug>.create`
- `module.<slug>.edit`
- `module.<slug>.delete`
- `module.<slug>.config`

## Enforcement Targets
- Controllers and route declarations for all write actions (POST/PUT/PATCH/DELETE).
- UI action buttons (create/edit/delete/toggle/config).
- Background actions triggered from admin UI.

## Required Mapping
- Every CRUD route must map to exactly one explicit permission.
- Every sensitive non-CRUD action must map to a `config` or dedicated permission.

## Validation Strategy
- Build route-permission inventory and compare against matrix.
- Fail CI if a state-changing route has no permission middleware.
- Verify negative cases (forbidden) and positive cases (authorized).

## Tests
- Each module action denied without permission.
- Same action allowed with exact permission.
- UI hides button for missing permission.
- API/admin response code is 403 with controlled message.

## Documentation
- Provide one matrix table per module with route names and required permission keys.

## Result
CRUD authorization becomes deterministic, auditable and resistant to permission drift.
