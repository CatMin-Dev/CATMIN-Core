# V2 211 - CSRF Hardening (Hardcore)

## Mission Output
Comprehensive CSRF verification across state-changing surfaces with hardening policy.

## Coverage Verification

### Forms (state-changing)
- Inventory review of module/admin Blade forms shows `@csrf` present for POST-based actions.
- Confirmed across users, pages, articles, media, menus, blocks, shop, webhooks, mailer, cache, cron, queue and admin logout.

### Routes
- State-changing admin routes are under `web` middleware groups (core + module route files).
- No detected write endpoints outside expected middleware for admin flows.

### Async Calls
- No current JS fetch/XHR mutation calls found in baseline scripts.
- Policy added for future async writes: mandatory `X-CSRF-TOKEN` from meta token.

## Non-CSRF Domains (Explicit)
- Internal API (`/api/internal`) uses token auth (`catmin.api-token`).
- Webhook incoming endpoint uses per-endpoint token and is intentionally stateless.

## Risks Remaining
1. Future async features could bypass CSRF if no shared helper is introduced.
2. Inconsistent 419 behavior could create support and UX issues.
3. Third-party/addon routes may add writes without following CSRF conventions.

## Hardcore Hardening Actions (Planned)
- Add mandatory security checklist in PR template for any new write route/form.
- Add test that rejects write requests without CSRF token in admin web context.
- Add test that all write routes expected in web context are not accidentally moved.
- Add central UI policy for 419 errors and session renewal guidance.

## Verification Matrix (Summary)
- Core admin login/logout: covered.
- Modules core/users/settings/pages/articles/media/shop: covered.
- Modules integrations (webhooks admin CRUD): covered.
- Roles/permissions admin actions: covered in existing web forms/routes.

## Result
No known CSRF hole identified in current rendered admin write flows. Hardening now shifts to automated guardrails, 419 consistency and future async discipline.
