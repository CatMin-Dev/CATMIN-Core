# V2 221 - Complete Roles CRUD

## Objective
Implement full admin UI for role management (list, create, edit, delete).

## Current Baseline
- Role model exists with permissions relationship.
- No admin UI for role CRUD currently visible.
- Some protected system roles exist (admin, system).

## Implementation Requirements

### Admin UI Pages
- **List roles**: display all roles, search/filter by name, show permission count, actions (edit/delete).
- **Create role**: form to create new role with name/description, assign permissions (multiple select).
- **Edit role**: form to modify role, update permissions, handle conflicting changes.
- **Delete role**: confirmation, prevent deletion of system roles, cascade or warn on user assignments.

### System Role Protection
- Mark certain roles as system-protected (`admin`, `system`, `guest`).
- System roles cannot be deleted or have permissions stripped.
- System roles can be edited (description only) by super-admin.

### Validation
- Role name: 2-64 characters, alphanumeric + underscores.
- Role name: unique.
- Description: 0-500 characters.
- Permissions: at least one permission required.

### Audit Trail
- Log all role create/update/delete with changed values and actor.
- Log permission changes on roles with before/after snapshot.

### Result
A complete, safe, and auditable role management interface.
