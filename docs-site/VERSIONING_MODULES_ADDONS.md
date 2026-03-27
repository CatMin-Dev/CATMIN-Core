# Versioning Modules et Addons (CATMIN V1)

## Objectif

Definir une base uniforme de versioning pour:

- les modules (`module.json`)
- les addons (`addon.json`)

sans implementer tout le systeme de mise a jour des maintenant.

## Format retenu

Format semver simple obligatoire:

- `major.minor.patch`
- exemple: `1.0.0`, `1.2.4`

Regex appliquee: `^\\d+\\.\\d+\\.\\d+$`

## Regles V1

- Si la version est invalide/absente, CATMIN normalise vers `0.1.0`.
- La valeur brute est conservee (`version_raw`) pour audit/debug.
- Les comparaisons de version utilisent `version_compare` via `VersioningService`.

## Champs attendus

### module.json

```json
{
  "name": "Pages",
  "slug": "pages",
  "version": "1.0.0",
  "enabled": true
}
```

### addon.json

```json
{
  "name": "Example Addon",
  "slug": "example-addon",
  "version": "1.0.0",
  "enabled": false
}
```

## Preparation updates futures

Cette base permet de construire ensuite:

- un detecteur d'upgrade (`installed < current`)
- des migrations conditionnelles par version
- un pipeline de release projet par projet

sans changer le format des metadonnees.
