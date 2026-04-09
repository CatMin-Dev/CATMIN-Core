# Safe Uninstall + Snapshot/Rollback (067/068)

## Désinstallation sûre
Workflow:
1. analyse d’impact (dépendances inverses, module critique)
2. snapshot automatique `pre-uninstall`
3. désactivation si actif
4. suppression contrôlée
5. logs d’audit

Politiques données:
- `keep_data` (défaut)
- `archive_data`
- `remove_data`

## Snapshot/Rollback
- création manuelle via manager modules
- création automatique avant update/uninstall
- stockage: `storage/modules/snapshots/`
- rollback: restauration fichiers module depuis snapshot sélectionné

Limite actuelle:
- rollback orienté fichiers/état module
- DB métier non restaurée automatiquement (comportement explicite)

