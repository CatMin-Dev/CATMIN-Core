# Queue Operations (Admin)

## Objectif

La page Queue admin est une console d'exploitation pour superviser les jobs `pending` et `failed`, relancer proprement et purger de maniere controlee.

## Actions disponibles

- Retry unitaire d'un job failed depuis la page detail job.
- Retry selected depuis la liste failed (selection multiple).
- Retry all sur tous les failed jobs.
- Delete unitaire d'un job failed depuis la page detail job.
- Clear selected depuis la liste failed (selection multiple).
- Clear all pour purger entierement la table `failed_jobs`.
- Ouverture detail job pour pending/failed.

## Filtres et lecture

- Filtre `status`: `all`, `failed`, `pending`.
- Filtre `queue`: cible une queue precise.
- Filtre `q`: recherche textuelle sur id/payload/exception.
- Pagination sur les listes pending et failed.

## Securite et permissions

- `module.queue.list`:
  - acces index queue
  - acces detail job pending/failed
- `module.queue.config`:
  - retry unitaire / selected / all
  - delete unitaire / selected / all

Les operations destructives et bulk exigent confirmation UI.

## Strategie de retry

- Prioriser `Retry selected` sur un sous-ensemble cible apres inspection.
- Utiliser `Retry all` seulement si l'incident est global et corrige.
- Verifier les causes (payload, exception, stack trace) avant relance de masse.

## Donnees sensibles

Le payload detaille est sanitise dans la vue detail:
- les cles sensibles (`password`, `token`, `secret`, `authorization`, `cookie`, `api_key`, `apikey`) sont masquees.

## Integration dashboard / alerting

- Les KPIs et alertes dashboard relies aux failed jobs redirigent vers `queue.index?status=failed`.
- Le lien rapide facilite le triage incident depuis le dashboard principal.
