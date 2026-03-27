# Mode Maintenance CATMIN (V1)

## Objectif

Permettre un mode maintenance simple pour le frontend, sans casser la maintenance native Laravel.

## Principe

CATMIN ajoute un flag settings:

- `system.maintenance_mode` (boolean)

Quand actif:

- les routes frontend renvoient une page maintenance (HTTP 503)
- les routes admin restent accessibles

La maintenance native Laravel (`php artisan down`) reste independante.

## Commande

```bash
php artisan catmin:maintenance status
php artisan catmin:maintenance on
php artisan catmin:maintenance off
```

La commande affiche aussi l'etat Laravel natif pour clarifier la situation.

## Comportement attendu

- `catmin:maintenance on`: frontend CATMIN indisponible
- `catmin:maintenance off`: frontend CATMIN disponible
- `php artisan down`: maintenance globale Laravel, non modifiee par CATMIN

## Limites V1

- pas de planification horaire
- pas de bypass token pour frontend
- pas de bascule via interface admin (CLI seulement)
