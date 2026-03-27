# V2 224 - System Role Protections (Hardcore)

## Mission Output
Protect critical system roles against unsafe modification or deletion.

## Target Roles
- `admin`
- `system`
- any role flagged `is_system=true`

## Protection Rules

### Deletion Protection
- System roles are non-deletable from UI and service layer.
- API/service calls attempting deletion must return controlled 403/422.

### Mutation Protection
- Core permission set of protected roles cannot be stripped by non-superadmin.
- Optional metadata updates (label/description) allowed with strict permission.

### Assignment Protection
- Prevent removal of last user that holds mandatory system role.
- Require explicit privileged permission for any change on protected roles.

### Defense In Depth
- Enforce protection in:
  - UI (buttons hidden/disabled),
  - controller validation,
  - service/domain layer constraints.

## Audit Requirements
- Log all attempted changes on protected roles (success/failure).
- Include actor, target role, action, decision reason.

## Tests
- Cannot delete protected role.
- Cannot remove critical permission from protected role without privilege.
- Cannot orphan system by removing last critical-assignee.
- Authorized metadata-only update succeeds.

## Result
Protected roles are hardened against accidental or malicious privilege collapse.
