# Settings Module

Module CATMIN pour administrer les parametres globaux essentiels.

## Base livree (prompt 033)

- interface admin simple de lecture/ecriture
- gestion des cles essentielles:
  - site.name
  - site.url
  - admin.path
  - admin.theme
  - site.frontend_enabled
  - site.registration_open
- affichage des valeurs stockees pour ces cles

## Integration

- routes module dans modules/Settings/routes.php
- controller admin dans modules/Settings/Controllers/Admin/SettingsController.php
- logique metier dans modules/Settings/Services/SettingsAdminService.php
- vue dans modules/Settings/Views/index.blade.php

## Limites volontairement assumees

- version simple sans usine a gaz
- admin.path est enregistre comme setting pour evolution progressive; le routage actif reste gouverne par la configuration actuelle
