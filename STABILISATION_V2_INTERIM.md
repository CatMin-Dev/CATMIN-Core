# STABILISATION V2 - RAPPORT INTERIM

**Date**: 28 mars 2026  
**Bloc**: 276 (Stabilisation finale V2)

## Résumé

Audit et stabilisation initiale de CATMIN V2 en fonction du rapport consolidé 000-V2-COMPLETE-AUDIT.md.

Phase 1 complétée : **Sécurité auth admin (P0)**.

## ✅ Complété

### 1. Auth Admin — Migration DB (P0 critique)

**Problème audit**: Auth admin en config brute (`catmin.admin.username/password`) — risque sécurité majeur.

**Solution livr ée**:
- Modèle `AdminUser` avec soft deletes (DB-backed)
- Migration `2026_03_28_000100_create_admin_users_table.php`  
  - Auto-seed depuis config pour migration depuis V1
  - Fields: username (unique), email, password (hashed Bcrypt), first/last name, super_admin flag, timestamps, soft deletes
- Métadonnées pour 2FA futur

**Service `AdminAuthService`**:
- Authentification robuste par username/email
- Hashing Bcrypt (Hash::check)
- Rate limiting (bloqué après 5 tentatives pendant 15 min)
- Tracking tentatives échouées
- Logging d'audit des succès/échecs
- Support 2FA (structure prête)

**Controller `AuthController` mis à jour**:
- Utilise `AdminAuthService` au lieu de credentials config
- Validation d'entrée sécurisée
- Rate limiting per IP (10 tentatives/900s)
- Logging d'audit complet (connexion réussie/échouée, IP, RBAC context)

**Tests** (`AdminAuthServiceTest`):
- 8 tests couvrant : succès, échecs, verrouillage, déblocage
- Validation des messages d'erreur génériques (non-révélation username/password)

**Commit**: `260b3f4` feat(auth): migrate admin from config credentials to DB-based with hashing

**Status**: ✅ **Validé et poussé**

---

## 🔴 P0/P1 Identifiés — À faire

### Audit P0 Permissions & Routes (en cours)

**Découverte**: ~121 routes admin découvertes.
- ~99 routes ont `middleware('catmin.permission:...')` appliqué
- ~22 routes n'ont pas de permission vérifiable => À auditer finement

**À faire**:
- Matrice exhaustive route/action/permission
- Vérifier les 22 routes non-protégées (ou non-découvertes)
- Tests d'authorization unit + integration 
- Documenter la RBAC_MATRIX pour tous les modules

### Durcissement Uploads (P1)

- Media uploads contrôlent taille/extension (config)
- À ajouter: MIME sniffing réel, antivirus optional, quarantine orphelins

### Logs + Masquage PII (P1)

- SystemLog en place
- À ajouter: masquage auto des secrets/PII avant storage

### Webhooks Anti-Replay (P1)

- Structure présente
- À ajouter: nonce/timestamp, rotation secrets

---

## 📊 État par Module

| Module | Auth | Permissions | Logs | Uploads | Webhooks | API | Docs |
|--------|------|-------------|------|---------|----------|-----|------|
| Core | ✅ | ~90% | ✅ | - | - | ~30% | - |
| Users | ✅ | ~90% | ✅ | - | - | ~20% | ✅ |
| Pages | ✅ | ~80% | ✅ | ⚠️ | - | ~10% | ⚠️ |
| Articles | ✅ | ~80% | ✅ | ⚠️ | - | ~10% | ⚠️ |
| Media | ✅ | ~80% | ✅ | ⚠️ | - | ~10% | ⚠️ |
| Shop | ✅ | ~90% | ✅ | - | - | ~30% | ✅ |
| Mailer | ✅ | ~90% | ✅ | - | - | ~20% | ✅ |
| Settings | ✅ | 🔴 | ✅ | - | - | ~20% | ⚠️ |
| Webhooks | ✅ | ~70% | ✅ | - | ⚠️ | ~30% | ⚠️ |

Légende: ✅ complet, ⚠️ partial, 🔴 missing

---

## 🔧 Travaux Recommandés — Prochaines Phases

### Phase 2 — P0/P1 Permissions & Tests (environ 3 jours)
1. Audit matrice routes → permissions exhaustif
2. Tests d'authorization (unit + feature)
3. Corriger zones non-protégées
4. Rate limiting sur endpoints sensibles (forgot-pwd, 2FA)

### Phase 3 — Durcissement métier (2-3 jours)
1. Upload MIME sniffing + antivirus
2. Webhooks anti-replay
3. Logs PII masquage
4. Soft-delete protection (pages/articles/media)

### Phase 4 — API externe (2-3 jours)
1. `/api/v1` layer versionnée
2. Auth API dédiée (scopes)
3. Rate limiting/quotas
4. Docs OpenAPI

### Phase 5 — Dashboard pilotage (2 jours)
1. KPI métier étendus (commandes, emails, erreurs)
2. Alertes in-app (emails échoués, uploads bloqués, jobs en panne)
3. Vue d'activité utilisateurs

---

## 📝 Checklist Stabilisation V2 finale

- [x] P0 Auth admin robustie (DB + hashing)
- [ ] P0 Permissions audit + tests
- [ ] P0 Tests non-régression (login, CRUD, actions sensibles)
- [ ] P1 Uploads durcis
- [ ] P1 Webhooks anti-replay
- [ ] P1 Shop + Mailer pipeline complet validé
- [ ] P1 Logs PII masquage
- [ ] P2 Docs embarquées (déjà faites: 266-270)
- [ ] Documentation état V2 finale

---

## 📌 Notes d'implémentation

### AdminUser Model
- Utilise softDeletes pour preservation des logs d'audit
- Champ `locked_until` pour verrous temporaires (rate limiting login)
- Champ `metadata` (JSON) prêt pour 2FA settings, oAuth tokens, etc.
- Méthodes helpers: `isLocked()`, `verifyPassword()`, `recordFailedLoginAttempt()`, `clearFailedLoginAttempts()`

### AdminAuthService
- Singleton injectable via service container
- Pattern: `attempt(username, password) -> {success, user, error}`
- Utilisable partout: controllers, tests, CLI, etc.
- Extensible pour 2FA, WebAuthn, SAML

### AuthController
- Backward compatible: accepte toujours username/email
- Rate limiting par IP (10 tentatives / 15 min)
- Messages d'erreur génériques (sécurité)
- Logs complets (actor, action, IP, RBAC context)

### Tests
- `AdminAuthServiceTest` couvre tous les chemins critiques
- Indépendant de la DB (crée/supprime fixtures)
- Peut être étendu avec tests E2E du form web

---

## 🚀 Prochaine Étape

Procédé pour lancer Phase 2:

1. Créer matrice exhaustive RBAC (module/action/permission/route)
2. Implémenter tests d'authorization (FeatureTest par module)
3. Identifier et corriger les trous de permission
4. Standardiser error handling (401 vs 403)

