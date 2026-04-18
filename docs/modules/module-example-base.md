# Module Example Base

Module de reference: `modules/admin/cat-contract-demo`

## Objectif
Valider de facon concrete les contrats core/modules sans logique metier.

## Couvre
- Manifest V1
- Bootstrap provider
- Routes admin + settings
- Navigation sidebar principale + settings sidebar separee
- Bridges admin + front
- Block front + snippet card
- Permissions + settings
- Notifications + healthchecks
- Assets admin
- Documentation module
- Smoke test local module

## Structure figee (socle obligatoire)
- assets/
- bridge/
- docs/
- routes/
- services/
- snippets/
- tests/
- views/
- bridge/admin.bridge.php
- bridge/front.bridge.php
- views/front/block.php
- snippets/card.php

## Verification obligatoire avant livraison
- Commande reusable (tous modules):
	- `php scripts/tests/module-socle-smoke.php modules/admin/<slug-module>`
- Commande module local:
	- `php modules/admin/<slug-module>/tests/Smoke/ModuleBootTest.php`

Le module est conforme seulement si la structure socle, les sidebars (main + settings), ui.inject, blocks et bridges sont presents.

## Contrat navigation
- `navigation.sidebar[*]` injecte dans la sidebar principale
- `navigation.settings_sidebar[*]` injecte dans la navigation settings, pas dans la sidebar principale
- un module peut creer un groupe dedie via `group`, `group_label[_i18n]`, `group_icon`, `group_order`

## Contrat UI
- ne declarer `ui.inject` que pour des cibles reellement voulues
- ne jamais declarer `topbar.actions` par defaut sur un module exemple

## Duplication recommande
1. Copier `modules/admin/cat-contract-demo` vers un nouveau slug.
2. Renommer `module_id`, `name`, `permissions namespace`, routes, et labels.
3. Garder la structure de dossiers et le contrat manifest.
4. Ajouter le metier dans `services/` et `controllers/` sans toucher au core.

## Publication actuelle
- embarque dans le core CATMIN RC
- publiable separement sur le depot prive modules uniquement
- publication publique differee jusqu'au test de portabilite `DEMO -> DEMO2` sur hebergement clean
