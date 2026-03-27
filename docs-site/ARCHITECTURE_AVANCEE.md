# Architecture Avancee CATMIN

Ce document consolide les briques ajoutees apres la base V1 pour faciliter la reprise du projet.

## 1. Addons

Objectif:

- ajouter des extensions externes au noyau modules
- garder une installation simple projet par projet

Points clefs:

- declaration dans `addons/<slug>/addon.json`
- activation via etat persiste
- chargement routes/hooks uniquement si addon actif

Exemple `addon.json` minimal:

```json
{
  "name": "Example Addon",
  "slug": "example-addon",
  "version": "1.0.0",
  "enabled": true
}
```

Reference detaillee: `ADDONS_SYSTEM.md`

## 2. Versioning modules/addons

Objectif:

- normaliser les versions semver
- comparer correctement les upgrades

Regles:

- format attendu: `major.minor.patch`
- valeurs invalides marquees comme non conformes

Exemple simple:

- `1.2.0 > 1.1.9` -> upgrade autorise
- `1.0` -> normalise/valide selon regle service

Reference detaillee: `VERSIONING_MODULES_ADDONS.md`

## 3. Migrations modulaires

Objectif:

- executer migrations par extension (module/addon)
- eviter collisions de noms de migrations

Commande unifiee:

```bash
php artisan catmin:migrate:extensions
```

Bonnes pratiques:

- timestamp unique par migration
- noms explicites
- dry-run ou environnement de test avant production

Reference detaillee: `MIGRATIONS_MODULES_ADDONS.md`

## 4. Strategie de mise a jour

Objectif:

- preparer une mise a jour guidee sans automatisation opaque

Flux recommande:

1. planifier: `catmin:update:plan`
2. verifier et sauvegarder
3. appliquer: `catmin:update:apply`
4. valider routes, migrations, sante

Reference detaillee: `UPDATE_SYSTEM_CATMIN.md`

## 5. RBAC progressif

Objectif:

- remplacer progressivement le mode tout-ou-rien
- piloter menu et routes via permissions

Convention:

- `module.<slug>.<action>`
- ex: `module.pages.menu`, `module.settings.config`

Exemple middleware:

```php
Route::get('/users', ...)->middleware('catmin.permission:module.users.list');
```

Reference detaillee: `RBAC_PERMISSIONS.md`

## 6. API interne

Objectif:

- exposer des endpoints internes techniques
- proteger les actions sensibles par token interne

Exemple d'appel:

```bash
curl -H "X-CATMIN-TOKEN: <token>" https://example.com/api/internal/system/status
```

Reference detaillee: `API_INTERNE_REST.md`

## 7. Events et hooks

Objectif:

- declencher des reactions sans coupler fortement les modules

Cas couverts:

- module active/desactive
- setting modifie
- contenu cree/modifie

Exemple de hook addon:

```php
<?php

return [
    'catmin.setting.updated' => [
        static function (array $payload): void {
            // Logique custom
        },
    ],
];
```

Reference detaillee: `EVENTS_HOOKS_SYSTEM.md`

## 8. CLI CATMIN

Objectif:

- fournir des commandes operables sans interface admin

Exemples utiles:

- `catmin:modules:list`
- `catmin:addons:list`
- `catmin:migrate:extensions`
- `catmin:install:check`

Reference detaillee: `CLI_COMMANDS_CATMIN.md`

## 9. Installation et checks environnement

Objectif:

- standardiser l'installation et les validations minimales

Commande principale:

```bash
php artisan catmin:install:check
```

Checks couverts:

- version PHP et extensions
- connexion base de donnees
- permissions dossiers critiques
- variables d'environnement minimales

References detaillees:

- `INSTALLATION_GUIDE.md`
- `INSTALL_ASSISTANT_BASICS.md`

## 10. Reprise projet: ordre conseille

Pour reprendre CATMIN proprement apres une pause longue:

1. lire `DEVELOPER_GUIDE.md`
2. lire ce document
3. verifier l'etat via `catmin:system:check` et `catmin:install:check`
4. verifier modules/addons actifs
5. valider migrations en environnement local
6. reprendre fonctionnalite par fonctionnalite
