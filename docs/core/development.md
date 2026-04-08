# Développement Core

## Conventions
- PHP strict types.
- Centraliser les dépendances runtime dans `core/`.
- Éviter la logique métier dans les vues.
- Ajouter des retours structurés pour erreurs (pages failsafe).

## Base de données
- Migrations dans `core/database/migrations/`.
- Seeders install via `install/InstallerEngine.php`.
- Version schema via `db_versions`.

## Sécurité
- CSRF obligatoire sur formulaires.
- Headers sécurité actifs.
- Routes admin sous middleware auth + IP rules.
