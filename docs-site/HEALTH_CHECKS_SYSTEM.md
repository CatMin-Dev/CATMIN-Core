# Health Checks Systeme CATMIN (V1)

## Objectif

Fournir une base de checks utile, lisible et reutilisable pour diagnostiquer rapidement l'etat CATMIN.

## Checks couverts

- connexion base de donnees
- accessibilite dossiers storage/cache/logs
- accessibilite zone uploads media
- presence des modules critiques (`core`, `users`, `settings`)
- validite configuration minimale (`APP_URL`, admin path, credentials admin)

## Reutilisation

Les checks sont centralises dans `HealthCheckService` et utilises par:

- commande CLI `catmin:system:check`
- endpoint interne protege `GET /api/internal/system/health`

## CLI

```bash
php artisan catmin:system:check
php artisan catmin:system:check --json
```

Le statut final est `OK` uniquement si:

- tous les health checks sont OK
- aucune collision migration
- aucun probleme d'etat modules

## API interne (token requis)

```bash
GET /api/internal/system/health
```

Retourne:

- `200` si sante globale OK
- `503` sinon

## Limites V1

- pas de check performance avance (latence, charge)
- pas de checks externes (SMTP, services tiers)
- pas de dashboard visuel admin dedie (CLI/API en premier)
