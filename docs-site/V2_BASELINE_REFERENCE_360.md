# V2 Stable Baseline Reference (Prompt 360)

Ce document sert de baseline officielle V2 stable pour maintenance et regression control.

## 1. Version baseline

- Dashboard version: `config('app.dashboard_version')`
- Development phase: `config('app.development_phase')`
- Tag cible stable: `v2-stable-YYYY-MM-DD`

## 2. Modules supportes (baseline)

Reference runtime: modules actifs au moment du freeze via:

```bash
php artisan catmin:freeze:v2 --json | jq '.baseline.modules_enabled'
```

## 3. Addons supportes (baseline)

Reference runtime: addons actifs au moment du freeze via:

```bash
php artisan catmin:freeze:v2 --json | jq '.baseline.addons_enabled'
```

## 4. Fonctionnalites validees en V2

- admin auth + RBAC
- gestion modules/addons contractuelle
- pages/articles/media operationnels
- settings/docs/help center embarques
- observabilite + logs + monitoring
- release operations: backup/update/recovery/rollback

## 5. Hors scope connu

- redesign complet UI/UX admin
- composants visuels next-gen
- epics produit non critiques
- gaming/FiveM

## 6. Criteres de support V2

- hotfix securite et bugs critiques uniquement
- pas de nouvelles features majeures
- respect des contrats extension et guardrails

## 7. Regressions interdites pour V3

La future refonte V3 ne doit pas casser:

- modeles permissions/routes securisees
- contrat modules/addons manifestes
- chaine release/checklist/rollback
- compatibilite donnees et migrations existantes
