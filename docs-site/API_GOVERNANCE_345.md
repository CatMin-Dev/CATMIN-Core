# API Governance 345

## Objectif

Le prompt 345 renforce l'API CATMIN sur quatre axes:

- scopes API lisibles et extensibles
- rate limiting contextualise par profil d'endpoint
- detection minimale d'abus avec blocage temporaire
- observabilite API via logs et monitoring

## Convention de scopes

Scopes atomiques conseilles:

- `external.read`
- `external.write`
- `pages.read` / `pages.write`
- `articles.read` / `articles.write`
- `media.read` / `media.write`
- `shop.read` / `shop.write`
- `ops.read` / `ops.write`

Profils composites exposes dans config:

- `readonly`
- `content-manager`
- `shop-manager`
- `ops-reader`

Les profils ne remplacent pas les scopes reels. Ils ne font qu'etendre une liste vers des scopes explicites.

## Middlewares

- `catmin.external-api-key`: authentifie la cle API externe hachee
- `catmin.api-scope:scope.name`: impose un scope precis sur les routes v2 protegees
- `catmin.api-rate-limit:profile`: applique un profil de rate limiting JSON-coherent

## Rate limiting

Profils disponibles dans `config/catmin.php`:

- `public-read`
- `authenticated-read`
- `write`
- `sensitive`

Headers exposes:

- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-Catmin-RateLimit-Profile`
- `Retry-After` sur 429

## Anti-abus

Compteurs temporaires en cache pour:

- credentials invalides
- refus de scope repetes
- depassements rate limit repetes

Quand un seuil est depasse, l'identite IP/token est temporairement bloquee.

## Gouvernance tokens

La table `api_keys` porte maintenant:

- `last_used_at`
- `last_used_ip`
- `usage_count`
- `revoked_at`
- `created_by`

Le token n'est jamais stocke en clair.

Commandes utiles:

```bash
# Creation avec scopes explicites
php artisan catmin:api:key-generate integration-a --scope=external.read,pages.read

# Creation via profil de scopes
php artisan catmin:api:key-generate cms-reader --profile=readonly

# Revocation par id
php artisan catmin:api:key-revoke 12 --reason="partner offboarding"

# Revocation par nom
php artisan catmin:api:key-revoke cms-reader --by=name --reason="rotation trimestrielle"
```

## Ajouter un endpoint correctement

Exemple minimal recommande:

```php
Route::middleware([
	'catmin.external-api-key',
	'catmin.api-scope:pages.read',
	'catmin.api-rate-limit:authenticated-read',
])->group(function (): void {
	Route::get('/api/v2/pages/private-preview', ...);
});
```

Regles:

- choisir un scope atomique clair
- choisir un profil de rate limiting adapte a la criticite
- paginer les reponses de liste
- reutiliser `V2Response::success()` et `V2Response::error()`
- s'assurer que les refus 401/403/429 sont testés

## Recommandations X10 verifiees

- scopes fins: oui
- middleware scope dedie: oui
- throttling contextualise: oui
- blocage temporaire anti-abus: oui
- erreurs JSON coherentes: oui
- logs API enrichis: oui
- monitoring de warnings API: oui

## Limites restantes

- pas encore d'endpoints v2 write metier exposes
- pas encore d'UI admin de rotation/revocation de cles
- documentation endpoint par endpoint encore basique

Ces points restent de bons candidats pour une extension X10 ulterieure si vous voulez aller jusqu'a une administration API complete.