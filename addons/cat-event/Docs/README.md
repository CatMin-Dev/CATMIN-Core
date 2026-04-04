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

## Parcours public (prompt 431)
- Route publique: `GET /events/{slug}`
- Inscription publique: `POST /events/{slug}/register`
- Confirmation signée: `GET /events/{slug}/confirmation/{participant}`

### Modes de participation
- `free_registration`: inscription directe, participant confirmé
- `approval_required`: inscription en attente (`pending`)
- `ticket_required`: CTA redirigé vers Shop bridge si actif
- `external_link`: CTA vers URL externe
- `disabled`: inscription fermée

### Règles de capacité
- capacité stricte respectée côté serveur
- liste d'attente possible via `allow_waitlist`
- idempotence formulaire via `form_token` + `idempotency_key`

### Statuts participant supportés
- `pending`, `approved`, `confirmed`, `cancelled`, `waitlisted`, `ticketed`, `attended`

## Cycle billet
1. **issued**: billet créé (source `manual|shop|import`) avec code unique + token.
2. **used**: billet validé au check-in (horodatage `used_at`).
3. **cancelled**: billet annulé (refusé au check-in).
4. **invalid**: billet marqué invalide (refusé au check-in).

Le code public reste rétro-compatible avec `ticket_number` et est exposé via `code`.

## Format QR / code
- Le QR encode un payload JSON:
	- `v`: version de payload
	- `type`: `catmin.event.ticket`
	- `event_id`
	- `ticket_code`
	- `token`
- Le rendu QR est stocké en data URI SVG dans `event_tickets.qr_code`.

## Flow check-in
1. Agent terrain ouvre `/admin/events/{event}/checkin` (mobile-friendly).
2. Saisie manuelle du code ou scan QR (caméra navigateur).
3. Validation de sécurité:
	 - événement non `cancelled|completed`
	 - ticket existant pour l'événement
	 - ticket non `cancelled|invalid`
	 - ticket pas déjà `used`
4. Si valide: ticket passe `used`, check-in créé, participant `attended`.
5. Si invalide/doublon: refus immédiat + log.

## Gestion doublons / annulations
- **Idempotence**: check-in verrouille le billet (`lockForUpdate`) pour empêcher les doubles validations concurrentes.
- **Doublon**: deuxième scan du même ticket => refus explicite.
- **Billet annulé/invalide**: refus explicite.

## Bridge Shop
- Le bridge `catmin-event-shop-bridge` continue d'émettre des billets via le flux participant.
- Les billets shop sont tagués source `shop` quand le service d'émission est utilisé avec cette source.
