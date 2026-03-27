# V2 243 - Shop Stock (Hardcore)

## Mission Output
Implement reliable stock management with operational controls.

## Core Behavior
- Initial stock setting per product.
- Stock decrement on order confirmation.
- Optional stock reservation window.
- Out-of-stock status handling.

## Controls
- Low-stock alerts.
- Prevent oversell on concurrent orders.
- Manual stock adjustments with audit reason.

## Integrity
- Transaction-safe stock updates.
- No negative stock quantities.

## Tests
- Decrement and rollback cases.
- Concurrent update collision handling.
- Out-of-stock transition behavior.

## Result
Inventory management becomes dependable and auditable.
