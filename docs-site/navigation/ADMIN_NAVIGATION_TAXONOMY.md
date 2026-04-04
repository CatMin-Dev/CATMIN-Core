# Admin Navigation Taxonomy (V2)

## Master Categories
- Dashboard
- Contenu
- Utilisateurs
- Exploitation
- Integrations
- Configuration
- Business / Addons

## Placement Rules
- Content authoring pages stay in Contenu.
- Access management pages stay in Utilisateurs.
- Monitoring, queue, cron, logs, analytics stay in Exploitation.
- External connectors (webhooks, mailer, marketplace, bundles) stay in Integrations.
- Core/system settings stay in Configuration.
- Domain addons (event, booking, crm, forms, shop) stay in Business / Addons.

## Item Contract
Required fields for navigation items:
- label
- icon
- route or path

Optional fields:
- active_when
- permission
- module
- target
- badge
- match_module

## State Model
- Master state: active/opened/closed
- Sub state: active/opened/closed
- Leaf state: active/hovered
- Shell state: expanded/collapsed

## Collapsed Mode
- Master icon remains visible.
- Flyout opens on hover.
- Clicking a flyout leaf restores expanded mode and keeps active route.
