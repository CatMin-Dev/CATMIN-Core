# Audit Global V1 vers V2 - Hardcore

## Positionnement

Ce document ne corrige rien. Il qualifie l'etat reel de CATMIN V1 a partir du code, des routes, des modules et des surfaces effectivement presentes.

## Lecture rapide

Verdict:

- base CMS/admin reelle et utilisable
- securite d'acces encore inegale
- gouvernance Roles/RBAC inachevee
- API externe non livree
- Shop et help center encore loin du perimetre V2 cible

## 1. Rapport structure par domaine

### Domaine: Routing web/admin

Etat actuel:

- `php artisan route:list --except-vendor` retourne 99 routes applicatives
- forte couverture admin sur CMS, settings, users, logs, cache, cron, queue, webhooks
- presence de routes dashboard de redirection (`admin.users`, `admin.roles`, `admin.settings`, `admin.modules`)

Risque:

- surface large mais niveau d'achevement inegal selon domaines

Priorite:

- haute

Recommandation:

- figer une matrice route -> permission -> action reelle -> test

Fichiers / points touches:

- `routes/web.php`
- `modules/*/routes.php`

### Domaine: Routing API

Etat actuel:

- API interne minimaliste exposee via `routes/api.php`
- endpoints lectures/settings/system uniquement
- protection token interne sur les endpoints systeme sensibles

Risque:

- aucune API externe produit/livable a ce stade

Priorite:

- haute

Recommandation:

- traiter l'API externe comme bloc V2 a construire, pas comme extension mineure

Fichiers / points touches:

- `routes/api.php`
- `app/Http/Controllers/Api/Internal/*`

### Domaine: Auth admin

Etat actuel:

- login/logout admin session fonctionnels
- compte legacy admin supporte
- contexte RBAC charge en session

Risque:

- auth exploitable mais encore tres dependante du legacy admin pour le mode super-permissif

Priorite:

- haute

Recommandation:

- renforcer ensuite sessions, politique mot de passe, anti brute-force, 2FA

Fichiers / points touches:

- `app/Http/Controllers/Admin/AuthController.php`
- `app/Services/RbacPermissionService.php`

### Domaine: Roles / permissions / RBAC

Etat actuel:

- convention permission existe
- middleware permission existe
- synchro roles systeme existe
- usage fin du middleware observe seulement sur Users

Risque:

- V1 donne l'apparence d'un RBAC large alors que la couverture reelle est tres partielle

Priorite:

- critique

Recommandation:

- etendre le controle permission a tous les modules sensibles avant d'ajouter de nouvelles surfaces admin

Fichiers / points touches:

- `app/Http/Middleware/EnsureCatminPermission.php`
- `modules/Users/routes.php`
- `config/catmin.php`

### Domaine: CRUD admin reel

Etat actuel:

- CMS principal mature: pages, articles, media, menus, blocks
- settings et users disposent d'une vraie logique admin
- modules techniques (cache, logger) ont des surfaces utiles

Risque:

- certains modules non CMS sont encore plus "console admin" que vrai produit fini

Priorite:

- moyenne

Recommandation:

- garder les CRUD CMS comme reference de qualite V1 pour les futures extensions

Fichiers / points touches:

- `modules/Pages/*`
- `modules/Articles/*`
- `modules/Media/*`
- `modules/Menus/*`
- `modules/Blocks/*`

### Domaine: Webhooks

Etat actuel:

- incoming webhook protege par token
- CRUD admin des webhooks sortants present
- dispatcher sortant present

Risque:

- absence de preuve directe dans cet audit que les evenements metier centraux utilisent effectivement le dispatcher

Priorite:

- haute

Recommandation:

- cartographier puis tester les liaisons evenement -> webhook sortant

Fichiers / points touches:

- `modules/Webhooks/Controllers/WebhookIncomingController.php`
- `modules/Webhooks/Services/WebhookDispatcher.php`
- `modules/Webhooks/routes.php`

### Domaine: Queue / Cron / Jobs

Etat actuel:

- Queue: supervision et purge des failed jobs
- Cron: listing taches disponibles et lancement manuel
- presence de base operationnelle mais pas de cockpit avance

Risque:

- confusion entre monitoring minimal et systeme d'exploitation industrialise

Priorite:

- moyenne

Recommandation:

- separer V2 en deux sujets: observabilite d'abord, orchestration ensuite

Fichiers / points touches:

- `modules/Queue/Controllers/Admin/QueueController.php`
- `modules/Cron/Controllers/Admin/CronController.php`

### Domaine: Uploads / fichiers

Etat actuel:

- module Media reel
- validations et durcissement ajoutes
- stockage public structure sous `storage/app/public/media`

Risque:

- pas de scan antivirus ni quarantaine

Priorite:

- moyenne

Recommandation:

- poursuivre le durcissement mais sans casser la mediatheque existante

Fichiers / points touches:

- `modules/Media/Controllers/Admin/MediaController.php`
- `modules/Media/Services/MediaAdminService.php`
- `config/catmin.php`

### Domaine: Logs / audit trail

Etat actuel:

- `system_logs` et module Logger reels
- erreurs application, actions admin et canal audit existent

Risque:

- base utile mais encore sans retention/rotation/recherche avancee

Priorite:

- moyenne

Recommandation:

- utiliser la couche actuelle comme fondation V2 audit, pas comme produit finalise

Fichiers / points touches:

- `modules/Logger/*`
- `docs-site/AUDIT_ADMIN_TRACE.md`

### Domaine: Shop

Etat actuel:

- module existe dans `modules/Shop`
- routeur couvre seulement produits
- module desactive selon `php artisan catmin:modules:list`

Risque:

- ecart important entre le mot "shop" et la realite metier livree

Priorite:

- critique

Recommandation:

- reconstruire la promesse Shop V2 autour de produits + categories + commandes + clients + factures, en assumant que V1 ne l'a pas deja livre

Fichiers / points touches:

- `modules/Shop/module.json`
- `modules/Shop/routes.php`

### Domaine: Mailer

Etat actuel:

- configuration et templates admin presents
- base d'email systeme existante

Risque:

- surface encore partielle pour un bloc "mailer pro"

Priorite:

- haute

Recommandation:

- ajouter vrai cycle template, tests d'envoi, journalisation et branding exploitable

Fichiers / points touches:

- `modules/Mailer/routes.php`
- `modules/Mailer/*`

### Domaine: Documentation embarquee

Etat actuel:

- documentation repository abondante dans `docs-site/`
- aucun module help/docs integre dans `modules/`

Risque:

- confusion entre documentation projet et documentation embarquee admin

Priorite:

- moyenne

Recommandation:

- considerer ce domaine comme non livre en V1 du point de vue fonctionnalite admin

Fichiers / points touches:

- `docs-site/*`
- `modules/` (absence de module docs/help)

## 2. Points reellement prets

- dashboard admin et socle auth session
- module manager et activation/dependances modules
- settings globales
- CMS principal (pages, articles, media, menus, blocks)
- logger, cache, check systeme/install, maintenance, backups CLI
- addons de base, versioning, migrations extensions, docs techniques

## 3. Points partiels

- RBAC global
- roles admin
- webhooks sortants relies au metier
- queue et cron industrialises
- mailer professionnalise
- shop complet
- documentation embarquee

## 4. Faux positifs detectes

### Faux positif: Roles semblent administres

Realite:

- page roles existe, mais pas de CRUD complet observe

Evidence:

- `modules/Users/routes.php`
- `modules/Users/Controllers/Admin/RoleController.php`

### Faux positif: RBAC semble partout

Realite:

- middleware permission existe, mais recherche globale ne remonte que Users pour l'usage module route par route

Evidence:

- `modules/Users/routes.php`
- `bootstrap/app.php`

### Faux positif: Shop existe deja vraiment

Realite:

- module desactive, routes uniquement produits, pas de surface commandes/clients/factures dans les routes lues

Evidence:

- `modules/Shop/module.json`
- `modules/Shop/routes.php`

### Faux positif: Documentation embarquee disponible

Realite:

- documentation repository oui, viewer/help center admin non observe comme module

Evidence:

- `docs-site/*`
- `modules/`

## 5. Anomalies critiques

1. RBAC tres partiellement applique
2. Roles non administrables serieusement depuis l'admin
3. Bloc Shop tres en dessous du perimetre V2 cible
4. API externe absente a ce stade
5. Liaison webhook sortant non prouvee par cet audit

## 6. Dette technique V2 prioritaire

### Priorite P0

- CRUD Roles complet
- permissions fines sur toutes les routes sensibles
- audit pages / routes / actions / permissions
- verification des events sortants webhook

### Priorite P1

- API externe securisee
- durcissement auth/sessions/2FA/rate limiting
- normalisation complete erreurs 401/403/419
- mailer professionnalise

### Priorite P2

- queue/cron plus operationnels
- docs embarquees reelles
- monitoring/performance/tests integres

## 7. Risques de regression par domaine

### Risque eleve

- auth / RBAC
- modules sensibles sans permissions fines
- API / webhooks

### Risque moyen

- queue / cron / jobs
- mailer
- shop lors d'une reactivation prematuree

### Risque plus maitrise

- CMS principal
- settings
- media de base

## 8. Conclusion de verite technique

CATMIN V1 est deja un produit administratif reel, surtout sur le noyau CMS et les briques techniques recentes. Mais la V2 doit partir d'une lecture lucide: les sujets gouvernance, securite complete, API externe, shop reel et documentation embarquee ne sont pas des finitions mineures. Ce sont des blocs structurants encore partiels ou non livres.
