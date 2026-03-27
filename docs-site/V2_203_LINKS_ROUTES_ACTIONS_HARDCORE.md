# Verification exhaustive des liens, routes et actions - Hardcore

## Methode

Analyse basee sur:

- navigation `config/catmin.php`
- resolveur `app/Services/AdminNavigationService.php`
- routes web et routes modules
- vues admin et vues modules

## Table de verite des interactions admin

| Emplacement | Libelle / interaction | Route / action visee | Etat actuel | Resultat reel | Permission attendue | Probleme constate | Correction recommandee |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Sidebar | Tableau de bord | `admin.index` | OK | ouvre dashboard | `module.core.menu` ou equivalent | pas de permission fine explicite observee | couvrir explicitement les routes core sensibles |
| Sidebar | Utilisateurs | `admin.users.index` -> redirect manage | OK | ouvre listing users | `module.users.list` | alias index/manage a clarifier | unifier noms + permissions |
| Sidebar | Roles | `admin.roles.index` -> redirect manage | Partiel | ouvre simple listing | `module.users.config` | pas de create/edit/delete | livrer CRUD Roles |
| Sidebar | Parametres | `admin.settings.index` -> redirect manage | Partiel | update globale possible | `module.settings.menu/config` | UI fine faible | enrichir gestion settings |
| Sidebar | Modules | `admin.modules.index` | OK | ouvre gestion modules | `module.core.config` | permission fine seulement au menu | proteger actions enable/disable/migrate |
| Sidebar | Logs | `admin.logger.index` | Partiel | listing + filtres | permission logger a definir | pas de delete / clear | ajouter retention / purge |
| Sidebar | Cache | `admin.cache.index` | OK | purge cache branchee | permission cache a definir | pas de permission fine observee | ajouter middleware permission |
| Sidebar | Planificateur | `admin.cron.index` | OK | listing + run task | permission cron a definir | couverture permission absente | ajouter permission dédiée |
| Sidebar | Queue | `admin.queue.index` | OK | listing + delete failed jobs | permission queue a definir | pas de retry/replay | etendre bloc queue |
| Sidebar | Webhooks | `admin.webhooks.index` | Partiel | CRUD admin branche | permission webhooks a definir | pas de send test en UI | ajouter action test |
| Sidebar CMS | Pages | `admin.content.show` -> `admin.pages.manage` | Partiel | CRUD sans delete | `module.pages.*` | delete absent | ajouter destroy si voulu |
| Sidebar CMS | Articles | `admin.content.show` -> `admin.articles.manage` | Partiel | CRUD sans delete | `module.articles.*` | delete absent | ajouter destroy si voulu |
| Sidebar CMS | Media | `admin.content.show` -> `admin.media.manage` | OK | CRUD principal branche | `module.media.*` | pas de bulk actions | ajouter bulk si necessaire |
| Sidebar CMS | Menus | `admin.content.show` -> `admin.menus.manage` | Partiel | menus + items sans delete | permission menus a definir | suppression non observee | ajouter delete menu/item |
| Sidebar CMS | Blocks | `admin.content.show` -> `admin.blocks.manage` | Partiel | CRUD sans delete | permission blocks a definir | delete absent | ajouter destroy |
| Sidebar Commerce | Shop | `admin.shop.manage` | Partiel | produit seulement, module desactive | permission shop a definir | perimetre commerce tres incomplet | ne pas activer trop tot |

## Verifications minimales par module

| Module | Index | Create | Edit | Delete | Toggle status | Bulk | Export | Import | Preview | Config | Test/send | Help/docs |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Users | Oui | Oui | Oui | Non | Oui | Non | Non | Non | Non | N/A | Non | Faible |
| Roles | Oui | Non | Non | Non | Non | Non | Non | Non | Non | N/A | Non | Faible |
| Settings | Oui | N/A | Global update | Non | Non | Non | Non | Non | Non | Oui | Non | Faible |
| Pages | Oui | Oui | Oui | Non | Oui | Non | Non | Non | Non observe | N/A | Non | Faible |
| Articles | Oui | Oui | Oui | Non | Oui | Non | Non | Non | Non observe | N/A | Non | Faible |
| Media | Oui | Oui | Oui | Oui | N/A | Non | Non | Non | Oui visuel partiel | N/A | Non | Faible |
| Menus | Oui | Oui | Oui | Non observe | Oui | Non | Non | Non | N/A | N/A | Non | Faible |
| Blocks | Oui | Oui | Oui | Non | Oui | Non | Non | Non | N/A | N/A | Non | Faible |
| SEO | Oui | Oui | Oui | Non | Non | Non | Non | Non | N/A | N/A | Non | Faible |
| Webhooks | Oui | Oui | Oui | Oui | Statut via edit | Non | Non | Non | N/A | N/A | Non | Faible |
| Mailer | Oui | Templates oui | Templates oui | Non | Non | Non | Non | Non | Non | Oui | Non | Faible |
| Cache | Oui | N/A | N/A | N/A | N/A | Non | Non | Non | N/A | Oui | Non | Moyen |
| Cron | Oui | N/A | N/A | N/A | N/A | Non | Non | Non | N/A | N/A | trigger manuel | Moyen |
| Queue | Oui | N/A | N/A | Failed jobs oui | N/A | Clear failed | Non | Non | N/A | N/A | Non | Moyen |
| Logger | Oui | N/A | N/A | Non | N/A | Non | Non | Non | N/A | N/A | Non | Moyen |
| Shop | Oui | Oui | Oui | Non | Oui | Non | Non | Non | Non | N/A | Non | Faible |

## Formulaires et actions JS

Etat observe:

- formulaires HTML classiques majoritaires
- confirmations inline sur certaines suppressions
- pas d'ecosysteme lourd de modales JS critique observe dans la surface lue
- pas de bulk actions JS significatives observees

Conclusion:

- la plupart des interactions sont server-side classiques et donc auditables par routes
- le manque principal n'est pas le JS fantome, mais l'absence d'actions metier completes

## Problemes transverses constates

1. CRUD incoherent selon modules
2. delete absent dans de nombreux modules de contenu
3. roles faux-positifs (page presente, gestion absente)
4. send-test absent sur Mailer et Webhooks
5. import/export quasi absents partout
6. preview/help contextuel tres faibles
7. permissions fines non homogenes sur les routes

## Correctifs recommandes ensuite

1. definir un standard CRUD minimal par module
2. imposer une matrice permission par action
3. ajouter tests d'interaction admin critiques
4. fermer les trous delete / test / help les plus visibles
5. distinguer clairement modules complets vs modules de base uniquement
