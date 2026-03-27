# Module SEO Base (Prompt 042)

## But V1

Fournir un stockage SEO simple et reutilisable pour les modules contenus.

## Modele retenu

Table `seo_meta`:

- target_type
- target_id
- meta_title
- meta_description
- meta_robots
- canonical_url
- slug_override

## Pourquoi ce modele

- sobre pour V1
- reutilisable pour Pages, News, Blog, Shop
- evolutif vers des regles SEO plus avancees plus tard

## Integration

- CRUD admin leger via routes `admin.seo.*`
- helper `seo_for($targetType, $targetId)` pour recuperer les metadonnees
