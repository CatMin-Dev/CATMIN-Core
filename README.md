# CATMIN Core

[![Version](https://img.shields.io/badge/version-0.6.0-2ecc71)](https://github.com/CatMin-Dev/CATMIN-Core/releases/tag/0.6.0)
![PHP](https://img.shields.io/badge/php-8.3%2B-44403c)
![DB Schema](https://img.shields.io/badge/db_schema-0.1.0--RC.1-c2234d)
![Installer](https://img.shields.io/badge/installer-lock%20enforced-16a34a)
[![Standalone](https://img.shields.io/badge/Standalone-Download%20Release-0ea5e9?logo=github)](https://github.com/CatMin-Dev/CATMIN-Core/releases)

CATMIN est un panel PHP standalone orienté administration, sécurité et exploitation terrain:
- installateur guidé avec lock final
- core modulaire + market/repository policy
- gestion admins/roles/permissions
- monitoring, logs, maintenance, cron, backup
- support i18n (`fr` / `en`)

## Télécharger la version Standalone

Le package standalone officiel est publié sur les releases GitHub:

**[Télécharger CATMIN Standalone depuis CATMIN-Core Releases](https://github.com/CatMin-Dev/CATMIN-Core/releases)**

## Pré-requis

- PHP `8.3+`
- Extensions PHP:
  - `pdo`, `pdo_mysql` (ou `pdo_sqlite`), `mbstring`, `json`, `fileinfo`
  - `openssl`, `curl`, `gd`, `intl`, `session`, `ctype`, `filter`
  - `hash`, `tokenizer`, `sodium`, `zip`, `phar`, `spl`
- Apache (`mod_rewrite`) ou Nginx équivalent
- MySQL/MariaDB ou SQLite selon le mode choisi à l'installation

## Installation rapide (production)

1. Déployer les fichiers CATMIN sur l'hébergement.
2. Pointer le webroot sur `public/` (recommandé).
3. Ouvrir `/install`.
4. Suivre les étapes (précheck -> DB -> superadmin -> lock final).
5. Se connecter au panel via la route admin configurée.

## Architecture (résumé)

| Zone | Rôle |
| --- | --- |
| `core/` | moteur applicatif, routing, services, sécurité |
| `admin/` | interface d'administration |
| `front/` | front public |
| `install/` | installateur + verrouillage final |
| `modules/` | modules installables |
| `storage/` | données runtime, backups, logs, updates |

## Build release standalone (maintainers)

Génération package:

```bash
bash scripts/release/build-standalone-zip.sh
```

Vérification intégrité package:

```bash
bash scripts/release/verify-standalone-package.sh ../release/catmin-<version>-standalone.zip
```

Artefacts attendus:
- `catmin-<version>-standalone.zip`
- `catmin-<version>-standalone-manifest.json`
- `catmin-<version>-standalone-checksums.json`
- `catmin-<version>-standalone-signature.json`
- `release-report.json`

## Sécurité / exploitation

- Lock install obligatoire après setup
- Politique de confiance modules (checksums/signatures/scopes)
- Route admin configurable
- Journalisation + monitoring + recovery docs

## Documentation

### Installation
- `docs/install/installation.md`
- `docs/install/permissions.md`
- `docs/install/neutraliser-installateur.md`
- `docs/install/hard-reset-superadmin.md`
- `docs/install/diagnostic-minimum.md`

### Core / release
- `docs/core/README.md`
- `docs/core/release.md`
- `docs/release/standalone-final.md`
- `docs/release/release-checklist.md`
- `docs/release/final-release-checklist.md`

### Modules / trust
- `docs/modules/repository-registry.md`
- `docs/modules/market-trust-policy.md`
- `docs/modules/community-signing-trust-admission.md`

## Dépôts

- Release public (core): `https://github.com/CatMin-Dev/CATMIN-Core`
- Release public (modules): `https://github.com/CatMin-Dev/CATMIN-Modules`

---

CATMIN `0.5.0-RC.9` - harmonisation globale actions admin en input-group icones/tooltips, nettoyage CSS inline et fix integrite backup.
