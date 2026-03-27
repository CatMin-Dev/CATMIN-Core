# V2 227 - Sensitive Module Permission Matrix (Hardcore)

## Mission Output
Apply stricter RBAC controls to high-risk modules and operations.

## Sensitive Domains
- Settings/system configuration
- Webhooks and external integrations
- Internal API diagnostics
- Role/permission administration
- Queue, cache, cron, logger administration

## Hardening Policy
- Sensitive actions require dedicated high-privilege permissions.
- No fallback to generic `menu` or broad admin session alone.
- Optionally require dual-control policy for critical actions (future).

## Matrix Examples
- `module.settings.config` for system settings writes.
- `module.webhooks.config` for webhook secret and endpoint changes.
- `module.users.config` for role/permission management.
- `module.cache.config` and `module.queue.config` for operational controls.

## Defense Layers
- Route middleware checks.
- Service-layer authorization guards.
- UI-level concealment of controls.

## Tests
- Sensitive action denied for role with only list/edit permissions.
- Sensitive action allowed for config-privileged role.
- Protected operations logged with actor and decision.

## Documentation
- Maintain dedicated sensitive-module matrix appendix.
- Document escalation path for granting temporary privileged access.

## Result
High-impact administrative actions are isolated behind explicit and minimal privileges.
