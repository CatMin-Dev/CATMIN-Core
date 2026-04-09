# CATMIN Community Repository Standard (064)

Le market CATMIN supporte un index officiel `catmin-repository.json` à la racine du dépôt.

## Obligatoire dépôt
- `schema_version`
- `repository_name`
- `repository_slug`
- `owner`
- `provider`
- `base_repo_url`
- `trust_claim`
- `modules`

## Obligatoire module
- `slug`
- `name`
- `type`
- `version`
- `catmin_min`
- `php_min`
- `manifest_url`
- `release_zip_url`

## Règles
- validation JSON + schema
- slug/URLs vérifiées
- trust_claim déclaré mais non souverain
- policy CATMIN garde la décision finale
- dépôt non standard: refus si `requires_manifest_standard=1`

