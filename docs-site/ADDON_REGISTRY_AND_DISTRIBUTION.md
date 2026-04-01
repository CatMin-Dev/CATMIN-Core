# Addon Registry And Distribution

## Objectif

Donner a CATMIN une base de distribution addon serieuse sans dependre d une marketplace SaaS.

## Contrat addon.json

Champs principaux:

- `name`
- `slug`
- `description`
- `version`
- `author`
- `category`
- `enabled`
- `dependencies`
- `required_core_version`
- `required_php_version`
- `required_modules`
- `has_routes`
- `has_migrations`
- `has_assets`
- `has_views`
- `has_events`
- `entrypoints`
- `permissions_declared`
- `homepage`
- `docs_url`
- `changelog`
- `compatibility`
- `install_notes`

## Strategie compatibilite

Avant installation d un addon distribue, CATMIN verifie:

1. version core minimale
2. version PHP minimale
3. modules requis actifs
4. addons dependants presents
5. structure du package
6. checksum SHA-256 si disponible

Statuts retournes:

- `compatible`
- `compatible_with_warnings`
- `incompatible`

## Strategie integrite

Paquet addon valide si:

- archive zip lisible
- `addon.json` present et valide
- structure coherente avec le manifest
- checksum package conforme si fourni

## Workflow installation

1. lecture package zip
2. verification checksum
3. lecture manifest
4. verification compatibilite
5. verification structure archive
6. extraction dans `addons/<slug>`
7. activation optionnelle
8. migrations optionnelles
9. log installation dans `storage/logs/addon-distribution.jsonl`

Commande CLI:

```bash
php artisan catmin:addon:install my-addon
php artisan catmin:addon:install --package=my-addon-1.2.0.zip
```

## Workflow update addon

1. comparer `installed_version` et version package
2. valider compatibilite cible
3. backup du dossier addon si ecrasement
4. installer le nouveau package
5. lancer migrations addon si necessaire
6. prevoir rollback futur via backup addon

## Registry

Registry local genere dans:

- `storage/app/addons/registry/index.json`

Il expose au minimum:

- slug
- nom
- version
- categorie
- auteur
- compatibilite
- dependances
- statut installe/active
- update disponible
- package file
- checksum

## Packaging addon

Commande:

```bash
php artisan catmin:addon:package my-addon
```

Sorties:

- archive zip dans `storage/app/addons/packages`
- checksum `.sha256`
- rebuild automatique du registry local

## Permissions recommandees

- `addon.registry.view`
- `addon.install`
- `addon.update`
- `addon.enable`
- `addon.disable`
- `addon.remove`

## UI admin

Page admin disponible:

- `admin/addons/marketplace`

Elle affiche:

- metadata addon
- compatibilite
- integrite package
- statut installe/active
- action installer / mettre a jour / activer / desactiver
