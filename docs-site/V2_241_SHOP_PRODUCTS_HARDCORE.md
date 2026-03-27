# V2 241 - Shop Products (Hardcore)

## Mission Output
Deliver production-ready product management with complete CRUD and inventory-aware behavior.

## Functional Requirements
- Full CRUD for products.
- Active/inactive lifecycle state.
- Price and stock management.
- Product validation and DB consistency.
- Optional media/image binding.

## Data Model
- `products`: name, slug, sku, status, visibility, price, stock_qty, low_stock_threshold, metadata.
- Optional product media relation.

## Admin Operations
- Bulk activation/deactivation.
- Bulk stock/price update.
- Search by name/sku/slug.

## Integrity Rules
- SKU unique.
- Price >= 0.
- Stock >= 0.
- Inactive product not orderable.

## Tests
- CRUD and validation tests.
- Status toggle tests.
- Stock boundary tests.

## Result
Product domain is stable, coherent, and operationally exploitable.
