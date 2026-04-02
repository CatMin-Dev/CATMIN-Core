# cat-event — Addon Événements CATMIN

## Description
Addon de gestion complète d'événements : CRUD, sessions multi-dates, inscriptions participants, billets numérotés avec QR, check-in manuel/scan, notifications mail.

## Tables
| Table | Description |
|---|---|
| `events` | Événements principaux |
| `event_sessions` | Sessions secondaires (multi-dates) |
| `event_participants` | Participants inscrits |
| `event_tickets` | Billets numérotés |
| `event_checkins` | Historique des check-ins |

## Permissions
| Permission | Rôle suggéré |
|---|---|
| `module.events.menu` | Admin+ |
| `module.events.list` | Éditeur+ |
| `module.events.create` | Éditeur+ |
| `module.events.edit` | Éditeur+ |
| `module.events.delete` | Admin+ |
| `module.events.checkin` | Hôte+ |

## Routes principales
- `GET /admin/events` — Liste des événements
- `GET /admin/events/create` — Formulaire création
- `GET /admin/events/{id}/edit` — Édition + sessions
- `GET /admin/events/{id}/participants` — Inscrits
- `GET /admin/events/{id}/tickets` — Billets
- `GET /admin/events/{id}/checkin` — Check-in

## Intégrations
- **Logger** : toutes les actions sensibles sont journalisées
- **Mailer** : email de confirmation envoyé à l'inscription
- **Webhooks** : `event.created`, `event.participant.registered`, `event.checkin.done`
