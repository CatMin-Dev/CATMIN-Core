# SEO Module

Base SEO CATMIN V1, simple et reutilisable.

## Donnees supportees

- meta_title
- meta_description
- meta_robots
- canonical_url
- slug_override

## SEO avance leger (V1)

Le frontend applique un resolver SEO simple via `seo_meta_payload()`:

- canonical automatique (avec override via `canonical_url`)
- Open Graph de base (`og:title`, `og:description`, `og:type`, `og:url`, `og:site_name`)
- fallback global quand aucune entree ciblee n'est trouvee

Ordre de resolution:

1. override explicite passe au helper
2. enregistrement SEO cible (`target_type` + `target_id`)
3. enregistrement SEO global (`target_type = global` ou cible nulle)
4. fallback settings (`site.name`, `site.description`, `seo.meta_robots`) + URL courante

## Reutilisation inter-modules

Le couple `target_type` + `target_id` permet de rattacher une entree SEO a Pages, Articles, Shop, etc.

## Portee V1

- stockage et edition de base
- pas d'automatisation SEO avancee
- socle pret pour integration progressive dans les modules contenus
