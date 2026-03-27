# V2 232 - Real Webhooks And Admin Linking (Hardcore)

## Mission Output
Wire incoming/outgoing webhooks to real domain events with operational visibility.

## Event Binding
- Map core events to dispatcher hooks:
  - content.published
  - content.updated
  - media.uploaded
  - user.updated (if allowed)
  - order.created/status_changed (shop)

## Incoming Webhooks
- Validate route token/signature.
- Persist execution logs with payload checksum.
- Trigger safe handlers (queued where expensive).

## Outgoing Webhooks
- Trigger per subscription + event matching.
- Record request body hash, response code, latency, retry count.
- Mark webhook health state from rolling success rate.

## Admin Observability
- Add dashboard cards:
  - active webhooks,
  - failures in last 24h,
  - mean latency.
- Add per-webhook timeline view with filters.

## Manual Verification
- Test endpoint per webhook config.
- Replay last payload for debug in non-production mode.

## Result
Webhooks are truly connected, measurable, and manageable from admin.
