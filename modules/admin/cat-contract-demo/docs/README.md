# CAT Contract Demo

Module de demonstration contractuelle pour CATMIN.

## Statut de publication
- embarque dans le core `0.7.0-RC.1`
- release standalone autorisee sur le depot prive modules uniquement
- publication publique differee jusqu'au test d'installation clean et duplication `DEMO2`

## Role exact
Valider un module admin minimal, sans logique metier, en s'appuyant sur les contrats officiels du core.

## Entrees exposees
- Route admin: `/catmin/contract-demo`
- Route settings: `/catmin/settings/contract-demo`
- Sidebar principale: une entree dans un groupe module dedie `Démo`
- Sidebar settings: une entree `Contract Demo Settings`

## Permissions
- `example.read`
- `example.write`
- `example.delete`
- `example.settings`
- `example.tools`

## Structure utilisee
- `manifest.json`: contrat principal
- `routes/admin.php`: page admin sous layout admin officiel
- `routes/settings.php`: page settings sous layout admin officiel
- `permissions.php`: permissions module
- `settings.php`: registre de settings module

## Limites volontaires
- Pas de route front
- Pas de route API
- Pas de route AJAX
- Pas d'injection topbar
- Pas de logique metier

## Docs complementaires
- `docs/integration.md`
- `docs/capabilities-matrix.md`
