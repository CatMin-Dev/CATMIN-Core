# Media Module

Base mediatheque CATMIN V1.

## Capacites V1

- upload de fichiers (images/documents)
- listing admin des medias
- apercu image simple
- suppression controlee (fichier + entree DB)
- metadonnees minimales (alt, caption, type, taille)

## Structure

- `Models/MediaAsset.php`
- `Services/MediaAdminService.php`
- `Controllers/Admin/MediaController.php`
- `Views/` ecrans CRUD simples
- `Migrations/` table `media_assets`

## Reutilisation future

Le module est prevu pour etre consomme par Pages, News, Blog, Shop via `media_asset_id` ou relation polymorphe ulterieure.
