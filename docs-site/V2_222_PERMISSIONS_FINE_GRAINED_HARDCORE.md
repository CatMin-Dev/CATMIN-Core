# V2 222 - Fine-Grained Permissions Per Module (Hardcore)

## Mission Output
Implement complete permissions system across all modules with enforcement at route and UI layers.

## Implementation Steps

### 1. Permissions Seeding
- Create database migration to seed all permissions.
- Define relationships: roles ↔ permissions (many-to-many).
- Assign default permissions to `admin` and `editor` roles.

### 2. Route Enforcement
- Apply `middleware('catmin.permission:module.<slug>.<action>')` to all sensitive routes.
- Create exception for unauthenticated vs. unauthorized routes (401 vs. 403).
- Add CI check to ensure all write routes have permission enforcement.

### 3. UI Enforcement
- Navigation items rendered only if user has `.menu` permission.
- Buttons/actions visible only if user has permission.
- Forms submitted with missing permissions fail with 403.

### 4. Cascade Behavior
- Permission `.menu` is prerequisite for `.list`.
- Permission `.create` implies `.list` (conceptually).
- Deletion removes user from role, not the permission itself.

### 5. Testing
- Test every route rejects requests without permission.
- Test navigation hides restricted items.
- Test permission grants access.

## Audit & Compliance
- Log all permission assignments and removals.
- Provide audit report: "Who has Permission X" and "What Permissions does Role Y have".

## Result
CATMIN permissions are now systematically defined, enforced everywhere, and fully auditable.
