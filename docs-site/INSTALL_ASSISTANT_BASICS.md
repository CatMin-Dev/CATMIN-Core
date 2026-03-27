# Base d'assistant d'installation (V1)

## Positionnement

CATMIN V1 n'embarque pas encore un wizard d'installation complet.

A la place, la base d'assistance repose sur:

- `catmin:install:check` pour les pre-requis
- documentation d'installation simple
- commandes CLI explicites (migrations, RBAC, checks)

## Etapes candidates pour un futur assistant web

1. Check environnement
2. Check DB
3. Initialisation schema
4. Initialisation roles/permissions
5. Verification finale

Cette feuille de route permet d'ajouter un assistant ulterieur sans casser le workflow CLI actuel.
