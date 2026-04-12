# CATMIN Module Release Pipeline (078-080)

## Scope
Official private release pipeline for CATMIN modules.

## Produced artifacts (required)
For each released module:
- `module.zip`
- `manifest.json`
- `checksums.json`
- `signature.json` (when signature is enabled)
- `release-metadata.json`

## Official pipeline
1. Pre-check source path and `manifest.json`
2. Validate manifest against CATMIN standard
3. Build clean stage (exclude sensitive and non-release files)
4. Build unsigned package
5. Generate deterministic SHA-256 checksums from packaged module content
6. Compute `module_hash`
7. Sign `module_hash` with RSA private key (optional/required by policy)
8. Build final `module.zip`
9. Generate `release-metadata.json`
10. Run post-build verification (`zip` + manifest + checksums + signature)

## Exclusions
Never package:
- `.git/`, `.github/`, `.vscode/`
- `tests/`, `__tests__/`
- temp/cache/backup folders
- `*.log`, `*.tmp`, swap/backup files
- private keys (`*.key`, `*.pem`, `*.p12`, `*.pfx`)

## Scripts
- `scripts/release/build-module-release.sh`
- `scripts/release/generate-module-checksums.php`
- `scripts/release/generate-module-signature.php`
- `scripts/release/generate-module-release-metadata.php`
- `scripts/release/verify-module-release.php`
- `scripts/release/sync-official-modules-index.php`

## Exemple concret
- `docs/release/module-pipeline-example-file-map-082.md`

## Usage

```bash
bash scripts/release/build-module-release.sh /abs/path/to/module
```

Custom release output dir:

```bash
bash scripts/release/build-module-release.sh /abs/path/to/module /abs/path/to/release/modules
```

With RSA signing:

```bash
MODULE_SIGNING_KEY=/abs/keys/module-private.pem \
MODULE_SIGNING_KEY_ID=catmin-official-key-001 \
bash scripts/release/build-module-release.sh /abs/path/to/module
```

Strict RELEASE mode (signature mandatory):

```bash
CATMIN_RELEASE_TARGET=release \
MODULE_SIGNING_KEY=/abs/keys/module-private.pem \
MODULE_SIGNING_KEY_ID=catmin-official-key-001 \
bash scripts/release/build-module-release.sh /abs/path/to/module
```

Update `catmin/modules` trust/module index after release build:

```bash
php scripts/release/sync-official-modules-index.php
```

## Checksums standard (079)
- Algorithm: `sha256`
- Source of truth: packaged module content
- Paths: relative, stable, slash `/`, sorted
- `checksums.json` is excluded from its own hash generation
- Global `module_hash` is SHA-256 of canonical `path:hash` sorted list

## RSA signature workflow (080)
- Sign target: `module_hash` from `checksums.json`
- Signature payload format:
  - `schema_version`
  - `algorithm` (`rsa-sha256`)
  - `module_slug`
  - `module_version`
  - `signed_hash`
  - `signature` (base64)
  - `key_id`
  - `signed_at`

## Security notes
- Private key is used only at release time and never committed
- Public verification key remains in CATMIN keyring
- In strict market policy mode, missing/invalid integrity/signature leads to install refusal
