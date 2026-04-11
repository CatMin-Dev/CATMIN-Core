# Module Reset Inventory (R001)

Date: 2026-04-11
Scope: /var/www/catmin.local/catmin

## Modules present (to remove)
- admin/cat-blog
- admin/cat-cache
- admin/cat-logger
- admin/cat-media
- admin/cat-menu
- admin/cat-page
- admin/cat-relations
- admin/cat-search
- admin/cat-seo
- admin/cat-settings-extended
- admin/cat-tags
- admin/cat-user-profiles

## Module SQL tables (module-created)
From module repositories/migrations and runtime create calls:
- cat_page_pages
- cat_page_pages_meta
- cat_page_revisions
- cat_page_audit_trail
- cat_page_workflow
- cat_page_preview_tokens
- cat_page_search_index
- mod_cat_blog_posts
- mod_cat_blog_meta
- mod_cat_blog_revisions
- mod_cat_logger_logs
- cat_cache_entries
- cat_media_assets
- cat_menu_items
- cat_relations_links
- cat_search_index
- cat_seo_rules
- cat_settings_extended
- cat_tags_tags
- user_profiles

## Core files with module-specific logic or injections
- admin/routes.php (module manager, market, status, integrity scan, dependency resolve, uninstall, module routes)
- admin/views/layouts/partials/sidebar.php (module injection + module group entries)
- admin/views/modules/index.php (modules manager)
- admin/views/modules-market.php (market)
- admin/views/modules/uninstall-confirm.php (uninstall UI)
- admin/views/settings/apps.php (module-adjacent apps launcher)
- admin/views/settings/module-repositories.php (module repositories + policies)
- core/module-loader.php (module scanning)
- core/module-activator.php (activation states)
- core/module-manifest-standard.php (module manifest standard + categories)
- core/module-validator.php (manifest validation)
- core/module-checksum-validator.php (checksums)
- core/module-signature-validator.php (signatures)
- core/module-snapshot-storage.php (snapshot storage)
- core/module-integrity-reporter.php (integrity reports)
- core/module-repository-checker.php (repositories)
- core/market-engine.php (market)
- core/market-github.php (github catalog)
- core/module-install-runner.php (install)
- core/module-installer.php (zip install)
- core/module-uninstaller.php (uninstall)
- core/module-zip-validator.php (zip validation)

## Sidebar entries currently present (before reset)
- Dashboard
- Editorial
- Modules
- Administration
- Settings
- System
- Settings sub-pages: General, Mail, Security, Apps, Module Repositories
- System sub-pages: Monitoring, Health, Logs, Cron, Maintenance, Updates, Trust Center

## Settings pages currently present
- /settings/general
- /settings/mail
- /settings/security
- /settings/apps
- /settings/module-repositories

## Bridges or faux-core logic currently present
- Page search in topbar points to /pages (module-specific)
- Dashboard modules stats + integrations ties to module scanning (core should only read modules declaratively)

## JSON data used as primary storage (identified)
- cache/cat-blog/front-index-default.json (module cache)
- storage/modules/integrity-reports/*.json (module integrity reports)
- storage/modules/snapshots/index/*.json (module snapshots)
- storage/modules/snapshots/files/*/*.json (module snapshot metadata)
- storage/updates/reports/*.json (core update reports)
- storage/updates/releases/*.json (release reports)
- storage/install/recovery-codes.json (core install)
- storage/config/runtime.json (core runtime config)
- storage/trust/*.json (trust cache)

## Notes
- Module tables above should be dropped via a controlled SQL script (not executed automatically).
- Module data should be archived before removal if needed.
- Modules directory must be removed from runtime after inventory.
