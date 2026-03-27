# V2 225 - Navigation Permission Matrix (Hardcore)

## Mission Output
Enforce navigation rendering based on RBAC matrix and effective permissions.

## Objective
Ensure each admin sidebar/topbar entry is visible only when the actor has matching permission.

## Matrix Model
- Navigation item -> required permission key.
- Optional module-enabled condition.
- Optional fallback (hidden if route missing or module disabled).

## Coverage
- Administration section entries.
- CMS section entries.
- Integrations section entries.
- Commerce section entries.
- Dynamic "enabled modules" entries.

## Enforcement
- Resolve effective permissions from session/context provider.
- Hide entry when permission is missing.
- Keep route-level permission middleware as authoritative second line.

## Quality Gates
- No item visible without matching permission unless explicitly public.
- No broken link rendered when route/module unavailable.
- Active state works for allowed entries only.

## Tests
- User with menu permission sees item.
- User without menu permission does not see item.
- Role change reflects on next session refresh.
- Dynamic module item hidden when module disabled.

## Documentation
- Maintain a matrix table `nav_item -> permission -> module`.
- Document exceptions and public entries.

## Result
Navigation now reliably reflects RBAC intent and reduces unauthorized action discovery.
