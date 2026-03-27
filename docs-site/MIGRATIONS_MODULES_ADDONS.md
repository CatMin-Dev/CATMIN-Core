# Migrations par Module et Addon (CATMIN V1)

## Convention des emplacements

- Modules: `modules/<Module>/Migrations/*.php`
- Addons: `addons/<addon>/Migrations/*.php`

Ces chemins restent compatibles avec les commandes Laravel `migrate --path=...`.

## Execution recommandee

Commande unifiee:

```bash
php artisan catmin:migrate:extensions
```

Options:

- `--modules` : uniquement modules actifs
- `--addons` : uniquement addons actifs
- `--dry-run` : affiche le plan sans execution

## Anti-collisions

Laravel enregistre les migrations par nom de fichier dans la table `migrations`.
Deux fichiers ayant le meme basename (meme nom de fichier) peuvent creer du desordre,
meme s'ils viennent de dossiers differents.

CATMIN V1 ajoute une garde:

- detection des collisions de basename entre modules/addons
- echec de la commande en cas de collision

## Bonnes pratiques de nommage

- utiliser un timestamp + slug source
- exemples:
  - `2026_03_27_120000_pages_create_pages_table.php`
  - `2026_03_27_121500_example_addon_create_example_logs_table.php`

## Base pour install/update futurs

Cette base permet de construire proprement:

- install initiale par extension
- updates incrementales par version
- orchestration centralisee sans casser la compatibilite Laravel
