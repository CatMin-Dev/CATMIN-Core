# 042 - Maintenance Mode Engine

## Livré
- moteur maintenance centralisé (`Core\maintenance\MaintenanceEngine`)
- niveaux de maintenance (`1..3`) + motif + message public
- whitelist IP + whitelist admin IDs
- bypass SuperAdmin garanti côté middleware
- page admin maintenance enrichie pour pilotage complet
- journalisation des bascules maintenance avec contexte

## Fichiers clés
- `core/maintenance/MaintenanceEngine.php`
- `core/security/SecurityManager.php`
- `admin/views/maintenance/index.php`
- `core/settings-schema.php`
- `install/InstallerEngine.php`

