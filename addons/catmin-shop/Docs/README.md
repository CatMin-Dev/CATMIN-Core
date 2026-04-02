# CATMIN Shop Addon

Addon e-commerce autonome pour CATMIN.

## Scope
- Produits et categories
- Clients
- Commandes et transitions de statut
- Factures et export PDF
- Parametres shop
- Permissions shop

## Dependencies
- core
- users
- settings
- media
- mailer

## Routes
Les routes admin sont exposees via `routes.php` et conservent les noms existants (`admin.shop.*`) pour compatibilite.

## Migrations
Les migrations sont dans `Migrations/` et executees via l'installation/activation addon.
