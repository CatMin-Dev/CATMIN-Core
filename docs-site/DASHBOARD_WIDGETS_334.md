# Dashboard Widgets 334

## Objectif
Le dashboard admin devient un centre de pilotage metier/operations avec:
- KPI actionnables
- alertes visibles
- widgets operationnels
- extension modulaire par widget registry

## Services introduits
- `App\Services\Dashboard\DashboardKpiService`
  - centralise les KPIs et blocs dashboard (alerts, quick actions, module health, widgets par defaut)
  - toutes les requetes sont defensives (`Schema::hasTable/hasColumn`) pour rester robuste si module absent
- `App\Services\Dashboard\DashboardWidgetRegistry`
  - registre global de providers de widgets
  - tri par priorite provider puis `order` widget
  - `collect($context)` retourne les widgets normalises

## Convention d'injection widget module
Depuis `modules/<Module>/hooks.php`:

```php
use App\Services\Dashboard\DashboardWidgetRegistry;

DashboardWidgetRegistry::register(function (array $context): array {
    $dashboard = (array) ($context['dashboard'] ?? []);
    $kpiIndex = (array) ($dashboard['kpi_index'] ?? []);

    return [
        'id' => 'module-example',
        'title' => 'Mon widget module',
        'subtitle' => 'Sous titre',
        'tone' => 'info', // secondary|info|warning|danger
        'order' => 80,
        'items' => [
            ['primary' => 'Ligne 1', 'secondary' => 'Detail', 'meta' => 'Info'],
        ],
        'empty' => 'Aucune donnee.',
        'action' => [
            'label' => 'Ouvrir',
            'url' => route('admin.modules.index'),
            'permission' => 'module.core.list',
        ],
    ];
}, 80);
```

Notes:
- Le callback peut retourner un widget unique ou une liste de widgets.
- Le contexte expose `dashboard` (dont `kpi_index`) et `enabled_modules`.
- Si un module est desactive, son `hooks.php` n'est pas charge, ses widgets disparaissent automatiquement.

## KPIs calcules (base)
- Admins actifs (15m)
- Pages publiees / brouillons
- Articles publies / brouillons
- Medias total
- Commandes en attente
- Failed jobs
- Webhooks KO (24h)
- Erreurs critiques (24h)
- Emails echoues (24h)
- Produits stock bas

## Widgets operationnels (base)
- Derniers incidents critiques
- Failed jobs recents
- Webhooks en erreur
- Commandes recentes
- Derniers contenus modifies
- Alertes stock bas

## Performance / robustesse
- Requetes groupees et limitees (`limit(6)` en listes)
- aucune dependance forte sur un module: fallback automatique a 0/liste vide
- aucun crash dashboard si table absente
