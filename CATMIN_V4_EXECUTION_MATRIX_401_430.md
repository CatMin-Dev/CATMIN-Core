# CATMIN V4 — Roadmap Execution Matrix (401-430)

Date: 4 avril 2026
Scope: prompts 401-430
Source of truth used: prompts archived in `prompts/effectue/`, repository code state, latest commits on `main`.

## 1) Roadmap condensee (Sprint 1 -> Sprint 5)

### Sprint 1 (stabilisation metier public)
- Executer `431` Event pages publiques + registration/ticketing.
- Executer `432` Booking calendrier + capacite/disponibilite.
- Executer `434` Forms + leads intake.
- Gate: parcours publics end-to-end testables (event/bookings/forms).

### Sprint 2 (data business)
- Executer `433` CRM relations + pipelines.
- Connecter CRM <- forms/events/bookings (flux entrants unifies).
- Consolider traces audit + notifications critiques sur flux business.

### Sprint 3 (packaging ecosysteme)
- Executer `435` packs addons V4.
- Stabiliser la matrice d'installation par bundle (core-only / content / business).
- Gate: installation reproducible par pack + rollback documente.

### Sprint 4 (admin navigation refonte)
- Executer `436` a `445` (navigation, IA, sidebar, topbar, layout dashboard, notifications UX, right panel, CRUD standards).
- Gate: coherence UX admin et zero regression RBAC/navigation.

### Sprint 5 (admin excellence + audit final)
- Executer `446` a `450` (finder, timeline, onboarding, templates, consolidation design gate).
- Gate: audit UX final, dette priorisee V5, readiness de release V4 stable.

## 2) Matrice d'execution (401-430)

| Prompt | Domaine | Type | Priorite | Dependances | Statut | Livrable attendu | Risque | Notes |
|---|---|---|---|---|---|---|---|---|
| 401 | WYSIWYG maison Bootstrap 5 | core | P0 | editor base, sanitization | done | editeur riche interne | moyen | base fondatrice V4 |
| 402 | Preview + publication differee | core | P1 | 401, pages/articles | done | preview + schedule flow | moyen | verifier scheduler en prod |
| 403 | Soft delete + corbeille | core | P1 | modules content | done | trash/restore contenu | faible | patterns reutilisables |
| 404 | Bulk actions standardisees | core | P1 | 403 | done | multi-actions listings | moyen | attention permissions fines |
| 405 | SEO sitemap robots | core | P1 | pages/articles frontend | done | meta + sitemap + robots | faible | lie au public routing |
| 406 | Tags/categories propre | core | P1 | articles | done | taxonomie articles propre | faible | prerequis contenu avance |
| 407 | Extraction shop en addon | addon | P0 | addon system | done | `catmin-shop` decouple | moyen | bridge dependencies surveillees |
| 408 | Event module complet | addon | P0 | addon system, users/settings | done | `cat-event` full base | moyen | prerequis 409/427/431 |
| 409 | Bridge Event -> Shop | bridge | P0 | 407, 408 | done | emission billets via shop | moyen | capacite + idempotence critiques |
| 410 | Templates pret a installer | core | P1 | install/settings/content | done | templates bootstrap install | faible | facilite onboarding |
| 411 | Profil admin complet | core | P2 | users/security | done | profil + sessions + password UX | faible | complete 327 family |
| 412 | Media library V2 | core | P1 | media module | done | picker/dropzone UX | moyen | volumetrie a monitorer |
| 413 | Dashboard V4 visuel + KPI | core | P2 | monitoring/analytics | done | home KPI widgets | faible | complete 334 lineage |
| 414 | Webhooks anti-replay robustesse | core | P0 | webhooks/security | done | anti-replay + robustesse | moyen | rester strict sur idempotence |
| 415 | Upload MIME reel + hardening | core | P0 | media/security | done | upload security renforcee | faible | point securite critique couvre |
| 416 | Booking addon | addon | P1 | addon system | done | `catmin-booking` base | moyen | prerequis 432 |
| 417 | CRM light addon | addon | P1 | addon system | done | `catmin-crm-light` | moyen | prerequis 433 |
| 418 | Map geo addon | addon | P2 | addon system, media | done | `catmin-map` + API geojson | faible | reutilise en 426 public map |
| 419 | Import/export addon | addon | P1 | logger/settings/modules | done | `catmin-import-export` | moyen | data quality & dry-run |
| 420 | Performance cache V2 | core | P1 | cache/monitoring | done | cache strategy V2 | moyen | invalidation doit rester stricte |
| 421 | Notifications admin critiques | core | P1 | logger/monitoring | done | centre notifications | faible | complete ops tooling |
| 422 | I18n localisation base | core | P1 | locale/settings | done | FR/EN + selector | faible | prerequis docs/public i18n |
| 423 | Profile extensions addon | addon | P2 | users/settings | done | `catmin-profile-extensions` | faible | extensible pour CRM |
| 424 | Slider addon | addon | P2 | media/pages/articles | done | `catmin-slider` render/admin/tests | faible | prepare blocs 428 |
| 425 | Remote backups addon | addon | P0 | backup/logging | done | S3 + Google + SFTP + FTP | moyen | credentials ops governance |
| 426 | Frontend rendering public | core | P0 | pages/articles/menus/seo | done | front public minimal extensible | moyen | assets statiques fixes post-hotfix |
| 427 | Event QR/checkin attendance | addon | P0 | 408,409,security | done | tickets+QR+checkin idempotent | moyen | base terrain pour 431 |
| 428 | Builder snippets/blocs injection | core | P1 | 401,426 | done | registry snippets/blocs + panel | moyen | gouvernance contenus metier |
| 429 | Auto-scoping editor fields | core | P1 | 401,428 | done | resolver modes par champ/module | moyen | eviter mappings incoherents |
| 430 | Roadmap + matrice completion | tooling | P0 | 401-429 | done | present document + sequence V4 | faible | feuille de route execution |

Legend statut: `done` = implemente et merge sur `main`.

## 3) Carte core vs addons/modules

### Reste dans le core (V4)
- Systeme editor (modes, resolver, snippets/blocs registry).
- Frontend public minimal (routage + layout + SEO de base).
- RBAC / security / monitoring / notification engine.
- UX admin transverse (navigation, dashboard, CRUD standards).

### Sorti en addon (deja effectif)
- `catmin-shop`
- `cat-event`
- `catmin-event-shop-bridge` (bridge)
- `catmin-booking`
- `catmin-crm-light`
- `catmin-map`
- `catmin-import-export`
- `catmin-profile-extensions`
- `catmin-slider`
- `catmin-backup-s3`

### Devrait encore sortir (cible)
- Toute integration externe optionnelle future (API externe complete, connecteurs metier, analytics tiers).
- Packs metier verticaux (`435`) en addons composites.

### Optionnel (selon bundle client)
- Shop, Event, Booking, CRM, Map, Import/Export, Slider, Remote Backup.

## 4) Dependances critiques (suite execution)

- `431` depend fortement de `427` (tickets/check-in) et `426` (frontend public).
- `432` depend de `416` (booking addon base).
- `433` depend de `417` (crm light) + flux forms/events/bookings.
- `434` alimente `433` (leads pipeline).
- `436-450` depend de la stabilite CRUD, RBAC et dashboard deja en place.
- `451-455` (WYSIWYG wave) depend de `401 + 428 + 429` deja poses.

## 5) Reports V5 (postponed volontaire)

- Refonte UI/UX majeure complete front/public (design system full-scope).
- API externe write multi-tenant (si reintroduite, uniquement en addon dedie).
- Analytics avancees externes (BI, CDP, connecteurs SaaS) en addon.
- Automation metier poussee (workflows no-code, orchestration cross-addon).
- Features non-core verticales (gaming, loyalty complexes, segmentation predictive).

## 6) Etat reel et points bloquants

### Ce qui est fait
- 401-430 sont executes et archives, avec code merge sur `main`.
- Les blocs structurants V4 (editor, frontend public, event check-in, addons business) sont en place.

### Ce qui bloque la suite (non-bloquants techniques, mais execution)
- Priorisation stricte necessaire entre flux publics business (`431-434`) et refonte UX admin (`436-450`).
- Eviter de melanger livraison metier public et redesign admin dans le meme sprint.

### Recommandation de sequencing immediate
1. Enchainer `431`, `432`, `434`, puis `433` (flux business complet avant UX admin massive).
2. Lancer `435` pour packaging une fois flux business stabilises.
3. Ensuite executer bloc `436-450` en lot avec gates de regression.

---

Ce document est la reference d'execution V4 pour piloter la suite sans derive de scope.
