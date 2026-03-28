# Super Admin Lock And Admin Recovery

## Objectif
Verrouiller la gouvernance du compte super-admin et fournir un flux securise de recuperation d'acces admin.

## Source de verite super-admin
- Modele: `admin_users`
- Indicateur: `is_super_admin` (bool)
- Etat actif: `is_active`

## Regles de gouvernance
- Interdiction de supprimer le dernier super-admin actif.
- Interdiction de desactiver le dernier super-admin actif.
- Interdiction de retirer le statut super-admin du dernier super-admin actif.
- Les blocages sont centralises via `SuperAdminGuardService`.
- Les tentatives bloquees sont journalisees (`super_admin.*.blocked`).

## Point d'application
- Enforcement global dans `AppServiceProvider` via hooks Eloquent `AdminUser::updating` et `AdminUser::deleting`.
- Le verrou est donc applique meme en cas d'appel direct modele/service.

## Flux forgot password admin
Routes admin publiques:
- `GET /admin/forgot-password` (`admin.password.request`)
- `POST /admin/forgot-password` (`admin.password.email`)
- `GET /admin/reset-password/{token}` (`admin.password.reset`)
- `POST /admin/reset-password` (`admin.password.update`)

## Securite du reset
- Token random 64 chars, stocke en hash SHA-256.
- Duree de vie configurable: `catmin.admin.password_reset_expire_minutes`.
- Token usage unique (`used_at` renseigne apres succes).
- Rate limiting dedie: `catmin-password-reset`.
- Message de retour non verbeux pour ne pas reveler l'existence du compte.
- Logs d'audit pour demande, succes, token invalide/expire.

## Stockage tokens
Table: `admin_password_reset_tokens`
- `email` (PK)
- `token` (hash)
- `created_at`
- `used_at`
- `requested_ip`
- `used_ip`

## Evenements
- `catmin.auth.password.reset.requested`
- `catmin.auth.password.reset.completed`

## Notes d'exploitation
- Les tests unitaires/feature associes utilisent SQLite en memoire et sont skips si `pdo_sqlite` absent.
- En production, appliquer la migration des tokens admin avant activation du flux.
