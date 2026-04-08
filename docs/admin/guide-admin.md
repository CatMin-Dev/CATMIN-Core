# Guide Administrateur

## Objectif
Piloter CATMIN au quotidien avec une base operationnelle claire et securisee.

## Acces admin
- URL admin: `/<admin_path>/login`
- Compte principal: superadmin cree a l'installation
- Reset superadmin via UI: non disponible (volontaire)

## Sections principales
- `Dashboard`: etat global, versions, sante systeme
- `Staff / Admins`: comptes admin, statuts, roles
- `Roles & Permissions`: matrice permissions par module/action
- `Modules`: gestion activation modules
- `Settings`: general, securite, mail
- `Maintenance`: mode maintenance, backup, restore simule
- `Logs`: traces applicatives/securite/systeme

## Regles admin essentielles
- Ne pas partager le compte superadmin.
- Creer des comptes nominatifs pour chaque operateur.
- Utiliser roles minimaux (principe du moindre privilege).
- Verifier les logs apres toute operation sensible.

## Mode maintenance
- Activer depuis `Maintenance` ou `Settings`.
- Option admin autorise: permet l'acces admin pendant maintenance.
- Message maintenance: personnaliser avant activation.

## Backups
- Creation backup manuel depuis `Maintenance`.
- Entrees historisees en base dans `core_backups`.
- Restore actuel: mode simule (tracabilite + verification flux).

## Bonnes pratiques
- Verifier timezone et parametres mail apres installation.
- Verrouiller `install/` en production.
- Auditer les settings critiques apres mise a jour.
