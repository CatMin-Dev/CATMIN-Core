# V2 221 - Complete Roles CRUD (Hardcore)

## Mission Output
Production-grade role management with full feature set and enterprise controls.

## Data Model
```
roles:
  - id
  - name (unique, 2-64 chars)
  - description (nullable, 500 chars max)
  - is_system (boolean, protected if true)
  - created_at
  - updated_at

role_permissions:
  - id
  - role_id
  - permission_id
  - created_at

role_audit_log:
  - id
  - role_id
  - action (create/update/delete/perm_added/perm_removed)
  - changed_by_id
  - before_data (JSON)
  - after_data (JSON)
  - created_at
```

## Features
- **List & Search**: paginated list, search by name/description, filter by permission, sort options.
- **Bulk Permissions**: assign/remove multiple permissions at once.
- **Conflict Detection**: if role editing conflicts (race condition), show merge UI.
- **Template Roles**: clone existing role as template for new roles.
- **Permission Groups**: organize permissions by module for clearer UI.

## Security
- System roles (admin, system) protected from deletion.
- Non-admin roles cannot edit system roles at all.
- Permission changes are logged with before/after state.
- Delete is soft-delete for audit trail, with hard-delete after 30 days (via scheduled job).

## Test Requirements
- Test system roles cannot be deleted.
- Test role name uniqueness validation.
- Test permission assignment and audit logging.
- Test search and filtering work.
- Test non-admin cannot modify protected roles.
- Test bulk permission edits log correctly.

## Result
CATMIN now has enterprise-grade role administration with full audit trail and safety guards.
