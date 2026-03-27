# V2 232 - Real Webhooks And Admin Linking

## Objective
Connect CATMIN webhooks to concrete system events and admin visibility.

## Current Baseline
- Incoming webhook endpoint exists.
- Outgoing dispatcher exists.
- Logging exists but event linkage can be expanded.

## Integration Targets
- Content events (page/article published/updated).
- Media uploaded/updated/deleted.
- Shop/order lifecycle events where applicable.
- Settings/integration events for ops use.

## Admin Linkage
- Show webhook status in admin (last trigger, last response, failures).
- Display recent deliveries and retries.
- Provide manual test trigger in admin for configured webhook.

## Traceability
- Log payload summary, status code, latency, result.
- Correlate webhook events with source entity/action.

## Result
Webhooks become functionally useful, observable, and aligned with real CATMIN operations.
