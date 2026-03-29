# QA Final Gate V2 (Prompt 340)

Cette commande formalise la gate de qualite finale V2 avec un statut global:

- `READY`
- `NOT READY`

Le rapport couvre:

- checklist complete V2
- validations automatiques
- checklist manuelle
- validation securite
- validation performance
- validation UX
- criteres de release

## Commande

```bash
php artisan catmin:qa:final-gate --save
```

Options utiles:

- `--with-tests` : execute aussi `catmin:validate:v2-plus` avec tests automatiques
- `--strict-manual` : rend les checks manuels critiques bloquants
- `--json` : affiche le rapport JSON
- `--save` : ecrit JSON+Markdown dans `storage/app/reports`

## Exemple

```bash
php artisan catmin:qa:final-gate --with-tests --strict-manual --save
```

## Interpretation READY / NOT READY

Le statut est `READY` si:

- aucun blocker automatique critique
- criteres release critiques valides
- et en mode `--strict-manual`, tous les checks manuels critiques sont `PASS`

Sinon le statut est `NOT READY`.

## Sorties

Rapports generes dans:

- `storage/app/reports/qa-final-gate-YYYYmmdd-HHMMSS.json`
- `storage/app/reports/qa-final-gate-YYYYmmdd-HHMMSS.md`
