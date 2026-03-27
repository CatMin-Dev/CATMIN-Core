# V2 248 - Shop Emails (Hardcore)

## Mission Output
Implement transactional email flow for order lifecycle.

## Email Events
- Order confirmation.
- Status changes (processing/shipped/completed/canceled).
- Invoice availability notification.

## Delivery Rules
- Trigger emails from validated workflow transitions.
- Queue email sending for reliability.
- Retry failed sends with logging.

## Template Requirements
- Clear customer-facing content.
- Branded but lightweight HTML.
- Plain-text fallback.

## Traceability
- Log send attempts, provider response, and final status.

## Result
Customer communication flow becomes automatic and reliable.
