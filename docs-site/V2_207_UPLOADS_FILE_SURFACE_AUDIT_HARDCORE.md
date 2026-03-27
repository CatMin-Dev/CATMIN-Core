# V2 207 - Uploads And File Surface Audit (Hardcore)

## Scope
Targeted audit of file upload/storage and file-exposed surfaces.

## Evidence Snapshot
- Upload workflow centralized around Media module controller/service.
- Validation checks extension and max size before persistence.
- Files are stored via Laravel Storage disk APIs (`storeAs`, `exists`, `delete`).
- Metadata persisted includes mime, extension, size and uploader id.

## Safe Zones
- No direct raw filesystem move logic in controller paths.
- Storage abstractions reduce direct path traversal risk.
- Folder input is constrained by regex (`^[a-zA-Z0-9_\-]*$`).

## Incomplete Zones
- Allowed types include SVG and ZIP by default.
- No visible malware scan/quarantine pipeline.
- No explicit content-disarm/sanitization path for active content files.

## Risks
1. SVG script payload/XSS vector if rendered in unsafe context.
2. Archive abuse (zip bombs/nested payloads) if unpacked in future workflows.
3. MIME confusion risk where extension and effective content differ.
4. Operational risk without file scanning and strict serving policy.

## Route/Module Links
- `modules/Media/routes.php` (create/store/update/destroy upload flows).
- `modules/Media/Controllers/Admin/MediaController.php`.
- `modules/Media/Services/MediaAdminService.php`.
- `config/catmin.php` (`uploads.allowed_extensions`, `uploads.max_file_kb`).

## Corrective Plan (V2)
- Split allowlist into strict default profile + explicit opt-in profile.
- Gate SVG and ZIP behind dedicated feature flags and role permissions.
- Add async scanning hook before activation/public exposure.
- Enforce content-type verification with safe serving headers.
- Add immutable audit trail for upload/delete actions.

## Immediate Priority
Harden extension policy first (especially SVG/ZIP), then add scan/quarantine hooks.
