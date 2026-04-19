# Changelog

## 0.2.5-dev - 2026-04-19
- Expanded internal documentation for menu/sidebar behavior and active state alignment.
- Added full checklist for perfect display quality (routes, keys, permissions, ordering, i18n, layout).
- Documented detailed contract capabilities: navigation, routes, settings, permissions, services, assets, events, notifications, healthchecks, ui.inject.
- Added mandatory post-change integrity process: regenerate checksums, regenerate signature, verify runtime trust.
- Added SQL demo migrations (`up` and `down`) to document and validate module database lifecycle.
- Fixed uninstall/snapshot scope handling for addons path resolution (preview no longer fails with module_not_found).

## 0.2.4-dev - 2026-04-18
- Initial contract demo module scaffold.
- Added strict manifest V1 contract coverage.
- Added release artifacts contract (checksums + signature + versioning).
