# CATMIN Core/Module Guardrails

## Module Work Policy
- During module creation or update, do not edit core files unless user explicitly requests core modification.
- Core files include `admin/routes.php`, `admin/views/**`, and `core/**`.

## Integration Policy
- Module must use injection/discovery (`manifest`, `routes`, `hooks`, `permissions`) and keep its business logic inside module files.
- If native cron/backup/log/settings exists, reuse it. Never build a parallel engine.

## i18n Policy
- FR and EN translations are mandatory.
- French strings must include proper accents.
- No raw key labels in UI.

## Data Policy
- Avoid unnecessary JSON fields for stable business data.
- Prefer typed columns when data needs querying/filtering.
