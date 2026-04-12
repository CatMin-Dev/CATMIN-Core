# CAT-SLUG

Bridge de slug centralise pour CATMIN.

## Fonctions
- generation de slug depuis un texte source
- normalisation (accents, caracteres speciaux, separateurs)
- gestion collisions avec suffixes automatiques
- validation de format et disponibilite
- reservation SQL dans `mod_cat_slug_registry`

## API interne
- `generateAndReserve(entityType, entityId, sourceText, scopeKey, manualSlug?)`
- `validateInScope(slug, scopeKey, excludeEntity?)`
- `suggest(sourceText, scopeKey)`

## Settings
- `slug.max_length`
- `slug.separator`
- `slug.lowercase_only`
- `slug.allow_manual_override`
- `slug.transliteration_enabled`
