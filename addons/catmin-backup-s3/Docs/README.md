# CATMIN Remote Backup Addon

## Overview

`catmin-backup-s3` ajoute une couche de sauvegarde distante au systeme de backup local CATMIN.

Providers supportes:
- S3 / S3-compatible (AWS, MinIO, etc.)
- Google Cloud Storage
- SFTP
- FTP

## Fonctionnalites

- Configuration provider depuis l'admin
- Secrets stockes chiffres (secret key, password, private key, service account JSON)
- Upload manuel d'un backup local vers stockage distant
- Listing des backups distants
- Download distant vers dossier local de preparation restore
- Retention distante (purge automatique des plus anciens)
- Logs audit (success/error) + alertes en cas d'echec

## Permissions

- `backup.remote.index`
- `backup.remote.upload`
- `backup.remote.download`
- `backup.remote.manage`

## Flux de base

1. Creer backup local (`catmin:backup:create` ou flux update/recovery)
2. Aller dans Admin > Backup distant
3. Configurer provider et tester connexion
4. Uploader un backup local
5. Executer retention si necessaire
6. Downloader un backup distant pour preparation restore local

## Settings stockes

Prefix: `addon.catmin_backup_s3.*`

Principaux champs:
- `enabled`
- `provider` (`s3|google|sftp|ftp`)
- `prefix`
- `retention_max`

S3:
- `endpoint`, `region`, `bucket`, `access_key`, `secret_key`, `use_path_style_endpoint`

Google:
- `google_project_id`, `google_bucket`, `google_service_account_json`

SFTP/FTP:
- `host`, `port`, `username`, `password`, `root`, `timeout`
- FTP: `passive`, `ssl`
- SFTP: `private_key` optional

## Restore preparation

Le module ne force pas un restore automatique pour limiter les risques.
Il telecharge le backup distant vers:

`storage/app/backups/remote-downloads/`

ensuite vous pouvez lancer le process de restore local CATMIN.

## Notes production

- Utiliser un bucket/dossier dedie backup avec ACL restreinte.
- Activer TLS pour FTP (ou preferer SFTP).
- Changer regulierement les credentials.
- Verifier periodiquement les tests de connexion et la retention.
