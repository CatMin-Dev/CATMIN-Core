# CATMIN Base Bridges and Master Modules Map (R002)

OBLIGATIONS ABSOLUES

- Mettre a jour le versioning a chaque modification significative
- Respecter le format de version defini par CATMIN
- Committer chaque etape de travail coherente
- Push sur le depot approprie
- Ne jamais oublier commit/push
- Ne jamais travailler sans versionner
- Ne jamais pousser sur le mauvais repo

## Objectif

Cartographie officielle des bridges fondamentaux et des modules consommateurs.

## Philosophie

1. Bridge fondamental
2. Widget/snippet expose
3. Module maitre consommateur
4. Module de lecture/service

Le module maitre ne reimplemente jamais un bridge.

## Couche A — Bridges fondamentaux

### CAT-SLUG
- Role: generer slugs, normaliser, collisions, suffixes
- Consommateurs: CAT-PAGE, CAT-BLOG, CAT-DIRECTORY

### CAT-SEO-META
- Role: title, meta description, canonical, robots, open graph, social image, score SEO
- Consommateurs: CAT-PAGE, CAT-BLOG, CAT-DIRECTORY

### CAT-TAGS
- Role: table tags, autocomplete, liaison tag-entite, widgets/snippets
- Consommateurs: CAT-BLOG, CAT-PAGE (si active), CAT-DIRECTORY

### CAT-CATEGORIES
- Role: categories, hierarchie, liaison categorie-entite, widgets
- Consommateurs: CAT-BLOG, CAT-DIRECTORY, PAGE (cas specifiques)

### CAT-AUTHOR
- Role: profils auteurs, liaison user-profile, bio/avatar, widgets/snippets
- Consommateurs: CAT-BLOG, CAT-PAGE (si auteur active)

### CAT-RELATION
- Role: relations contenus, suggestions, widgets related
- Consommateurs: CAT-PAGE, CAT-BLOG, CAT-DIRECTORY

### CAT-MEDIA-LINK
- Role: featured media, galleries liees, usages media
- Consommateurs: CAT-PAGE, CAT-BLOG, CAT-DIRECTORY

### CAT-MENU-LINK
- Role: liaison entite-navigation, ordre/placement, breadcrumbs
- Consommateurs: CAT-PAGE, CAT-BLOG, CAT-DIRECTORY

### CAT-SEARCH-INDEX
- Role: indexation, mapping champs, reindexation
- Consommateurs: CAT-PAGE, CAT-BLOG, CAT-DIRECTORY

### CAT-WORKFLOW
- Role: etats editoriaux, transitions, review/approval
- Consommateurs: CAT-PAGE, CAT-BLOG

### CAT-REVISION
- Role: historique, snapshots, diff, rollback
- Consommateurs: CAT-PAGE, CAT-BLOG

## Validation
- Bridges distincts
- Modules maitres consommateurs uniquement
- Dependances explicites
