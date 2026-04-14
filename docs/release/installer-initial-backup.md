# CATMIN V1 - Installer Initial DB Backup (048)

## Ajout

L'installateur CATMIN génère désormais un backup initial de la base juste après l'exécution réussie et avant le lock final.

## Fonctionnement

- Génération dans `storage/backups/install/`
- SQLite: copie `.sqlite`
- MySQL/MariaDB: export `.sql`
- Token temporaire de téléchargement (15 min)
- Lien disponible sur l'étape `Report`
- Invalide après lock final

## Comportement en erreur

L'échec de génération backup est non bloquant:

- l'installation peut continuer
- l'échec est affiché
- l'échec est journalisé

## Logs

- génération backup initial
- téléchargement backup
- refus token invalide/expiré
