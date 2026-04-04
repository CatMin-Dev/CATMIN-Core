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

## Moteur disponibilite V4 (prompt 432)

### Services metier
- `AvailabilityEngine`: calcule disponibilite, blocages, conflits
- `BookingPolicyService`: regles de statut, capacite, fermetures
- `BookingCalendarService`: expose les donnees calendrier (jour/semaine/mois)

### Capacite et conflits
- capacite par slot
- surbooking optionnel (`allow_overbooking`)
- fermeture manuelle (`status=closed|blocked`, `blocked_reason`)
- collision de creneaux avec buffers service (`buffer_before_minutes`, `buffer_after_minutes`)
- prevention doublon email/slot

### Etats reservation
- `pending`, `confirmed`, `cancelled`, `completed`, `no_show`

### API interne calendrier
- `GET /admin/booking/api/calendar?from=...&to=...&booking_service_id=...`
- `GET /admin/booking/api/slots/{id}` (detail + dernieres reservations)
