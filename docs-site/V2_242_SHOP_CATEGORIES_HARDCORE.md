# V2 242 - Shop Categories (Hardcore)

## Mission Output
Implement robust category structure and product assignment behavior.

## Requirements
- Simple category hierarchy.
- Product assignment to one or multiple categories.
- Admin ordering/visibility controls.
- Fast filtering and navigation.

## Data Integrity
- Unique category slug.
- Optional parent category with cycle prevention.
- Safe delete strategy (reassign or block when products attached).

## Admin UX
- Category tree/list visualization.
- Drag/drop or explicit ordering field.
- Product count per category.

## Tests
- Category CRUD.
- Parent/child rules.
- Product assignment and filtering.

## Result
Category navigation and organization become clear and maintainable.
