# Core Contract System (CATMIN)

## Principe
CORE = moteur contractuel
MODULE = extension declarative

Le core ne contient pas de logique metier module. Il lit des declarations manifest, valide, ordonne et injecte.

## Contrats couverts
- Chargement modules: scan, validation, compatibilite, dependances, etat runtime
- Routes modules: `routes_map` par zone (`admin`, `front`, `api`, `ajax`, `settings`, `tools`)
- Permissions/settings: fichiers declares via manifest
- UI injection: validation des cibles via registre d'anchors
- Snapshot runtime: source de verite pour le routeur et les bridges

## Fichiers cle
- `core/module-loader.php`
- `core/module-validator.php`
- `core/module-manifest-standard.php`
- `core/module-manifest-v1-schema.php`
- `core/module-runtime-snapshot.php`
- `core/module-ui-anchor-registry.php`

## Interdictions
- Pas de patch metier module dans le core
- Pas de route/menu/view hardcodee pour un module specifique
- Pas de bypass des loaders/registries
