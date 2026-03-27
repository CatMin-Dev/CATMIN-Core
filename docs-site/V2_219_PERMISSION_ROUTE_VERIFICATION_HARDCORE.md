# V2 219 - Permission Verification On Sensitive Routes (Hardcore)

## Mission Output
Enforce consistent permission middleware on all state-changing and sensitive admin routes.

## Current Audit
- Some module routes enforce permission middleware; others rely only on `catmin.admin` middleware.
- Routes without explicit permission checks may be exploitable if user session is compromised.

## Hardcore Implementation

### Route Permission Audit
- Generate inventory of all POST/PUT/PATCH/DELETE/sensitive routes.
- For each route, explicitly define required permission (or document if skipped).
- Add routes to CI check: any new route without explicit permission or skip-reason fails build.

### Permission Middleware Policy
- All state-changing admin routes MUST have one of:
  - Direct permission middleware: `->middleware('catmin.permission:module.users.edit')`.
  - OR explicit skip with documented reason in code comment.
- Permission format: `module.<module_slug>.<action>`.

### Enforcement Examples
- `/admin/users/{user}` PUT should require `module.users.edit`.
- `/admin/pages/{page}` PUT should require `module.pages.edit`.
- `/admin/settings` PUT should require `module.settings.config`.

### Implementation
- Create RoutePermissionChecker class to introspect all routes.
- Add CI command: `artisan catmin:check-route-permissions`.
- Create route documentation template per module.
- Update existing routes that are missing permission enforcement.

## Test Requirements
- Test non-admin user cannot perform restricted action.
- Test user with specific permission can perform that action.
- Test user without permission is rejected with 403.
- Test all POST/PUT/PATCH/DELETE routes are covered by permission checks or documented.

## Result
All sensitive CATMIN routes are now properly protected by permission middleware, eliminating privilege escalation vectors.
