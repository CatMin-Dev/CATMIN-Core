# CATMIN Trust Registry Remote Service (Future)

## Goal
Prepare a future optional remote trust service without making CATMIN core dependent on online infrastructure.

## Current behavior (core test phase)
- Remote sync is disabled by default.
- Local embedded keyring is always available.
- Local cache remains the mandatory fallback.
- Manual import of official keyring is supported from Trust Center.

## Planned endpoints
- `keyring.json`: known public keys.
- `trust-registry.json`: trusted publishers and scope mapping.
- `revocations.json`: revoked `key_id` list with reason/date.
- `publishers.json`: publisher trust declarations.
- `metadata.json`: future signed metadata/versioning.

## Security doctrine
- Public keys are public; private keys never live in CATMIN core.
- Core must not trust remote blindly.
- Embedded trust anchors are never removed by remote sync.
- Sync errors never block local trust verification.

## Cache and fallback
- Cache files:
  - `storage/trust/keyring-cache.json`
  - `storage/trust/trust-registry-cache.json`
- Core keeps working in local mode even when remote is unavailable.

## Rotation and revocation lifecycle
1. New key published (remote/import).
2. Previous key can become `deprecated`.
3. After grace period, key becomes `revoked`.
4. Signatures using revoked key are denied for install/update.

## Notes
This file documents architecture only. The service can be activated later when infrastructure is ready.
