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

## Références éditeurs
- `docs/modules/community-repository-standard-064.md`
- `docs/release/module-release-pipeline-078-080.md`
- `docs/modules/community-signing-trust-admission-081.md`
