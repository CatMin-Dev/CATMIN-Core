# Audit Admin et Tracabilite (V1)

## Objectif

Tracer les actions sensibles sans journaliser inutilement tout le bruit applicatif.

## Canal d'audit

Les actions critiques sont enregistrees dans `system_logs` avec:

- `channel = audit`
- `event` explicite
- contexte nettoye (champs sensibles masques)

## Evenements audites (base V1)

- `auth.login` / `auth.logout`
- `setting.updated`
- `module.enabled` / `module.disabled`
- `user.created` / `user.updated` / `user.toggled_active`
- `content.page.created` / `content.page.updated`
- `content.article.created` / `content.article.updated`

## Protection des donnees sensibles

Le contexte d'audit masque les cles sensibles:

- password
- password_confirmation
- token
- api_token
- secret
- authorization

## Consultation admin

Page existante:

- `admin/logs`

La vue permet de filtrer par canal, incluant `audit`.

## Limites V1

- pas de retention automatique/rotation des logs
- pas de signature d'integrite des entrees
- pas de moteur de recherche avance multi-critere
