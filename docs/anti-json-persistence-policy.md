# Anti-JSON Persistence Policy

## Scope

This workspace forbids JSON as a persistent business or administrative storage format when the data is queryable, editable, durable, or part of the normal application state.

## Forbidden

- Trusted devices stored in files such as `trusted-devices.json`
- CAT-MEDIA presets stored in settings keys such as `media.variant_presets_json`
- CAT-MEDIA fallback presets stored in settings keys such as `media.preset_fallback_key` or `media.preset_fallback_json`
- Crop geometry stored as a source-of-truth blob such as `crop_json`

## Required replacements

- Trusted devices must be stored in the relational table `core_trusted_devices`
- CAT-MEDIA presets must be stored in `mod_cat_media_variant_presets`
- CAT-MEDIA crop geometry must be stored in explicit relational columns such as `crop_x`, `crop_y`, `crop_w`, `crop_h`, `rotation`, `zoom`, `focal_x`, `focal_y`, `is_circle`
- `transform_payload` may exist only as constrained technical metadata and must not become a hidden business schema

## Allowed JSON cases

- Technical manifests, signatures, and checksums
- Logs, exports, fixtures, and one-shot migration inputs
- Legacy cleanup code whose only role is to migrate or purge forbidden JSON storage

## Guardrail

Run `catmin/scripts/guard-no-json-pseudo-bdd.sh` to detect forbidden markers outside approved migration-cleanup paths.