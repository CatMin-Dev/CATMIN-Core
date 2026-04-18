# Manifest V1 Officiel (CATMIN)

Version: 1.0
Statut: actif a partir de 0.7.0-RC.1

## Regles
- Seul `manifest.json` est accepte.
- `module.json` n'est plus lu par le loader.
- Validation stricte schema V1 si `schema_version = "1.0"`.
- Tout chemin declare doit etre relatif au module et sans traversal.
- checksums et signature release obligatoires.
- versioning obligatoire (strategie + changelog) pour toute release module.

## Champs obligatoires V1
- `schema_version`
- `module_id`
- `name`
- `version`
- `type`
- `compatibility`
- `bootstrap.provider`
- `routes`
- `permissions.file`
- `settings.file`
- `dependencies`
- `docs.index`
- `release.checksums`
- `release.signature`
- `release.versioning.strategy`
- `release.versioning.changelog`

## Bloc release recommande

```json
{
	"release": {
		"checksums": "release/checksums.json",
		"signature": "release/signature.json",
		"versioning": {
			"strategy": "semver",
			"changelog": "docs/CHANGELOG.md"
		}
	}
}
```

## Notes de compatibilite
- Les manifests legacy sans `schema_version` restent normalises par `core/module-manifest-standard.php`.
- Les manifests V1 sont validates en plus par `core/module-manifest-v1-schema.php`.

## Exemple de reference
- `modules/admin/cat-contract-demo/manifest.json`
