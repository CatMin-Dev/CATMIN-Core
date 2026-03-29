# Security Hardening 343

## Objectif

Le prompt 343 ajoute une couche de hardening production orientee:

- security headers coherents
- guardrails de configuration sensible
- detection anticipee des derives de securite
- no-cache sur pages sensibles (auth/2FA/reset)

## Headers actifs

Middleware global: `App\\Http\\Middleware\\ApplySecurityHeaders`

Headers appliques quand `catmin.security.headers.enabled=true`:

- `Content-Security-Policy`
- `X-Frame-Options`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy`
- `Permissions-Policy`
- `Strict-Transport-Security` (production + HTTPS + option active)

## CSP par defaut

Politique configurable via `CATMIN_SECURITY_CSP`.

La valeur par defaut autorise les besoins admin/WYSIWYG classiques sans ouvrir les directives critiques:

- `default-src 'self'`
- `frame-ancestors 'none'`
- `object-src 'none'`
- `script-src` et `style-src` restent compatibles avec l existant (inline autorise)

## Pages sensibles no-cache

Le middleware applique automatiquement sur les chemins sensibles:

- `admin/login`
- `admin/forgot-password`
- `admin/reset-password`
- `admin/2fa/*`

Headers:

- `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`
- `Pragma: no-cache`
- `Expires: 0`

## Guardrails production

Service: `App\\Services\\SecurityHardeningService`

Checks controles:

- `APP_DEBUG` actif en production (critique)
- mot de passe admin faible/par defaut (warning/critique selon env)
- token API interne absent/faible (warning/critique selon env)
- webhook incoming secret absent/faible
- cookie session non secure en production (critique)
- couverture 2FA admin
- activation/configuration headers de securite

## Visibilite des alertes

Les guardrails remontent dans:

- Monitoring Center (`security` domain)
- `php artisan catmin:install:check`

Comportement:

- `critical` bloque le check install
- `warning` reste non bloquant mais visible dans le rapport

## Checklist pre-prod

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_SECURE_COOKIE=true`
- `CATMIN_API_INTERNAL_TOKEN` robuste
- `CATMIN_WEBHOOK_INCOMING_SECRET` robuste
- credentials admin non par defaut
- 2FA activee sur au moins un compte admin
- headers securite actifs
