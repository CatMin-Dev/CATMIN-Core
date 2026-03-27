# V2 244 - Shop Orders (Hardcore)

## Mission Output
Deliver real order management lifecycle.

## Requirements
- Order creation flow.
- Status model (`pending`, `paid`, `processing`, `shipped`, `completed`, `canceled`, `refunded`).
- History/timeline per order.
- User/customer linkage.

## Admin Actions
- Update status with validation rules.
- Add internal notes.
- Trigger downstream hooks (email/invoice) from status transitions.

## Integrity
- Enforce valid state transitions.
- Log actor and timestamp for each transition.

## Tests
- Positive and invalid transition tests.
- Order history consistency tests.

## Result
Order lifecycle is structured, traceable, and operationally clear.
