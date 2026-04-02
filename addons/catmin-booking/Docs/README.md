# CATMIN Booking

## Presentation
Addon de réservation services/créneaux/réservations

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
- Route admin: `admin.addon.catmin_booking.index`
- Fichier routes: `routes.php`
- Controleur admin: `Controllers/Admin/*AdminController.php`

## Permissions
- module.booking.menu
- module.booking.list
- module.booking.create
- module.booking.edit
- module.booking.delete
- module.booking.manage_slots
- module.booking.api

## Events emis
- addon.catmin_booking.configured

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
