# 008 — Convention modules activables/désactivables

## Objectif atteint
Mise en place d’une base de standardisation pour les modules CATMIN (sans implémentation finale du loader).

## Modules couverts
- Core
- Pages
- News
- Blog
- Media
- SEO
- Mailer
- Shop
- Users
- Settings

## Convention appliquée par module
Chaque module contient désormais:
- `module.json`
- `routes.php`
- `Controllers/`
- `Models/`
- `Views/`
- `Migrations/`
- `Services/`

## État d’activation initial
- `core` activé (`enabled: true`)
- autres modules désactivés (`enabled: false`)

## Dépendances déclarées
- Dépendances déclarées dans chaque `module.json` via `depends`.
- Exemple aligné sur le prompt: `blog`/`news` dépendent de `core`, `media`, `seo`.

## Cohérence avec la roadmap
- Cette étape est une convention structurelle.
- Le système runtime complet (enable/disable effectif + injection navigation) sera implémenté progressivement dans les prompts suivants.
