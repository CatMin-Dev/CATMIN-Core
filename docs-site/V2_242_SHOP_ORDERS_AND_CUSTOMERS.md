# V2 242 - Shop Orders And Customers

## Objective
Introduce real order and customer management baseline.

## Scope
- Order structure.
- Customer structure.
- Order statuses and consultation in admin.
- Core action history and linkage to invoices/emails.

## Orders Baseline
- Order identity, customer reference, totals, currency.
- Status lifecycle (pending, paid, processing, shipped, canceled, refunded).
- Timeline/history entries for admin traceability.

## Customers Baseline
- Customer profile with contact fields.
- Link to order history.
- Basic segmentation (active/inactive).

## Admin Views
- Order list with status/date filters.
- Customer list with order count/last activity.
- Order detail page with status transitions and notes.

## Future Linkage
- Invoice generation hooks.
- Transactional email triggers by order state.

## Result
Shop gets an operational order/customer base aligned with future billing and email workflows.
