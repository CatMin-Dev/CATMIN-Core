# V2 228 - UI Verification Against Permissions (Hardcore)

## Mission Output
Verify end-to-end UI consistency with RBAC permissions.

## Goal
Ensure interface state (menus, buttons, forms, actions) always matches effective user permissions.

## Verification Areas
- Sidebar and topbar navigation visibility.
- List page action buttons (create/edit/delete/toggle).
- Detail page controls and admin widgets.
- Form submission pathways and post-action feedback.

## Core Rules
- Hidden action in UI does not replace backend authorization.
- If UI shows an action, backend must authorize it.
- If backend denies an action, UI should not expose it in normal flow.

## Test Plan
- Persona-based matrix (viewer/editor/manager/admin).
- Snapshot tests for key admin pages per persona.
- Attempted direct URL/action access without UI path must still be denied.
- Regression check after role reassignment.

## UX Requirements
- Consistent unauthorized messaging.
- No confusing dead links or visible disabled controls without explanation.
- Optional hint text when feature unavailable due to permissions.

## Documentation
- Publish UI permission matrix by page and action.
- Define checklist for new module UI contributions.

## Result
UI is aligned with RBAC reality, reducing confusion and unauthorized interaction attempts.
