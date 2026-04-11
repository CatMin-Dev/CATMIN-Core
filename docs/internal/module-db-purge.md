# Module DB purge (R001)

This package ships two scripts:
- scripts/db/backup-modules.sql (creates archive_* copies)
- scripts/db/purge-modules.sql (drops module tables)

Execution order:
1) Run backup-modules.sql (verify row counts).
2) Validate archive tables.
3) Run purge-modules.sql.

Notes:
- These scripts do not include indexes or constraints on archive tables.
- Adapt per DB engine if needed.
- Do not run on production without validation.
