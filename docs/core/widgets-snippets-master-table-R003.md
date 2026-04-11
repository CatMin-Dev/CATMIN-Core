# CATMIN Widgets and Snippets Master Table (R003)

OBLIGATIONS ABSOLUES

- Mettre a jour le versioning a chaque modification significative
- Respecter le format de version defini par CATMIN
- Committer chaque etape de travail coherente
- Push sur le depot approprie
- Ne jamais oublier commit/push
- Ne jamais travailler sans versionner
- Ne jamais pousser sur le mauvais repo

## Tableau 1 — Widgets Dashboard

| Widget | Bridge source | Module consommateur | Effet | Cible |
|---|---|---|---|---|
| Recent content | CAT-ACTIVITY | CAT-DASHBOARD-INSIGHTS | Derniers contenus modifies | Admin |
| Pending review | CAT-WORKFLOW | PAGE / BLOG / INSIGHTS | Attentes validation | Admin |
| SEO incomplete | CAT-SEO-META | SEO-DASHBOARD / INSIGHTS | Champs manquants | Admin |
| Comments pending | CAT-COMMENTS | INSIGHTS / COMMENTS | Moderation | Admin |
| Audit critical | CAT-AUDIT | INSIGHTS | Alertes critiques | Admin |
| Notifications summary | CAT-NOTIFICATIONS | INSIGHTS | Resume alertes | Admin |

## Tableau 2 — Widgets Listing

| Widget | Bridge source | Module consommateur | Effet | Cible |
|---|---|---|---|---|
| Featured badge | CAT-PUBLISHING | BLOG / PAGE / DIRECTORY | Badge featured | Admin |
| SEO score badge | CAT-SEO-META | PAGE / BLOG / DIRECTORY | Score rapide | Admin |
| Tags chips | CAT-TAGS | PAGE / BLOG / DIRECTORY | Tags | Admin |
| Author badge | CAT-AUTHOR | BLOG / PAGE | Auteur | Admin |
| Relation count | CAT-RELATION | PAGE / BLOG | Liens | Admin |
| Category badge | CAT-CATEGORIES | BLOG / DIRECTORY | Categorie | Admin |

## Tableau 3 — Snippets Builder

| Snippet | Bridge source | Module consommateur | Effet | Cible |
|---|---|---|---|---|
| Hero block | CAT-CONTENT-BLOCKS | PAGE / BLOG | Hero | Builder |
| CTA block | CAT-CONTENT-BLOCKS | PAGE / BLOG | CTA | Builder |
| FAQ repeat | CAT-CONTENT-BLOCKS | PAGE / BLOG | FAQ | Builder |
| Gallery block | CAT-MEDIA-LINK | PAGE / BLOG | Galerie | Builder |
| Code block | CAT-CONTENT-BLOCKS | PAGE / BLOG | Code | Builder |
| Related content block | CAT-RELATION | PAGE / BLOG | Contenus lies | Builder |
| Author block | CAT-AUTHOR | BLOG / PAGE | Auteur | Builder |

## Tableau 4 — Snippets WYSIWYG

| Snippet | Bridge source | Module consommateur | Effet | Cible |
|---|---|---|---|---|
| Info callout | CAT-CONTENT-BLOCKS | PAGE / BLOG | Encadre info | Editeur |
| Warning callout | CAT-CONTENT-BLOCKS | PAGE / BLOG | Encadre alerte | Editeur |
| Quote enhanced | CAT-CONTENT-BLOCKS | PAGE / BLOG | Citation | Editeur |
| Tag inline | CAT-TAGS | BLOG / PAGE | Tag inline | Editeur |
| Media embed | CAT-MEDIA-LINK | PAGE / BLOG | Media | Editeur |
| Internal link suggestion | CAT-RELATION / SEARCH | PAGE / BLOG | Lien interne | Editeur |

## Tableau 5 — Snippets Relations/Navigation

| Snippet | Bridge source | Module consommateur | Effet | Cible |
|---|---|---|---|---|
| Breadcrumbs | CAT-MENU-LINK | PAGE / BLOG | Fil d’ariane | Front/Admin |
| Related links | CAT-RELATION | PAGE / BLOG | Liens lies | Front |
| Category pills | CAT-CATEGORIES | BLOG / DIRECTORY | Badges | Front |
| Tag cloud | CAT-TAGS | BLOG | Nuage tags | Front |
| Tag list comma | CAT-TAGS | BLOG / PAGE | Tags liste | Front |
| Author mini card | CAT-AUTHOR | BLOG | Auteur | Front |

## Tableau 6 — Snippets Marketing/SEO

| Snippet | Bridge source | Module consommateur | Effet | Cible |
|---|---|---|---|---|
| SEO score meter | CAT-SEO-META | SEO-DASHBOARD | Score | Admin |
| Missing meta alert | CAT-SEO-META | PAGE / BLOG | Alerte meta | Admin |
| Social preview | CAT-SEO-META | PAGE / BLOG | Preview social | Admin |
| Internal links hint | CAT-RELATION / SEARCH | BLOG / PAGE | Maillage | Admin |
| SEO quick summary | CAT-SEO-META | listing/detail | Resume SEO | Admin |

## Tableau 7 — Widgets/Snippets Front

| Widget/snippet | Bridge source | Module consommateur | Effet | Cible |
|---|---|---|---|---|
| Recent posts | CAT-BLOG | BLOG front bridge | Recents | Front |
| Featured posts | CAT-BLOG + CAT-PUBLISHING | BLOG front bridge | A la une | Front |
| Related posts | CAT-RELATION | BLOG front bridge | Lies | Front |
| Tag cloud | CAT-TAGS | BLOG front bridge | Nuage tags | Front |
| Author card | CAT-AUTHOR | BLOG / PAGE front bridge | Auteur | Front |
| Breadcrumbs | CAT-MENU-LINK | PAGE/BLOG front bridge | Navigation | Front |
| Form embed | CAT-FORMS | PAGE/BLOG front bridge | Formulaire | Front |
| File download list | CAT-FILE | PAGE/DIRECTORY front bridge | Downloads | Front |
