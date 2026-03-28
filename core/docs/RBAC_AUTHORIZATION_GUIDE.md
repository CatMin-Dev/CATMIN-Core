# CATMIN RBAC Authorization Guide

## Objectif
Fournir une gouvernance d'acces uniforme, mesurable et testable pour l'admin CATMIN.

## Convention de permissions
Convention active (compatibilite historique):
- Format: `module.<slug>.<action>`
- Actions standards: `menu`, `list`, `create`, `edit`, `delete`, `config`

Exemples:
- `module.users.list`
- `module.users.config`
- `module.settings.list`
- `module.settings.config`
- `module.core.list`
- `module.core.config`

## Mapping Route -> Permission
Regle:
- route de lecture: `list`
- creation: `create`
- edition/mise a jour: `edit`
- suppression: `delete`
- operations sensibles (enable/disable/migrate/rebuild/config): `config`

Routes core sensibles:
- `admin.users.index` -> `module.users.list`
- `admin.roles.index` -> `module.users.config`
- `admin.settings.index` -> `module.settings.list`
- `admin.modules.index` -> `module.core.list`
- `admin.modules.*` (config/actions) -> `module.core.config`

## Middleware central
Middleware: `catmin.permission`

Responsabilites:
- lit la permission requise sur la route
- applique RBAC session
- applique bypass super-admin explicite
- renvoie `403` si refuse
- journalise `security.permission.denied` via logger

## Politique super-admin
Source centralisee: `App\Services\RbacPermissionService`

Regles:
- bypass si permission wildcard `*`
- bypass si role `super-admin`
- plus de logique implicite basee sur un id fixe

## Audit de couverture
Commande:
- `php artisan catmin:audit-rbac`
- `php artisan catmin:audit-rbac --save`
- `php artisan catmin:audit-rbac --json`

Sorties:
- couverture des routes admin sensibles
- routes sensibles non protegees
- incoherences mapping permission
- rapports JSON/Markdown dans `storage/app/reports/`

## Synchronisation permissions
Source de verite systeme:
- `catmin:rbac:sync`
- permissions construites par convention via `RbacPermissionService`

## UI et permissions
La securite n'est pas seulement route-level:
- menus conditionnes par `catmin_can(...)`
- actions sensibles masquees si permission absente
- ecrans edition en lecture seule si permission config absente

## Ajouter une nouvelle route sensible
1. definir la route admin
2. ajouter `catmin.permission:<permission>`
3. aligner la permission avec convention `module.<slug>.<action>`
4. masquer/afficher action UI via `catmin_can(...)`
5. executer `php artisan catmin:audit-rbac`
6. ajouter test d'autorisation (403 sans permission, ok avec permission, bypass super-admin)
