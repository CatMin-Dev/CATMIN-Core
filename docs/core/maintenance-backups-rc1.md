# Maintenance / Backups / Restore - RC1

## Objectif

Sous-systeme d'administration robuste pour maintenance, sauvegardes, restauration, suppression et audit.

## Niveaux de maintenance

- Niveau 0: desactive, fonctionnement normal.
- Niveau 1: front bloque, admins autorises selon politique.
- Niveau 2: maintenance technique, acces admin limite, operations controlees.
- Niveau 3: maintenance lourde, superadmin + whitelists.
- Niveau 4: verrouillage total, superadmin whiteliste uniquement.

## Types de backup

- db_only
- files_only
- db_files
- full_instance
- pre_update_snapshot
- pre_restore_snapshot

## Format

Chaque backup RC1 stocke:

- backup_format_version
- core_version
- backup_type
- created_at
- origin
- manifest JSON (contenu reel detecte)
- checksum + taille
- statut integrite + marquage orphelin
- metadata auteur

## Restore

Options disponibles:

- restore DB only
- restore files only
- restore full
- dry-run
- snapshot auto pre-restore

Comportement actuel:

- DB restore automatise: support SQLite (archive contenant database.sqlite ou dump SQL)
- Files restore: extraction du prefixe files/ depuis archive backup
- Full restore: DB puis fichiers

## Suppression robuste

Flux de suppression:

1. verification index logique
2. verification fichier physique
3. lock anti-concurrence
4. suppression physique
5. suppression tracking
6. si fichier manquant: marquage orphelin puis reparation index explicite

## Audit

Table dediee: core_maintenance_audit

Actions journalisees:

- backup.create
- backup.delete
- backup.orphan.repair
- backup.restore
- backup.restore.dry_run

Contexte enregistre:

- actor_user_id
- actor_username
- ip_address
- action/result/message
- contexte technique JSON

## Limites connues

- Restore DB automatise complet cible SQLite. Pour MySQL/MariaDB, une extension de restore guide est requise.
- Les fichiers restaures se limitent au contenu versionne dans l'archive (prefixe files/).
