# V2 222 - Fine-Grained Permissions Per Module

## Objective
Define a comprehensive permissions matrix covering all modules and actions.

## Current Baseline
- Permission convention exists: `module.<slug>.<action>` (e.g., `module.users.edit`).
- Permissions system is RBAC-based but not fully operationalized across all modules.

## Permissions Matrix Design

### Core Module
- `module.core.config` - view system configuration.
- `module.core.health` - access health checks.
- `module.core.status` - view system status.

### Users Module
- `module.users.menu` - view users menu item.
- `module.users.list` - list users.
- `module.users.create` - create new user.
- `module.users.edit` - edit users.
- `module.users.delete` - delete users.
- `module.users.config` - manage roles/permissions matrix.

### Pages Module
- `module.pages.menu` - view pages menu item.
- `module.pages.list`, `.create`, `.edit`, `.delete`, `.config`.

### Media Module
- `module.media.menu`, `.list`, `.create`, `.edit`, `.delete`, `.config`.

### (Pattern for other modules: Settings, Articles, Webhooks, Shop, etc.)

## Action Convention
- `.menu` - navigation visibility (prerequisite for other permissions).
- `.list` - view list.
- `.create` - create new.
- `.edit` - modify existing.
- `.delete` - remove/archive.
- `.config` - management/settings for that module.

## Result
A consistent, comprehensive permissions framework ready for role assignment and enforcement.
