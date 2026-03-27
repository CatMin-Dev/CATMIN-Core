# Module Media Base (Prompt 041)

## Objectif V1

Poser une mediatheque simple et utile sans complexite excessive.

## Livrable

- upload
- listing
- apercu image
- edition metadonnees
- suppression controlee

## Donnees retenues

Table `media_assets`:

- disk, path, filename, original_name
- mime_type, extension, size_bytes
- alt_text, caption
- metadata (json)
- uploaded_by_id

## Integration CATMIN

- routes admin dediees: `admin.media.*`
- entree CMS `content/media` redirigee vers la vraie gestion module
- UI coherente avec pattern CRUD CATMIN
