# Bridge Dependency Matrix — CATMIN R002

**Statut**: Référence officielle post-R002  
**Date**: 2026-04-12  
**Auteur**: Décisions session R002 + validation utilisateur

---

## Règle fondamentale

```
Module maître → Bridge(s) uniquement
Module maître → Service(s) lecture-seule uniquement
Module maître ✗→ Autre module maître (INTERDIT)
Bridge → Bridge(s) amont uniquement (jamais de cycle)
Bridge ✗→ Module maître (INTERDIT)
```

---

## Couche A — Bridges (13 bridges fondamentaux)

| Bridge | Slug DB | Dépend de (bridges) | Consommé par (bridges) | Consommé par (masters) |
|--------|---------|---------------------|------------------------|------------------------|
| **CAT-SLUG** | cat-slug | — | cat-seo-meta, cat-menu-link, cat-search-index | cat-page, cat-blog, cat-directory, cat-forms |
| **CAT-SEO-META** | cat-seo-meta | cat-slug | cat-search-index | cat-page, cat-blog, cat-directory |
| **CAT-TAGS** | cat-tags | — | cat-relation, cat-search-index | cat-blog, cat-directory |
| **CAT-CATEGORIES** | cat-categories | — | cat-relation, cat-search-index | cat-blog, cat-directory |
| **CAT-AUTHOR** | cat-author | — | cat-search-index | cat-blog, cat-comments |
| **CAT-RELATION** | cat-relation | — | cat-search-index | cat-blog, cat-directory, cat-page |
| **CAT-MEDIA-LINK** | cat-media-link | — | — | cat-page, cat-blog, cat-directory |
| **CAT-MENU-LINK** | cat-menu-link | cat-slug | — | cat-page, cat-blog, cat-directory |
| **CAT-SEARCH-INDEX** | cat-search-index | cat-slug, cat-seo-meta, cat-tags, cat-categories, cat-author | — | cat-page, cat-blog, cat-directory |
| **CAT-WORKFLOW** | cat-workflow | — | cat-publishing | cat-page, cat-blog, cat-comments |
| **CAT-REVISION** | cat-revision | — | — | cat-page, cat-blog |
| **CAT-PUBLISHING** | cat-publishing | cat-workflow | — | cat-page, cat-blog, cat-directory |
| **CAT-CONTENT-BLOCKS** | cat-content-blocks | — | — | cat-page, cat-blog |

---

## Couche B — Modules maîtres (5 modules)

### CAT-PAGE
```
Dépendances bridges (obligatoires):
  cat-slug
  cat-seo-meta
  cat-content-blocks
  cat-media-link
  cat-publishing
  cat-workflow
  cat-revision

Dépendances bridges (optionnelles):
  cat-menu-link
  cat-relation
  cat-search-index

Services RO:
  CAT-MEDIA (lecture assets)
  CAT-FILEMANAGER (lecture fichiers)
```

### CAT-BLOG
```
Dépendances bridges (obligatoires):
  cat-slug
  cat-seo-meta
  cat-tags
  cat-categories
  cat-author
  cat-content-blocks
  cat-media-link
  cat-publishing
  cat-workflow
  cat-revision

Dépendances bridges (optionnelles):
  cat-relation
  cat-menu-link
  cat-search-index

Services RO:
  CAT-MEDIA
  CAT-FILEMANAGER
```

### CAT-DIRECTORY
```
Dépendances bridges (obligatoires):
  cat-slug
  cat-seo-meta
  cat-tags
  cat-categories
  cat-relation
  cat-search-index

Dépendances bridges (optionnelles):
  cat-media-link
  cat-menu-link
  cat-publishing

Services RO:
  CAT-MEDIA
```

### CAT-FORMS
```
Dépendances bridges (obligatoires):
  cat-slug

Dépendances bridges (optionnelles):
  cat-workflow
  cat-publishing

Services RO:
  CAT-MEDIA (uploads)
```

### CAT-COMMENTS
```
Dépendances bridges (obligatoires):
  cat-author
  cat-workflow
  cat-publishing

Dépendances bridges (optionnelles):
  cat-relation
  cat-notifications (service RO)
```

---

## Couche C — Modules service / lecture seule (6 modules)

| Module | Consomme (bridges, RO) | N'écrit jamais vers |
|--------|------------------------|---------------------|
| **CAT-SEO-DASHBOARD** | cat-seo-meta, cat-slug, cat-search-index | tous — lecture seule |
| **CAT-ANALYTICS** | tous (events agrégés) | tous — lecture seule |
| **CAT-ACTIVITY** | events core + modules | tous — lecture seule |
| **CAT-AUDIT** | events core + modules | tous — lecture seule |
| **CAT-NOTIFICATIONS** | events emis par bridges | tous — lecture seule |
| **CAT-DASHBOARD-INSIGHTS** | agrégation multi-bridges | tous — lecture seule |

---

## Graphe de dépendances par niveau

```
NIVEAU 0 (aucune dépendance bridge)
├── cat-slug          ← SOCLE ABSOLU
├── cat-tags
├── cat-categories
├── cat-author
├── cat-relation
├── cat-media-link
├── cat-workflow
├── cat-revision
└── cat-content-blocks

NIVEAU 1 (dépend de N0)
├── cat-seo-meta      ← dépend: cat-slug
├── cat-menu-link     ← dépend: cat-slug
└── cat-publishing    ← dépend: cat-workflow

NIVEAU 2 (dépend de N0 + N1)
└── cat-search-index  ← dépend: cat-slug, cat-seo-meta, cat-tags, cat-categories, cat-author

PRÉ-INFRA (non bridge, fondation technique)
├── CAT-MEDIA         ← assets
├── CAT-FILEMANAGER   ← fichiers
├── WYSIWYG           ← éditeur
└── PAGEBUILDER       ← structure visuelle

MODULES MAÎTRES (dépendent N0+N1+N2 + pré-infra)
├── CAT-PAGE          ← [prioritaire]
├── CAT-BLOG
├── CAT-DIRECTORY
├── CAT-FORMS
└── CAT-COMMENTS

SERVICES LECTURE (dépendent des bridges, jamais write)
├── CAT-SEO-DASHBOARD
├── CAT-ANALYTICS
├── CAT-ACTIVITY
├── CAT-AUDIT
├── CAT-NOTIFICATIONS
└── CAT-DASHBOARD-INSIGHTS
```

---

## Namespace permissions (par bridge)

| Bridge | Capabilities déclarées |
|--------|------------------------|
| cat-slug | `slug.read`, `slug.write`, `slug.delete`, `slug.reorder` |
| cat-seo-meta | `seo.read`, `seo.write`, `seo.delete` |
| cat-tags | `tags.read`, `tags.write`, `tags.delete` |
| cat-categories | `categories.read`, `categories.write`, `categories.delete`, `categories.reorder` |
| cat-author | `author.read`, `author.write`, `author.delete` |
| cat-relation | `relation.read`, `relation.write`, `relation.delete` |
| cat-media-link | `media-link.read`, `media-link.write`, `media-link.delete` |
| cat-menu-link | `menu-link.read`, `menu-link.write`, `menu-link.delete`, `menu-link.reorder` |
| cat-search-index | `search-index.read`, `search-index.sync`, `search-index.admin` |
| cat-workflow | `workflow.read`, `workflow.write`, `workflow.admin` |
| cat-revision | `revision.read`, `revision.restore`, `revision.delete` |
| cat-publishing | `publishing.read`, `publishing.draft`, `publishing.publish`, `publishing.admin` |
| cat-content-blocks | `content-blocks.read`, `content-blocks.write`, `content-blocks.delete`, `content-blocks.reorder` |

**Règle**: Tout accès à une capability non déclarée dans `bridge.contract.json` → policy bloque automatiquement. Aucun accès implicite.

---

## Règles d'isolement DB

| Règle | Description |
|-------|-------------|
| 1 bridge = 1 scope DB | Chaque bridge possède ses propres tables + ses propres migrations |
| Aucune migration transverse | Un module maître ne peut jamais inclure de migration touchant les tables d'un bridge |
| Aucune write cross-bridge directe | Interactions uniquement via API / events / sync du bridge concerné |
| Rollback isolé | Rollback d'un bridge ne touche que son propre scope |

---

## Politique de dépréciation d'un bridge

```
1. Annonce :          status = deprecated dans bridge.contract.json
2. Coexistence :      1 à 2 versions mineures/majeures du core
3. Alias :            alias_for = nouveau bridge_id (si faisable)
4. Migration auto :   migration_script si migration sûre
5. Suppression :      status = removed à date annoncée dans removal_target
6. Pendant coex :     uniquement correctifs critiques, aucune nouvelle feature
7. Visibilité :       avertissement admin + logs + CI actif dès status = deprecated
```
