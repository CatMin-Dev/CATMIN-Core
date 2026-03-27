# V2 243 - Invoices Design And Generation

## Objective
Create a practical first invoicing layer for CATMIN.

## Scope
- Invoice data model.
- Clean invoice template design.
- HTML/PDF rendering path.
- Link invoices to orders.
- Prepare email delivery integration.

## Invoice Baseline
- Invoice number, issue date, due date.
- Customer billing identity.
- Order references.
- Line items, totals, taxes, currency.

## Rendering
- Canonical HTML template.
- PDF generation capability from the same source template.
- Print-friendly layout.

## Workflow
- Generate invoice on eligible order status.
- Keep immutable issued invoice snapshots.

## Result
A clean, usable invoice baseline ready for production refinement.
