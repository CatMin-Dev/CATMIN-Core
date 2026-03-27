# CATMIN Core Module

## Purpose
`Core` is the minimal and stable foundation module for CATMIN.
It provides only cross-cutting capabilities that other modules depend on.

## What belongs in Core
- Kernel-level shared services
- Read access to global settings
- Module state summary and dependency visibility
- Generic admin context metadata

## What must NOT go in Core
- Feature/business logic for content modules (Pages, Blog, News, Media)
- Feature/business logic for domain modules (Shop, Mailer, etc.)
- UI-specific implementations tied to one module only

## Minimal architecture
- `module.json`: module identity and provided capabilities
- `routes.php`: minimal diagnostic/admin routes
- `Services/`: shared foundation services
- `Controllers/`, `Models/`, `Views/`: reserved for future minimal extension

## Dependency role
All functional modules should depend on `core` first, then add domain-specific dependencies.
This keeps the dependency graph predictable and avoids circular architecture.
