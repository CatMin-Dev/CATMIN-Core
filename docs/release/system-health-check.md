# 043 - System Health Check

## Livré
- service health check interne (`Core\system\HealthCheckService`)
- statuts normalisés: `healthy`, `warning`, `critical`, `unknown`
- checks core/env/db/security/storage/modules/cron
- page admin dédiée: `/admin/system/health`
- résumé global + tableau détaillé par check

## Fichiers clés
- `core/system/HealthCheckService.php`
- `admin/views/system/health.php`
- `admin/routes.php`

