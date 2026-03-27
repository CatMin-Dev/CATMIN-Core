# V2 206 - Forms And Validation Audit (Hardcore)

## Scope
Targeted audit of admin forms and input validation implementation.

## Evidence Snapshot
- Most admin controllers validate inputs directly with `$request->validate(...)`.
- Broad modules coverage found: users, pages, articles, menus, settings, seo, shop, mailer, webhooks, media.
- Media upload includes array/file constraints, extension allowlist and size limits.
- Most state-changing forms are Blade forms with `@csrf`.

## Safe Zones
- Strong baseline presence of server-side validation in CRUD controllers.
- Use of max length, type and format rules (email/url/regex) in many modules.
- No obvious client-side-only trust pattern for sensitive writes.

## Incomplete Zones
- Validation rules are scattered across controllers (no FormRequest standardization).
- Permission checks are inconsistent at route level for several module endpoints.
- Some business constraints still rely on service logic rather than explicit validation contracts.

## Risks
1. Rule drift and inconsistent behavior between modules due to inline validation style.
2. Authorization bypass risk where routes are missing permission middleware.
3. Error payload consistency risk across modules (UX/API differences, support burden).

## Route/Module Links
- `modules/Users/Controllers/Admin/UserController.php`.
- `modules/Pages/Controllers/Admin/PageController.php`.
- `modules/Articles/Controllers/Admin/ArticleController.php`.
- `modules/Settings/Controllers/Admin/SettingsController.php`.
- `modules/Media/Controllers/Admin/MediaController.php`.
- `modules/Webhooks/Controllers/Admin/WebhookController.php`.

## Corrective Plan (V2)
- Introduce FormRequest classes per domain (users, pages, media, webhooks, settings).
- Add shared validation rule objects/helpers for repeated fields (slug, status, URL).
- Add route-permission parity checks in CI for state-changing endpoints.
- Add feature tests asserting invalid payload rejection and exact permission failures.

## Immediate Priority
Start with modules that mix rich payload + state changes: media, webhooks, users, settings.
