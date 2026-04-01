# Prompt 359 - Extension Contracts + Architecture Lockdown

Cette doc formalise les regles normatives pour etendre CATMIN sans casser la coherence V2.

## 1. Contrat Module

Structure minimale attendue pour chaque module:

- module.json
- routes.php
- Controllers/
- Views/
- Services/

Recommandes:

- Migrations/
- Docs/ ou README.md
- hooks.php (si extension UI/events)

Regles de manifeste module.json:

- Champs obligatoires: name, slug, version, enabled
- Version semver obligatoire x.y.z
- slug coherent avec le module charge
- depends explicite si dependances metier

Regles routes module:

- Toute route admin doit exposer un middleware catmin.permission
- Le mapping de permissions doit suivre la convention module.<slug>.<action>

Regles vues module:

- Interdit: acces DB direct dans les vues Blade (DB::, Schema::, ->table())
- La logique metier doit rester dans Services/Controllers

## 2. Contrat Addon

Structure minimale attendue:

- addon.json
- routes.php (si has_routes=true)
- Controllers/
- Views/
- Services/
- Migrations/ (si has_migrations=true)

Recommande:

- Docs/ ou README.md
- hooks.php

Regles addon.json:

- Champs obligatoires: name, slug, version
- required_modules doit inclure core quand requires_core=true
- permissions_declared obligatoire si routes admin
- ui_hooks au format before:<slot> ou after:<slot>

Regles d isolation addon:

- Interdit: couplage direct au core par chemin fichier
- Interdit: routes admin sans catmin.permission

## 3. Contrat Hooks + Events

- Utiliser CatminHookRegistry pour les injections UI
- Utiliser CatminEventBus pour les events metier
- Les hooks UI doivent etre nommes avant/apres un slot explicite
- Les payloads event doivent etre des tableaux assoc.

## 4. Contrat UI Admin

Pour conserver la coherence V2:

- Navigation admin: via config catmin.navigation (pas de hardcode sauvage)
- Dashboard widgets: via DashboardWidgetRegistry
- Settings pages: respecter la convention route + permission config/list
- CRUD pages: index/create/edit avec mapping permissions coherent

## 5. Contrat RBAC

Convention unique:

- module.<slug>.menu
- module.<slug>.list
- module.<slug>.create
- module.<slug>.edit
- module.<slug>.delete
- module.<slug>.config

Regles:

- Toute route admin sensible: middleware catmin.permission
- Super-admin conserve wildcard *
- Les extensions doivent declarer les permissions qu elles utilisent

## 6. Contrat transversal (settings/docs/monitoring/analytics)

- Settings: centraliser via SettingService/config; pas de hardcode disperses
- Docs: chaque extension doit exposer un point de doc de base
- Monitoring: erreurs/system events journalises proprement
- Analytics: emissions privacy-safe via service Analytics

## 7. Validateur de contrat

Nouvelles commandes:

- php artisan catmin:validate-extension <slug>
- php artisan catmin:validate-addon <slug>

Sortie JSON disponible avec --json.

Ces validateurs verifient notamment:

- presence des fichiers requis
- manifeste valide
- routes admin avec catmin.permission
- couplage core interdit (addons)
- acces DB interdit dans les vues

## 8. Lockdown Checklist

Avant merge d une extension:

- Le validateur extension passe
- Le validateur addon passe
- Pas de route admin sans permission
- Pas de SQL/DB en vue Blade
- Manifestes valides
- Documentation minimale presente

## 9. Integration V2+

La commande catmin:validate:v2-plus integre desormais un check extension_contracts pour rendre visibles les derives architecturelles avant release.
