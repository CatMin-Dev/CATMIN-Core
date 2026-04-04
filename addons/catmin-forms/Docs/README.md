# CATMIN Forms

Addon de formulaires publics configurables avec soumissions admin.

## Types supportes
- contact
- lead
- event_request
- booking_request
- custom

## Mapping metier
- `none`: stockage simple
- `crm_lead`: creation contact CRM (si addon CRM actif)
- `event_preregistration`: creation participant event pending
- `booking_request`: creation reservation booking pending

## Securite
- CSRF
- honeypot anti-spam
- rate limit frontend
- validation serveur stricte

## Extensibilite
Un addon peut ajouter son propre mapping en etendant `FormRoutingService`.
