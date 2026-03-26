# 007 — Arborescence cible CATMIN (préparée)

## Objectif atteint
Préparation de l’arborescence long terme sans migration brutale ni suppression de l’existant.

## Arborescence cible (état actuel)
- `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/` : socle Laravel présent.
- `resources/views/admin/` : amorcé pour l’intégration progressive admin.
- `resources/views/frontend/` : préparé pour la couche frontend Blade future.
- `modules/` : créé.
- `addons/` : créé.

## Dossiers métier créés dans `modules/`
- `modules/Core`
- `modules/Pages`
- `modules/News`
- `modules/Blog`
- `modules/Media`
- `modules/SEO`
- `modules/Users`
- `modules/Settings`
- `modules/Shop`

## Rôle futur de chaque zone
- `app/`: logique applicative Laravel (services, controllers, policies, etc.).
- `resources/views/admin`: intégration progressive du dashboard en Blade.
- `resources/views/frontend`: rendu frontend indépendant du dashboard admin.
- `modules/*`: fonctionnalités cœur activables par domaine métier.
- `addons/`: extensions additionnelles optionnelles.
- `dashboard/` (legacy conservé): référence fonctionnelle actuelle, non supprimée.
- `frontend/` (legacy conservé): base frontend PHP actuelle, non supprimée.

## Cohabitation de transition
- Les dossiers `dashboard/` et `frontend/` restent en place pendant la transition.
- Les nouveaux dossiers Laravel/modules servent de cible progressive.
- Aucune migration massive de fichiers legacy n’est faite à ce stade.

## Résultat
Arborescence long terme prête, compatible avec une migration incrémentale sans casse immédiate.
