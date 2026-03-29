# Performance Profiling CATMIN (Prompt 342)

Le socle performance CATMIN repose sur une approche simple et exploitable:

- instrumentation HTTP ciblee sur routes admin/API critiques
- comptage requetes DB et temps cumule par requete HTTP
- journalisation des requetes lentes
- mesure des jobs longs
- budgets de performance par zone critique
- page admin de lecture technique

## Ce qui est mesure

- `duration_ms` par requete HTTP critique
- `query_count`
- `slow_query_count`
- `total_query_time_ms`
- `memory_peak_bytes`
- routes/budgets en depassement
- jobs queue longs

## Budgets suivis

Budgets par defaut:

- login admin
- dashboard home
- monitoring center
- performance center
- logs
- queue
- settings
- pages/articles/media listings
- shop orders
- endpoints API publics critiques

Chaque budget expose:

```php
[
  'key' => 'admin.dashboard',
  'label' => 'Dashboard home',
  'route' => 'admin.index',
  'target_response_ms' => 350,
  'max_response_ms' => 700,
  'max_queries' => 18,
  'max_slow_queries' => 1,
]
```

## Reporting

Lecture admin:

- `GET /admin/performance` (`admin.performance.index`)

Lecture CLI:

- `php artisan catmin:performance:report`
- `php artisan catmin:performance:report --save`
- `php artisan catmin:performance:report --json`

Les rapports sauvegardes sont ecrits dans `storage/app/reports/`.

## Gouvernance requetes

Les requetes lentes (`db.query.slow`) sont journalisees dans `system_logs` canal `performance` avec:

- SQL compacte
- temps d execution
- route/path associee si disponible
- budget route si applicable

## Optimisations appliquees dans 342

- cache court sur le dashboard KPI pour eviter recalculs repetes
- cache court sur le widget monitoring du dashboard
- pagination API publique v2
- listings Pages / Articles / Media allegees par selection de colonnes utiles

## Que surveiller

- hausse des `budget_breaches`
- routes a `avg_queries` > budget
- memes requetes lentes repetitives
- jobs longs recurrents

## Strategie si depassement

1. verifier N+1 et eager loading
2. mutualiser les compteurs/KPI repetes
3. ajouter cache court sur indicateurs tres consultes
4. limiter les colonnes listees/API
5. deplacer les traitements lourds en queue