# CATMIN Import Export

Addon d'import/export multi-modules pour CATMIN.

## Cibles supportées
- `pages`
- `articles`
- `users`
- `booking`
- `crm`

## Formats
- JSON
- CSV

## Capacités
- export structuré par module
- import avec validation par ligne
- mode dry-run
- overwrite contrôlé par clé métier (`slug`, `email`, `confirmation_code`)
- journalisation dans `system_logs` (`import_export.export`, `import_export.import`)

## UI admin
- écran unique `admin.import_export.index`
- export direct en téléchargement
- import via upload ou collage de contenu
- aperçu du dernier résultat et logs récents
