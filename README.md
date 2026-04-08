# CATMIN Standalone

![Version](https://img.shields.io/badge/version-0.3.0--dev.3-ec407a)
![PHP](https://img.shields.io/badge/php-8.3%2B-777777)
![Package](https://img.shields.io/badge/package-standalone%20zip-3b82f6)
![Status](https://img.shields.io/badge/status-ready%20for%20install%20test-16a34a)

CATMIN est un panel standalone PHP orienté administration, installation guidée et sécurité core.

## Pré-requis
- PHP `8.3+`
- Extensions PHP: `pdo`, `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `openssl`, `curl`, `gd`, `intl`, `session`, `ctype`, `filter`, `hash`, `tokenizer`, `sodium`, `zip`, `phar`, `spl`
- Apache avec `mod_rewrite` (ou Nginx équivalent)

## Installation rapide
1. Uploader le contenu CATMIN sur votre hébergement PHP.
2. Pointer le webroot sur `public/` (recommandé), ou laisser la racine avec le routeur fourni.
3. Ouvrir `/install` et suivre l'assistant.
4. Valider le lock final de l'installateur.
5. Se connecter via `/admin`.

## Packaging release
- Build ZIP:
```bash
bash scripts/release/build-standalone-zip.sh
```
- Vérification ZIP:
```bash
bash scripts/release/verify-standalone-package.sh ../release/catmin-<version>-standalone.zip
```

## Documentation
- `docs/install/installation.md`
- `docs/install/neutraliser-installateur.md`
- `docs/install/permissions.md`
- `docs/install/hard-reset-superadmin.md`
- `docs/install/diagnostic-minimum.md`
- `docs/release/standalone-final.md`
