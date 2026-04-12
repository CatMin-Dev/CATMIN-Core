# CATMIN CORE LOCK - 0.4.0-rc.4

Date: 2026-04-12
Version: 0.4.0-rc.4

## Scope
- Core lock confirmed: architecture changes frozen.
- Allowed after lock: bugfix, security fix, release/packaging maintenance only.
- New functional scope must be delivered by modules/addons.

## This lock pass
- Database cleanup applied on working instance: only `admin_*`, `core_*`, `front_users` kept as native baseline.
- Non-native/module/content tables removed from baseline database.
- Installer/migration baseline revalidated on isolated DB (`catmin_install_verify`): full native schema recreated and seeded.
- Versioning alignment fixed: `version.json db_schema` now matches `config/database.php` (`0.1.0-dev.3`).
