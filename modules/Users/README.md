# Users Module

Module CATMIN responsable de la gestion de base des utilisateurs dashboard.

## Contenu de la base v1

- listing des utilisateurs
- creation de compte
- edition de compte
- activation/desactivation (si colonne users.is_active presente)
- association simple de roles existants

## Perimetre

- UX alignee avec le dashboard Bootstrap existant
- pas de RBAC avance dans ce prompt
- module branche via routes chargees dynamiquement par ModuleLoader

## Notes techniques

- Routes admin dans modules/Users/routes.php
- Controllers dans modules/Users/Controllers/Admin
- Logique metier dans modules/Users/Services/UsersAdminService.php
- Vues Blade module dans modules/Users/Views
- Migration optionnelle users.is_active dans modules/Users/Migrations
