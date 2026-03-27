# V2 241 - Shop Products, Categories, Stock

## Objective
Transform Shop into a practical, maintainable commerce base.

## Scope
- Product model enrichment.
- Category structure.
- Product statuses and visibility.
- Pricing and stock baseline.
- Validation and admin operability.

## Product Baseline
- Core fields: name, slug, description, sku, status, visibility.
- Pricing fields: base price, currency, optional compare-at price.
- Inventory fields: stock quantity, low-stock threshold, stock status.

## Category Baseline
- Simple hierarchy support.
- Product-category assignment (many-to-many if needed).
- Admin filtering by category and status.

## Validation Rules
- Required product identity fields.
- Non-negative stock and prices.
- Unique SKU/slug policy.

## Admin Usability
- Product listing with filters (status/category/stock state).
- Create/edit forms with clear validation feedback.
- Visibility toggles for catalog publication.

## Result
Shop gains a credible product/catalog/inventory foundation fit for real operations.
