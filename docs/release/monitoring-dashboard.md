# 045 - Monitoring Dashboard

## Livré
- service monitoring interne (`Core\system\MonitoringService`)
- widgets monitoring sur dashboard (erreurs, sécurité, maintenance, modules)
- page dédiée `/admin/system/monitoring`
- compteur d'alertes sécurité discret en topbar
- intégration avec health snapshot, logs core et événements sécurité

## Fichiers clés
- `core/system/MonitoringService.php`
- `admin/views/system/monitoring.php`
- `admin/views/dashboard/partials/monitoring-summary.php`
- `admin/views/layouts/partials/topbar.php`
- `admin/routes.php`

