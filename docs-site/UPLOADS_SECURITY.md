# Securite Fichiers et Uploads CATMIN (V1)

## Objectif

Renforcer la securite des uploads sans casser le module Media.

## Mesures appliquees

- whitelist d'extensions autorisees via `config/catmin.php`
- limite de taille par fichier (`max_file_kb`)
- validation Laravel `mimes` sur chaque upload
- verification serveur de l'extension avant stockage
- nom de fichier randomise (UUID)
- assainissement strict du nom de dossier
- contexte d'audit nettoye pour eviter fuite de secrets

## Configuration

Section:

- `catmin.uploads.allowed_extensions`
- `catmin.uploads.max_file_kb`

Exemple V1:

- images: `jpg,jpeg,png,gif,webp,svg`
- documents simples: `pdf,txt,csv,json`
- medias: `mp4,webm,mp3`
- archive: `zip`

## Bonnes pratiques complementaires

- limiter droits ecriture au strict necessaire sur `storage/app/public/media`
- ne jamais rendre executables les fichiers uploades
- scanner antivirus en amont si contexte sensible
- sauvegarder avant imports massifs

## Limites V1

- pas de scan antivirus integre
- pas de quarantine workflow
- pas de validation contenu profonde par type de fichier
