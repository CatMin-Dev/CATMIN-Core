# CATMIN Module Pipeline Example & File Map (082)

## Objectif
Donner un exemple concret et exploitable du mapping source -> package -> artefacts release pour un module CATMIN.

## Exemple dépôt source
Chemin source:
- `/modules/admin/cat-page/`

Contenu attendu:
- `manifest.json`
- `routes.php`
- `permissions.php`
- `settings.php`
- `controllers/`
- `views/`
- `lang/`
- `assets/`
- `migrations/`
- `README.md`
- `CHANGELOG.md`

## Exemple package final
Nom package:
- `cat-page-1.0.0.zip`

Contenu interne du ZIP (racine module):
- `manifest.json`
- `routes.php`
- `permissions.php`
- `settings.php`
- `controllers/...`
- `views/...`
- `lang/...`
- `assets/...`
- `migrations/...`
- `README.md`
- `CHANGELOG.md`

Exclusions release (non exhaustif):
- `.git/`, `.github/`, `.vscode/`
- `tests/`, `__tests__/`
- `tmp/`, `cache/`, `backups/`
- `*.log`, `*.tmp`
- clés privées (`*.key`, `*.pem`, `*.p12`, `*.pfx`)

## Artefacts release attendus
Pour `cat-page:1.0.0`:
- `cat-page-1.0.0.zip`
- `cat-page-1.0.0.manifest.json`
- `cat-page-1.0.0.checksums.json`
- `cat-page-1.0.0.signature.json` (si signature active)
- `cat-page-1.0.0.release-metadata.json`

## Exemple workflow global
1. Dépôt source valide.
2. Build package final.
3. Génération checksums (`sha256` + `module_hash`).
4. Signature du `module_hash` (RSA).
5. Vérification post-build.
6. Publication des artefacts.

## Exemple vérification côté CATMIN
1. Télécharger ZIP.
2. Télécharger checksums.
3. Télécharger signature.
4. Extraire en staging.
5. Recalculer hashes fichier.
6. Comparer avec `checksums.json`.
7. Vérifier signature RSA sur `module_hash`.
8. Installer uniquement si tous les contrôles sont OK.

## Exemple commandes (pipeline local)
```bash
bash scripts/release/build-module-release.sh /abs/path/modules/admin/cat-page
```

Avec signature:
```bash
MODULE_SIGNING_KEY=/abs/keys/module-private.pem \
MODULE_SIGNING_KEY_ID=catmin-official-key-001 \
bash scripts/release/build-module-release.sh /abs/path/modules/admin/cat-page
```

Vérification explicite:
```bash
php scripts/release/verify-module-release.php /abs/release/modules/cat-page-1.0.0/module.zip --require-signature
```

