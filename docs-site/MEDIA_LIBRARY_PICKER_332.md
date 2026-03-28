# Media Library UX + Picker (Prompt 332)

## Objectif

Rendre la bibliotheque media exploitable au quotidien avec:
- recherche texte
- filtres et tri
- pagination
- upload drag and drop
- picker reutilisable pour les formulaires

## Bibliotheque media

Ecran `admin/media/manage`:
- recherche sur nom, alt, legende, chemin, mime, extension
- filtres dossier/type/periode
- tri: plus recents, plus anciens, nom, type
- pagination 12/24/48/96
- affichage en cartes avec preview image ou fallback extension

## Upload drag and drop

Le formulaire d'upload rapide sur la bibliotheque supporte:
- glisser/deposer
- multi-fichiers
- feedback de selection
- fallback clic/parcourir

Le traitement serveur reste le meme endpoint `admin.media.store` et les validations existantes sont conservees.

## Picker reutilisable

Composants Blade:
- `x-admin.media.picker-field`
- `x-admin.media.picker-modal`

Endpoints:
- `admin.media.picker` (JSON pagine)
- `admin.media.picker_item` (JSON detail par id)

Le picker est branche dans:
- Pages create/edit (`media_asset_id`)
- Articles create/edit (`media_asset_id`)

## Notes d'integration

- Le picker met a jour un champ hidden `media_asset_id`.
- Au chargement d'un formulaire en edition, la preview se recharge via endpoint JSON.
- Le composant est prevu pour etre rebranche ensuite sur Shop/Mailer sans duplication de logique.
