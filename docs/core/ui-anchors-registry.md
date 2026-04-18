# UI Anchors Registry

Registre officiel des cibles injectables pour les modules.

## Source de verite
- `core/module-ui-anchor-registry.php`

## Anchors supportes
- `sidebar.main`
- `sidebar.settings`
- `topbar.actions`
- `topbar.indicators`
- `dashboard.widgets`
- `dashboard.cards`
- `dashboard.activity`
- `dashboard.monitoring`
- `page.header.actions`
- `page.footer.actions`
- `snippets.registry`
- `notifications.feed`
- `settings.sections`
- `admin.tools`
- `front.widgets`
- `front.blocks`

## Validation
- `manifest.ui.inject[*].target` doit exister dans ce registre.
- `id` doit etre unique par module.
