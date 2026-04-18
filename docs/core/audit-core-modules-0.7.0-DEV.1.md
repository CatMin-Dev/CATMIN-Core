# CATMIN Audit Core/Modules - 0.7.0-DEV.1

Date: 2026-04-18
Perimetre: CORE uniquement, sans patch metier module dans le noyau.
Reference de cadrage: prompts/catmin_audit_core_modules.md

## 1. Audit complet du CORE

### 1.1 Securite generale du CORE

Constats verifies:
- Session/cookie policy centralisee dans `core/security/SecurityManager.php` (secure, httponly, samesite=Lax).
- CSRF actif pour verbes mutateurs via `csrfCheckMiddleware()` dans `core/security/SecurityManager.php` et token session dans `core/security/CsrfManager.php`.
- Rotation CSRF active hors zone install (configurable) dans `core/security/SecurityManager.php`.
- Controle whitelist IP et audit des refus dans `core/security/SecurityManager.php` + `core/security/SecurityAuditLogger.php`.
- Verrou install applique avec redirection admin login dans `core/security/SecurityManager.php`.
- Middleware auth admin et reauth recente present dans `core/security/SecurityManager.php`.
- Headers de securite/CSP/noindex appliques par `core/security/HeaderManager.php` + `core/security/CspBuilder.php` + `core/security/SecurityManager.php`.
- Validation/normalisation settings par `core/settings-validator.php` et `core/settings-engine.php`.

Risques/limites observes:
- Niveau de rigueur heterogene entre handlers historiques dans `admin/routes.php` (grand fichier orchestration, risque de couplage).
- Une partie des checks de securite est fortement runtime-config dependante (erreurs possibles si permissions storage incorrectes).

### 1.2 Audit layout CORE et points d'ancrage

Points d'ancrage exploitable identifies:
- Sidebar admin: collecte d'entrees modules dans `admin/routes.php` (collecte `admin_sidebar` et `settings_sidebar` du manifest).
- Topbar/quick actions: pont dedie `core/topbar-bridge.php` + registre `core/topbar-registry.php`.
- Dashboard/system cards: pages admin extensibles via structure nav/settings dans `admin/routes.php`.
- Widgets/snippets: primitives presentes cote topbar/settings; pas encore un registre widget unifie et documente pour tous modules.

Conclusion section 1.2:
- Injection UI existe deja pour navigation/settings.
- Standardisation des hooks widgets/snippets a finaliser pour uniformite complete.

### 1.3 Audit moteur d'injection actuel

Capacites existantes:
- Decouverte modules par scope/type via `core/module-loader.php`.
- Manifest supporte (`manifest.json`, fallback `module.json`) avec validation par `core/module-validator.php`.
- Compatibilite et dependances: `core/module-compatibility-checker.php` + `core/module-dependency-resolver.php`.
- Etat runtime persistant: `core/module-state-store.php`.
- Snapshot runtime central: `core/module-runtime-snapshot.php`.
- Activation/desactivation runtime sans muter le manifest: `core/module-activator.php`.

Limitations actuelles:
- Standard manifest encore dual (`manifest.json` + legacy `module.json`) selon chemins.
- Chargeur tres route-centric; abstractions injection UI/events pas totalement homogenes.
- Audit de dependances inter-modules present mais non encore expose partout via une API contractuelle unique.

## 2. Cartographie des interactions CORE <-> Modules

### 2.1 Interactions possibles

Interactions deja praticables sans patch core metier:
- Sidebar admin: manifest `admin_sidebar` et `settings_sidebar` (lecture dans `admin/routes.php`).
- Routes admin/front: resolues via loader + zones manifest (`core/module-loader.php`, `core/module-runtime-snapshot.php`).
- Settings: pipeline central `core/settings-engine.php` + `core/settings-registry.php`.
- Permissions: socle RBAC/permissions dans tables core/admin et chargeurs associes.
- Assets/views: support declaratif manifest + conventions de dossiers modules.
- Notifications/system events: base presente, integrable via bridges et services core existants.

### 2.2 Routes

Zones cartographiees:
- Admin: `admin/routes.php`.
- Front: `front/routes.php`.
- Install: `install/routes.php`.
- Noyau routeur/dispatch: `core/router.php`, `core/route-dispatcher.php`, `core/router/Router.php`.

Types de routes identifies:
- Admin pages/actions (GET/POST) protegees middleware auth/csrf.
- Front public routes selon zone front.
- Install wizard routes dediees.
- Endpoints type AJAX via pattern request/method + `Request::isAjax()` dans `core/request.php`.
- API: pas de couche API REST unifiee premier-classe encore standardisee (possible mais non formalisee globalement).

## 3. Definition d'un module

### 3.1 Architecture minimale recommandee

Structure minimale viable:
- `manifest.json`
- `routes.php` (admin/front selon zones)
- `controllers/`
- `views/`
- `services/`
- `assets/`
- `permissions.php`
- `settings.php`

References:
- `docs/core/modules.md`
- `docs/modules/wave1-roadmap.md`
- `core/module-loader.php`
- `core/module-validator.php`

### 3.2 Navigation

Modeles supportes:
- Injection sous categories existantes via `admin_sidebar`.
- Entrees settings via `settings_sidebar`.
- Capacite sous-menu selon structure des items manifest et ordre UI (`admin/routes.php`, sections settings/sidebar).
- Gating par permissions via mecanismes auth/roles/permissions du core.

## 4. Manques actuels

### Non faisable proprement (aujourd'hui)
- API modules formalisee unique (contrat stable REST/AJAX inter-modules) non standardisee bout-en-bout.
- Registre widgets/snippets universel documente et stable pour tous contextes UI.

### Partiellement faisable
- Injection UI avancee cross-zones possible mais selon conventions non completement uniformes.
- Interactions modules->core riches possibles mais avec couplage a certains flux existants.

### Non securise / risque
- Erreurs de permissions storage peuvent casser des briques runtime (integrity reports, runtime config, sessions install).
- Gros fichier `admin/routes.php` augmente le risque de regressions de securite/maintenance.

### Non standardise
- Coexistence `manifest.json` et `module.json` legacy.
- Contrats de bridge/hook pas encore homogenes dans tous les domaines (widgets, snippets, notifications).

## 5. Livrable de synthese

- Audit CORE: sections 1.1 a 1.3 ci-dessus.
- Interactions CORE/modules: section 2.
- Routes: section 2.2.
- Standard module: section 3.
- Limites/recommandations: section 4 + section 6 ci-dessous.

## 6. Contraintes appliquees

- CORE non modifie pour ajouter une logique metier module specifique.
- Injection par contrats manifests/zones/dependances/settings/permissions.
- Modularite stricte preservee: runtime state store + snapshot central.

## 7. Recommandations finales

Priorite P1:
- Unifier strictement le standard manifest et deprecier `module.json` legacy.
- Stabiliser un contrat API/AJAX inter-modules officiel.
- Durcir les checks de permissions runtime storage au boot admin/install.

Priorite P2:
- Extraire des sous-domaines de `admin/routes.php` vers controllers/services plus atomiques.
- Standardiser un registre widgets/snippets global avec schema de validation.

Priorite P3:
- Documenter une matrice officielle des points d'ancrage UI et hooks events (source de verite unique).
- Ajouter tests integration inter-modules sur injection sidebar/settings/routes/permissions.
