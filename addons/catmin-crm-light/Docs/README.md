# CATMIN CRM Light

## Presentation
CRM léger: contacts, entreprises, notes et intégrations

## Version
- 1.0.0

## Categorie
- business

## Dependances modules
- core
- users
- settings
- logger
- mailer

## Routes
- Route admin: `admin.addon.catmin_crm_light.index`
- Fichier routes: `routes.php`
- Controleur admin: `Controllers/Admin/*AdminController.php`

## Permissions
- module.crm.menu
- module.crm.list
- module.crm.create
- module.crm.edit
- module.crm.delete
- module.crm.timeline

## Events emis
- addon.catmin_crm_light.configured

## Events ecoutes
- setting.updated

## Hooks UI utilises
- aucun

## Config disponible
- slug
- category

## Prochaines etapes
- Ajouter les ecrans metier dans `Views/admin`.
- Ajouter les services metier dans `Services`.
- Ajouter les migrations necessaires dans `Migrations`.
- Completer les listeners/events specifiques metier.
