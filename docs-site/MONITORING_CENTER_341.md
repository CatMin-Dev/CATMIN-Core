# Monitoring Center CATMIN (Prompt 341)

Le Monitoring Center consolide les signaux ops dans une vue unique:

- etat global (`ok`, `warning`, `degraded`, `critical`)
- checks consolides par domaine
- incidents correles
- clusters d alertes
- historique de snapshots

## Routes admin

- `GET /admin/monitoring` (`admin.monitoring.index`)
- `GET /admin/monitoring/incidents` (`admin.monitoring.incidents`)
- `POST /admin/monitoring/snapshot` (`admin.monitoring.snapshot`)

Permissions:

- `module.logger.list` (lecture monitoring)

## Modele unifie des checks

Chaque check expose:

```php
[
  'status' => 'ok|warning|degraded|critical',
  'domain' => 'queue',
  'title' => 'Failed jobs',
  'message' => 'failed_jobs=12, seuil_warning=5',
  'metric' => 12,
  'threshold' => 5,
  'checked_at' => now()->toIso8601String(),
  'actions' => [
    ['label' => 'Ouvrir Queue', 'route' => 'admin.queue.index', 'url' => '...'],
  ],
]
```

## Incident correlation

Les incidents sont consolides par fingerprint de check (`domain + title`) et enrichis:

- occurrences
- first_seen / last_seen
- severity/status courants
- recovered_at lors du retour a `ok`

Cela evite les doublons massifs sur des alertes identiques.

## Historisation

Tables:

- `monitoring_snapshots`
- `monitoring_incidents`

Schedulers:

- snapshot toutes les 5 minutes (`monitoring.snapshot`)
- purge snapshots > 30 jours (`monitoring.prune`)

## Extension module

Pour ajouter un check:

1. enrichir `App\Services\MonitoringService::collectChecks()`
2. respecter le format check unifie
3. definir au moins une action de remediation si possible

## Interpretation rapide

- `ok`: nominal
- `warning`: anomalie faible
- `degraded`: impact operationnel modere
- `critical`: impact fort / action immediate

Score global: 0-100, derive des severites des checks.
