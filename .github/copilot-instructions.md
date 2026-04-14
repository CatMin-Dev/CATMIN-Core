# CATMIN Core/Module Guardrails

## Mandatory Reading
- Before major module implementation, read and apply:
	- `instructions/000-CATMIN-master-brief.md`
	- `instructions/001-CATMIN-blueprint-v1.md`
	- `instructions/002-CATMIN-agent-plan.md`
	- `instructions/003-CATMIN-versioning-strategy.md`
	- `instructions/004-CATMIN-prompt-starter.md`
	- `instructions/005-CATMIN-legal-and-tracking-notes.md`
	- `instructions/CATMIN-GITHUB-REPO-GUARD-PROMPT-X100.md`
- If there is any overlap, follow the strictest rule.

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

## Versioning Policy
- Follow CATMIN version format: `MAJOR.MINOR.PATCH-STAGE.NUMBER`.
- Keep repository boundary discipline (core vs modules) and commit scope strict.
