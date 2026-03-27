# Audit Global V1 vers V2

## Objectif

Partir du code reel, des routes reelles et des modules reels pour qualifier l'etat actuel de CATMIN avant toute extension V2.

## Resume executif

Etat global: V1 exploitable mais heterogene.

Ce qui est clairement operationnel:

- socle admin Laravel
- auth admin session
- modules CMS principaux (pages, articles, media, menus, blocks)
- settings, migrations modules, module manager, logs, cache
- API interne minimale

Ce qui est clairement partiel ou fragile:

- RBAC applique de facon tres incomplete
- roles visibles mais sans vrai CRUD
- webhooks sortants prepares mais liaison metier non demontree
- queue/cron surtout orientes monitoring et declenchement manuel
- shop present mais desactive et tres partiel
- help center / documentation embarquee non presents comme modules V1

## 1. Noyau admin et navigation

Etat actuel:

- admin principal accessible via routes dediees et dashboard central
- tableau de bord, settings, modules, users, roles et contenu sont relies
- la navigation est pilotee par config + service

Risque:

- la coherence navigation / permissions n'est pas totale
- plusieurs zones sensibles restent visibles ou accessibles sans permission fine hors Users

Priorite:

- haute

Recommandation:

- conserver la base actuelle
- durcir la couverture permissions route par route avant d'etendre la V2

Fichiers / points touches:

- `routes/web.php`
- `app/Services/AdminNavigationService.php`
- `config/catmin.php`
- `app/Http/Controllers/Admin/DashboardController.php`

## 2. Routes web et pages admin reelles

Etat actuel:

- 99 routes applicatives sont exposees via `php artisan route:list --except-vendor`
- les modules Pages, Articles, Media, Menus, Blocks, Settings, Users, Logger, Cache, Cron, Queue, Webhooks ont des routes admin reelles
- les routes frontend de base existent (`/`, `/site`, `/page/{slug}`)

Risque:

- l'existence d'une page UI ne garantit pas une fonctionnalite complete
- plusieurs routes de monitoring n'ont pas d'action metier profonde associee

Priorite:

- moyenne

Recommandation:

- produire une matrice route -> action -> permission -> test pour la V2

Fichiers / points touches:

- `routes/web.php`
- `modules/*/routes.php`

## 3. Auth admin, roles et permissions

Etat actuel:

- auth admin session fonctionnelle
- contexte RBAC charge en session a la connexion
- middleware `catmin.permission` existe
- couverture fine appliquee seulement dans le module Users

Constat critique:

- la recherche `catmin.permission` ne remonte que `bootstrap/app.php` et `modules/Users/routes.php`
- les autres modules reposent presque exclusivement sur `catmin.admin`

Risque:

- faux sentiment de securite RBAC
- administration trop permissive hors Users

Priorite:

- critique

Recommandation:

- etendre le middleware permission a tous les modules sensibles avant d'ajouter des features V2 dependantes du RBAC

Fichiers / points touches:

- `app/Services/RbacPermissionService.php`
- `app/Http/Middleware/EnsureCatminPermission.php`
- `modules/Users/routes.php`
- `bootstrap/app.php`

## 4. Users et Roles

Etat actuel:

- Users dispose d'un CRUD reel avec affectation de roles et activation/desactivation
- la page roles existe
- le controleur Roles se limite a un listing

Faux positif:

- l'existence de `admin/roles/manage` peut faire croire a un module Roles complet alors qu'il n'y a ni create, ni update, ni delete dans les routes lues

Risque:

- impossibilite de piloter correctement le RBAC depuis l'admin

Priorite:

- critique

Recommandation:

- faire du CRUD Roles un chantier V2 prioritaire avant permissions fines par module

Fichiers / points touches:

- `modules/Users/routes.php`
- `modules/Users/Controllers/Admin/RoleController.php`
- `modules/Users/Services/UsersAdminService.php`

## 5. CMS principal: Pages, Articles, Media, Menus, Blocks, SEO

Etat actuel:

- les modules Pages, Articles, Media, Menus et Blocks ont chacun un vrai socle CRUD avec routes, services, vues et migrations
- les helpers frontend existent pour pages, menus, news/blog, media et blocks
- SEO existe comme module separable et branche sur les helpers

Risque:

- certaines integrations transverses restent legeres (SEO global, usage frontend avance, permissions fines absentes)

Priorite:

- moyenne

Recommandation:

- considerer ce bloc comme base V1 la plus mature
- completer surtout la securisation et les tests plutot que le rebatir

Fichiers / points touches:

- `modules/Pages/*`
- `modules/Articles/*`
- `modules/Media/*`
- `modules/Menus/*`
- `modules/Blocks/*`
- `modules/SEO/*`
- `app/Helpers/CatminHelper.php`

## 6. Modules techniques: Cache, Logger, Cron, Queue

Etat actuel:

- Cache: actions utiles de purge
- Logger: listing admin + logs application/admin/audit
- Cron: listing de taches et lancement manuel
- Queue: comptage jobs + suppression des jobs en echec

Limite reelle:

- Cron et Queue sont davantage des consoles de supervision / support que des systemes d'orchestration complets

Risque:

- survente de la capacite operationnelle si on les presente comme complets

Priorite:

- moyenne

Recommandation:

- en V2, distinguer clairement monitoring, replay, retry, scheduling reel et operations destructives

Fichiers / points touches:

- `modules/Cache/*`
- `modules/Logger/*`
- `modules/Cron/Controllers/Admin/CronController.php`
- `modules/Queue/Controllers/Admin/QueueController.php`

## 7. Webhooks et API

Etat actuel:

- API interne protegee existe sur `/api/internal/*`
- endpoint entrant webhook existe sur `POST /webhooks/incoming/{token}`
- CRUD admin des webhooks sortants existe
- service `WebhookDispatcher` existe

Point partiel:

- la presence du dispatcher ne prouve pas sa liaison metier effective aux evenements centraux
- aucune API externe publique versionnee n'est exposee a ce stade

Risque:

- fonctionnalite webhook surestimee si les evenements ne declenchent pas reellement les envois attendus

Priorite:

- haute

Recommandation:

- auditer puis relier explicitement les evenements sortants utilises
- separer clairement API interne et future API externe V2

Fichiers / points touches:

- `routes/api.php`
- `modules/Webhooks/routes.php`
- `modules/Webhooks/Services/WebhookDispatcher.php`
- `app/Http/Controllers/Api/Internal/*`

## 8. Shop et mailer

Etat actuel:

- Shop est present dans le code mais desactive
- le routeur Shop ne couvre que la gestion produit
- aucune evidence lue de categories, commandes, clients, factures ou paiement dans les routes exposees
- Mailer possede une gestion de config et de templates admin

Point partiel:

- Mailer n'expose pas encore un cycle complet d'administration (ex: delete template non visible dans les routes lues)
- Shop ne correspond pas encore au perimetre V2 annonce

Risque:

- gros ecart entre existence du module et maturite metier reelle

Priorite:

- haute

Recommandation:

- traiter Shop comme chantier V2 quasi neuf a partir d'un socle minimal existant
- professionnaliser Mailer avant d'en faire un bloc critique transactionnel

Fichiers / points touches:

- `modules/Shop/module.json`
- `modules/Shop/routes.php`
- `modules/Mailer/routes.php`
- `modules/Mailer/*`

## 9. Addons, versioning, updates, health, maintenance, backups

Etat actuel:

- base addons installables presente
- versioning, migrations extensions, update planner, checks install/systeme, maintenance mode et backup CLI existent
- ces briques sont documentees et branchees au socle

Risque:

- ce sont des bases serieuses mais encore peu eprouvees par une large couverture de tests automatises

Priorite:

- moyenne

Recommandation:

- conserver ces briques comme base V2 technique
- ajouter validation croisee et tests de non-regression avant industrialisation forte

Fichiers / points touches:

- `app/Services/AddonManager.php`
- `app/Services/VersioningService.php`
- `app/Services/HealthCheckService.php`
- `app/Services/BackupService.php`
- `app/Console/Commands/*`

## 10. Documentation embarquee et help center

Etat actuel:

- beaucoup de documentation technique existe dans `docs-site/`
- aucun module Help Center / viewer markdown integre n'est present dans `modules/`

Faux positif:

- la documentation projet existe, mais le systeme embarque admin annonce pour la V2 n'est pas encore livre comme fonctionnalite admin reelle

Risque:

- confusion entre documentation repository et documentation embarquee exploitable par un admin

Priorite:

- moyenne

Recommandation:

- traiter la documentation embarquee comme futur bloc V2 autonome, pas comme feature deja acquise

Fichiers / points touches:

- `docs-site/*`
- `modules/` (absence de module help/docs dedie)

## 11. Pages orphelines, liens morts, routes manquantes probables

Constats probables:

- pas de CRUD Roles derriere la page `roles.manage`
- pas de surface API externe reelle malgre l'ambition V2 API externe
- pas de help center admin relie au dashboard
- Shop visible dans le code mais desactive et non branche a un perimetre complet commande/client/facture

Risque:

- pages ou intentions produit surinterpretees par rapport au reel

Priorite:

- haute

Recommandation:

- produire ensuite une matrice specifique pages / routes / actions / permissions / tests

## 12. Risques de regression majeurs

- extension du RBAC sans couverture uniforme des routes
- activation du Shop sans terminer les dependances metier
- exposition webhooks/API sans modele de permissions et logs complet
- operations destructives admin peu cloisonnees hors module Users
- multiplicite des briques V2 recentes encore peu stabilisees par tests automatises

## 13. Dette V2 prioritaire

Tier 0:

- CRUD Roles complet
- couverture RBAC route par route hors Users
- verification des liaisons reelles WebhookDispatcher <-> evenements
- matrice pages/routes/actions/permissions

Tier 1:

- professionnalisation API externe
- durcissement securite formulaires/endpoints sensibles
- clarification operationnelle Queue/Cron
- Shop reel (commandes, clients, stock, factures)

Tier 2:

- help center embarque
- industrialisation tests / monitoring / perf

## Conclusion

CATMIN V1 n'est pas un prototype vide. Le noyau CMS/admin est reel et deja exploitable. En revanche, la V2 doit partir d'un constat net: RBAC, gouvernance des roles, API externe, shop reel et help center embarque ne sont pas encore au niveau d'un bloc termine. La bonne strategie V2 est donc une professionnalisation du reel, pas une fiction de completude.
