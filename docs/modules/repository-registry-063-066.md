# Module Repository Registry (063 + 066)

## Objectif

Cette livraison introduit un registre officiel des dépôts modules pour CATMIN, avec gouvernance de confiance et UI d'administration.

## Composants core

- `core/module-repository-registry.php`
- `core/module-repository-repository.php`
- `core/module-repository-validator.php`
- `core/module-repository-checker.php`
- `core/module-repository-trust.php`
- `core/module-repository-logger.php`

## Base de données

Migration ajoutée:

- `core/database/migrations/004_add_module_repository_registry.php`

Tables:

- `core_module_repositories`
- `core_market_policy`

## UI Admin

Nouvelle page:

- `/admin/settings/module-repositories`

Fonctions:

- ajouter / éditer dépôt
- activer / désactiver
- check dépôt
- blocage trust
- suppression (non officiel)
- édition des policies globales market

## Market multi-repo

Le market consomme maintenant le registre des dépôts actifs.

- fusion multi-source par `scope/slug`
- priorité par trust level (`official > trusted > community > blocked`)
- contrôle d'installation par policy (signature/checksums/manifest)

## Workflow

Conforme au fichier:

- `prompts/CATMIN-GITHUB-COPILOT-CODEX-WORKFLOW.md`

Push effectué sur dépôt privé uniquement.
