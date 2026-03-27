# V2 223 - User Assignments To Roles And Permissions (Hardcore)

## Mission Output
Complete RBAC assignment workflow between users, roles and effective permissions.

## Current Baseline
- User-role relation exists (`users` <-> `roles` through `user_roles`).
- RBAC context is resolved at login and stored in session.
- Role and permission structures exist but assignment lifecycle remains partially manual.

## Implementation Scope

### Assignment Flows
- Assign one or multiple roles to a user from admin UI.
- Revoke roles from a user with confirmation and reason.
- Display effective permissions computed from all assigned roles.
- Support direct exceptional permissions only if explicitly enabled by policy.

### Integrity Rules
- No duplicate user-role assignment.
- At least one admin-capable user must always remain in system.
- Removing a role should refresh effective permission cache/session for impacted user.

### Runtime Sync
- On role assignment change:
  - invalidate user RBAC cache,
  - force permission refresh on next request,
  - optionally invalidate active sessions for immediate effect.

### UI Requirements
- User detail page shows:
  - assigned roles,
  - inherited permissions,
  - last RBAC update metadata.
- Role assignment panel supports search/filter by role.

### Tests
- Positive: user gains access after role assignment.
- Negative: user denied when role removed.
- Edge: duplicate assignment prevented.
- Edge: critical admin role removal blocked when it would lock system.

## Documentation
- Document assignment behavior and effective permission computation.
- Document immediate vs deferred permission refresh policy.

## Result
RBAC assignment between users and roles is operational, consistent, and testable.
