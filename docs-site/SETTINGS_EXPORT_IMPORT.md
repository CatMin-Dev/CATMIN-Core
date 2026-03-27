# Export / Import Settings CATMIN (V1)

## Objectif

Sauvegarder et reimporter les settings CATMIN dans un format lisible, avec des garde-fous.

## Format d'export

Format JSON `catmin.settings.export.v1`:

```json
{
  "meta": {
    "format": "catmin.settings.export.v1",
    "generated_at": "2026-03-27T10:00:00+00:00"
  },
  "settings": [
    {
      "key": "site.name",
      "value": "CATMIN",
      "type": "string",
      "group": "site",
      "is_public": false
    }
  ]
}
```

## Commandes

Exporter:

```bash
php artisan catmin:settings:export
php artisan catmin:settings:export storage/app/settings/projet-a.json --include-defaults
```

Importer (prudent):

```bash
php artisan catmin:settings:import storage/app/settings/projet-a.json --dry-run
php artisan catmin:settings:import storage/app/settings/projet-a.json --overwrite
```

## Comportement de securite

Par defaut, l'import:

- n'ecrase pas les cles existantes (sans `--overwrite`)
- ignore les cles protegees (`app.key`, `database.*`, etc.)
- valide le format minimal (`meta.format` + tableau `settings`)

Options avancees:

- `--allow-protected` pour autoriser les cles protegees

## Precautions

Avant import en production:

1. faire un export de sauvegarde
2. lancer un `--dry-run`
3. verifier les cles qui seraient modifiees
4. appliquer ensuite seulement

## Limites V1

- pas de diff detaille par cle en sortie standard
- pas de signature/chiffrement du fichier d'export
- pas de rollback automatique post-import
