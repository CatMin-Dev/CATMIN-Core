# V2 249 - Order Workflow (Hardcore)

## Mission Output
Define and enforce coherent order state machine.

## Workflow Model
- States: pending -> paid -> processing -> shipped -> completed.
- Alternate paths: canceled, refunded.

## Transition Rules
- Explicit allowed transitions only.
- Guard conditions per transition (payment captured, stock allocated, etc.).
- Manual admin override with audit reason.

## Operational UX
- Admin sees available next actions per current state.
- Invalid transitions are blocked with clear feedback.

## Audit
- Log old/new state, actor, timestamp, reason.

## Tests
- Positive transition coverage.
- Negative transition rejection.

## Result
Order workflow is deterministic, auditable, and operationally safe.
