# Verification des liens, routes et actions

## Objectif

Verifier que les interactions admin CATMIN pointent vers de vraies routes et de vraies actions, et identifier les zones mortes ou inachevees.

## Resume rapide

Constat general:

- les liens majeurs de navigation existent et pointent vers des routes reelles
- plusieurs modules ont un socle CRUD partiel: create/edit/toggle mais pas delete
- certains ecrans donnent une impression de completude superieure a la logique reellement branchee

## 1. Navigation principale

Navigation analysee depuis:

- `config/catmin.php`
- `app/Services/AdminNavigationService.php`
- `routes/web.php`

Etat actuel:

- Tableau de bord, Utilisateurs, Roles, Parametres, Modules, Logs, Cache, Cron, Queue, Webhooks, Pages, Articles, Media, Menus, Blocks et Shop ont des routes cibles reelles

Conclusion:

- pas de lien principal clairement mort dans la navigation lue
- le probleme se situe surtout dans la profondeur des actions disponibles par module

## 2. Modules admin reels vs partiels

### Complets ou quasi complets sur les actions principales

- Media: index/create/edit/delete branches
- Webhooks: index/create/edit/delete branches
- Cache: index + actions de purge branchees
- Queue: index + suppression failed jobs branchee
- Cron: index + declenchement manuel branche

### Partiels

- Users: create/edit/toggle active, pas de delete
- Roles: listing seulement, pas de CRUD roles
- Pages: create/edit/toggle status, pas de delete
- Articles: create/edit/toggle status, pas de delete
- Blocks: create/edit/toggle status, pas de delete
- Menus: create/edit/toggle status, creation items, pas de delete item/menu observe
- SEO: create/edit, pas de delete
- Mailer: config + templates create/edit, pas de delete template
- Shop: produits create/edit/toggle, pas de delete et module desactive

## 3. Zones sans logique complete

### Roles

- la route `admin.roles.manage` existe
- le controleur Roles ne fait qu'un listing
- pas de create/edit/delete role observe

### Settings

- update globale presente
- pas d'UI d'administration fine setting par setting

### Shop

- routes produits presentes
- aucune evidence route de commandes/clients/factures dans la surface route actuelle

## 4. Actions manquantes les plus visibles

- delete page
- delete article
- delete block
- delete user
- CRUD roles complet
- delete seo entry
- delete menu / menu item
- delete mailer template
- clear/delete logs
- delete produit shop
- send test mail
- send test webhook
- preview contenu avant publication
- bulk actions (publish/delete/export/import)

## 5. Resultat reel

Etat actuel des interactions:

- navigation principale: branchee
- formulaires create/edit principaux: majoritairement branches
- actions delete: tres inegales selon modules
- bulk/import/export: presque absents
- help/docs contextuels: faibles ou absents selon modules

## 6. Recommandation immediate V2

Avant toute extension lourde:

1. produire une matrice par module des actions attendues vs actions reelles
2. fermer les faux positifs CRUD
3. harmoniser le minimum CRUD (index/create/edit/delete/toggle si pertinent)
4. brancher les permissions attendues sur toutes les routes sensibles

## 7. Fichiers de reference principaux

- `config/catmin.php`
- `routes/web.php`
- `modules/*/routes.php`
- `modules/*/Views/*.blade.php`
- `modules/Users/Controllers/Admin/RoleController.php`
