# Analytics Internes Privacy-Safe

## Pourquoi ce systeme

CATMIN distingue trois couches:

1. audit securite: qui a fait quoi (preuve)
2. monitoring technique: etat de sante de la plateforme
3. analytics produit/admin: usage et adoption

Ce document couvre la couche analytics produit/admin.

## Principes privacy-safe

- opt-in: collecte desactivable
- pas d envoi externe obligatoire
- payloads legers
- anonymisation possible de l acteur
- pas de contenu saisi en clair
- pas de mots de passe/tokens/cookies collectes

## Ce qui est collecte

Exemples d events:

- `admin.module.opened`
- `admin.action.performed`
- `auth.login.succeeded`
- `auth.login.failed`
- `article.created`
- `article.published`
- `addon.installed`
- `recovery.run`

Chaque event contient:

- event_name
- domain
- action
- status
- role (si disponible)
- route_name/path sanitisee
- contexte leger
- metadata legere
- timestamp

## Ce qui n est pas collecte

- mots de passe
- tokens/secrets/cookies
- contenu de formulaires
- keylogging
- donnees personnelles sensibles

## Configuration

Settings:

- `analytics.enabled`
- `analytics.retention_days`
- `analytics.anonymous_mode`
- `analytics.modules_tracked`

Par defaut, la collecte est desactivee.

## Retention et purge

- table: `analytics_events`
- purge: `php artisan catmin:analytics:prune`
- retention configurable (7 a 365 jours)

## Dashboard admin

Route:

- `admin/analytics`

Donnees exposees:

- volumes evenements
- top modules/domaines
- actions frequentes
- frictions (warning/failed)
- timeline recente

## API d emission

Depuis modules/addons:

```php
\App\Services\Analytics::track('shop.order.created', 'module', 'create', 'success', [
    'source' => 'shop',
]);
```

Rester sobre: pas de payload sensible.
