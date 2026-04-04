# Navigation Migration & Regression Safety

## Feature Flag
- Config: catmin.features.admin_navigation_v2
- Default: true
- Rollback: set false and keep legacy partial behavior.

## Migration Mapping
Old sections -> New masters:
- Administration -> Configuration / Utilisateurs / Exploitation
- Integrations -> Integrations
- CMS -> Contenu
- Addon-driven entries -> Business / Addons or Integrations

## Regression Checklist
- All admin critical routes reachable from sidebar.
- Active state works for index/create/edit/show families.
- RBAC hidden entries remain hidden.
- Collapsed mode opens flyouts and does not trap focus.
- Addon entries appear only when addon enabled.

## Rollback Plan
1. Disable feature flag.
2. Clear view/cache.
3. Validate admin index, content, users, settings.
