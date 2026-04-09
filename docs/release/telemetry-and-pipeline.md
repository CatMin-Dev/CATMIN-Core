# CATMIN Telemetry Minimal + Release Pipeline

## Telemetry Minimal (Opt-in)

CATMIN supporte une télémétrie **minimale**, transparente et désactivable.

Activation via `.env`:

```env
TELEMETRY_ENABLED=false
```

### Champs autorisés

- `core_version`
- `php_version`
- `env`
- `modules_enabled_count`
- `updates_pending`
- `timestamp`

### Champs interdits

- email admin
- IP brute
- identifiants personnels
- secrets (mots de passe, tokens, clés)
- dumps SQL / payloads sensibles

Stockage local: table `core_telemetry_reports`.

## Release Pipeline (Core/Modules)

Pipeline recommandé:

1. vérifier structure package
2. valider manifest CATMIN
3. générer checksums SHA-256/SHA-512
4. générer signature RSA SHA-256 (si clé privée fournie)
5. publier ZIP + metadata

Script fourni:

```bash
php scripts/release/generate-release-metadata.php release/package.zip /path/private-key.pem
```

Artifacts générés:

- `*-checksums.json`
- `*-signature.json`

