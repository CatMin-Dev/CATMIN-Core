# CATMIN Core Lock & Repo Strategy (054)

## Core lock
- Core structure is frozen.
- Routing, auth, settings, topbar and i18n are frozen.
- Only bugfix/security/maintenance updates are allowed on locked core.
- No structural refactor without explicit human instruction.

## Repository strategy
- Private dev repos:
  - `CatMin-Dev/core`
  - `CatMin-Dev/modules`
- Public release repos:
  - `CatMin-Dev/CATMIN-Core`
  - `CatMin-Dev/CATMIN-Modules`

## Mandatory rules
- No public push without explicit human approval.
- Daily work, tests and fixes happen in private repos.
- Public repos receive only validated and manually approved releases.

## Module compatibility contract
Each module manifest must declare:
- `catmin_min`
- `catmin_max`

Validation rejects modules when:
- current core version `< catmin_min`
- current core version `> catmin_max`

## Module path convention
- `/modules/{type}/{slug}/`

## Versioning
- `MAJOR.MINOR.PATCH-STAGE.NUMBER`

