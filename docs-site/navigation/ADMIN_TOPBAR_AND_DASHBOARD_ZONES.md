# Topbar Context & Dashboard Zones

## Topbar Contracts
Services:
- AdminTopbarService
- AdminPageContextService
- AdminActionRegistry

Topbar zones:
- Left: breadcrumbs + page title
- Center: command surface (go-to pages/actions)
- Right: notifications, global actions, profile

### Context Actions
Modules/addons can register per-route actions through AdminActionRegistry::registerForPage().

### Global Actions
Modules/addons can register global actions through AdminActionRegistry::registerGlobal().

## Dashboard Zone System
Services:
- DashboardLayoutService
- DashboardZoneRegistry
- DashboardWidgetPriorityService

Zones:
- critical
- kpis
- activity
- actions
- secondary

Widget contract extensions:
- zone
- priority
- span (half|full)

Fallbacks:
- tone=>zone mapping is applied when zone is missing.
