# Structure Modules

## Dossier
- `modules/<module>/module.json`
- code module autonome

## Règles
- déclarer compatibilité core/db
- déclarer dépendances
- fournir entrées nav admin si nécessaire
- ne pas écrire hors runtime autorisé

## Activation
- état module stocké en base
- chargement via engine module core
- checks de compatibilité à l'upgrade

## Source de vérité runtime
- `enabled_by_default` est une intention initiale de manifest uniquement
- l'état runtime réel est persisté dans `core_modules.status` via `CoreModuleStateStore`
- les consommateurs core doivent lire le snapshot central `CoreModuleRuntimeSnapshot`
- le snapshot central aligne router, boot, permissions loader, sidebar/settings admin et diagnostics modules

## Références éditeurs
- `docs/modules/community-repository-standard.md`
- `docs/release/module-release-pipeline.md`
- `docs/modules/community-signing-trust-admission.md`
