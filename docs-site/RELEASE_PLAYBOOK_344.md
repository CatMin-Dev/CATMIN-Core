# Release Playbook 344 — V2 Stable Criteria & Operations

## Contexte

CATMIN V2.5-dev a atteint une position technique solide (62% de complétude d'après audit V2.5 du 28 mars 2026), mais n'est pas encore "V2 stable" car des critères P0/P1 bloquants restent fermes. Ce playbook objective la notion de stabilité et fournit un protocole opérationnel de release.

---

## 1) Critères objectifs V2 stable

### 1.1 Critères P0 (bloquants release)

#### 1.1.1 Sécurité / Auth
- [ ] `APP_DEBUG=false` en production (vérifiable via guardrails 343)
- [ ] Admin mot de passe non par défaut et `>= 12 chars`
- [ ] 2FA activé sur au moins 1 compte super-admin
- [ ] Session cookie secure en production
- [ ] CSRF tokens en place sur tous formulaires admin (verify via tests)
- [ ] Rate limiting login actif (5/min par IP)

**Preuve attendue:** `php artisan catmin:install:check --json | grep -A 10 security_guardrails` retourne 0 critical

#### 1.1.2 RBAC couverture
- [ ] 100% (99/99) des routes admin sensibles protégées par middleware `catmin.permission`
- [ ] Super-admin policy explicite et non éditable
- [ ] Tests d'autorisation: au moins 1 test par rôle critique (super-admin, editor, viewer)
- [ ] Pas de "routes oubliées" exportant données sensibles sans permission check

**Preuve attendue:**
```bash
php artisan test tests/Feature/Admin/Rbac/ --filter="Authorization" --no-coverage
# Tous GREEN
```

#### 1.1.3 Webhooks sécurité
- [ ] Signature HMAC obligatoire ou token UUID fort
- [ ] Anti-replay: timestamp + nonce store avec TTL 300s
- [ ] Idempotence key optionnel exposé dans test
- [ ] Logs ALL livraisons (réussi/failed/retry)

**Preuve attendue:** Tests webhooks incluent vérification anti-replay + signature

#### 1.1.4 Logs opérationnels
- [ ] Dashboard Logger accessible et lisible
- [ ] Pagination min 20/50/100 sélectionnable
- [ ] Filtres: canal, niveau, date range minima
- [ ] Alertes critiques visibles (failed jobs, erreurs système)

**Preuve attendue:** UI logger opérationnelle (manual verification)

#### 1.1.5 Monitoring critiques
- [ ] Monitoring dashboard affiche statut database/queue/logs/performance
- [ ] Incidents détectables: failed jobs `> seuil`, webhooks KO, erreurs critiques
- [ ] Alertes sécurité visibles (debug=true prod, secrets weak, etc.)

**Preuve attendue:** Page monitoring accessible, 0 "critical" incidents au checktime

#### 1.1.6 Tests automatisés stabilité
- [ ] Suite complete: `php artisan test` passe 100%
- [ ] Zéro warnings DEPRECATION ou INCOMPLETE
- [ ] Couverture min auth, API, webhooks, permission

**Preuve attendue:**
```bash
php artisan test --no-coverage 2>&1 | tail -1
# "Tests: X passed, 0 failed"
```

### 1.2 Critères P1 (sécurité/ops, fortement recommandés)

#### 1.2.1 Sessions admin actives
- [ ] Liste sessions actives accessible (IP, device, last activity)
- [ ] Invalidation session forcée possible
- [ ] Logout remote fonctionne

#### 1.2.2 Admin password reset
- [ ] "Mot de passe oublié" en place avec token sécurisé
- [ ] Token expire après 1h
- [ ] Notifications email en place

#### 1.2.3 API externe V2
- [ ] Endpoints publiques documentées
- [ ] Scopes (read/write) respectés
- [ ] Rate limit enforced: 120/min par défaut
- [ ] Erreurs normalisées (pas leak info interne)

#### 1.2.4 Logs rotation & archivage
- [ ] Purge automatique après 14j par défaut
- [ ] Archivage après 90j
- [ ] Commande: `php artisan catmin:logs:purge`

#### 1.2.5 Queue intégrité
- [ ] Failed jobs `< 10` en steady state
- [ ] Commande retry/delete visible UI
- [ ] Cron job purge regulière

### 1.3 Critères P2 (UX CMS, souhaitable pour "V2 production fit")

#### 1.3.1 Pages/Articles
- [ ] Champ contenu = WYSIWYG lisible (classe, pas textarea brut)
- [ ] Listing: recherche titre/contenu + tri date/titre
- [ ] Media picker intégré (sélection image per page)
- [ ] Aperçu (preview avant publication)

#### 1.3.2 Media manager
- [ ] Upload drag-drop ou input file multi
- [ ] Recherche texte (nom fichier)
- [ ] Filtres: type/date/dossier
- [ ] Pagination 20/50

#### 1.3.3 Settings cohérents
- [ ] Onglets logiques (site, admin, sécurité, mail, ops)
- [ ] Labels clairs + descriptions helper texte
- [ ] Validation côté serveur + affichage erreurs

### 1.4 Critères P3 (excellence, "nice to have")

- Dashboard métier visuel (conversion funnel, SLA queue, webhooks statut sanitaire, SEO coverage)
- Uniformisation UX bulk actions
- API v2 métier étendue

---

## 2) Checklist pré-release (exécutable)

### 2.1 Freeze & Préparation (T-1 jour)

- [ ] Code review: commits récents validés
- [ ] **Branche 'stable' créée** à partir de main (ex: `release/v2-stable-2026-03-29`)
- [ ] Migrations prêtes, rollback testé
- [ ] `.env.production` revu et sécurisé

### 2.2 Sécurité P0 (2-3h avant release)

```bash
# Vérification securité
php artisan catmin:install:check --json | jq '.checks.security_guardrails'
# Résultat: critical_count=0

# Vérifier APP_DEBUG
grep "APP_DEBUG=false" .env.production

# Vérifier admin password
# Manuellement: créer compte test + verifier mot de passe non débile

# Vérifier 2FA sur super-admin
mysql -u root catmin -e "SELECT username, two_factor_enabled FROM admin_users WHERE is_super_admin=1;"
# Au moins 1 should be true
```

### 2.3 RBAC & Autorisation (1-2h)

```bash
# Course rapide sur routes sensibles
php artisan test tests/Feature/Admin/Rbac/SettingsAuthorizationTest.php
php artisan test tests/Feature/Admin/Rbac/UsersAuthorizationTest.php
php artisan test tests/Feature/Admin/Rbac/ModulesAuthorizationTest.php

# Tous GREEN?
# Sinon => NO-GO
```

### 2.4 Tests stabilité (30-45m)

```bash
# Run full suite
php artisan test --no-coverage 2>&1 | tail -20

# Vérifier ligne finale: "Tests: X passed, 0 failed"
# Si Y failed ou Z incomplete => NO-GO
```

### 2.5 Smoke manual (15m)
1. Login admin (standard + 2FA if enabled)
2. Dashboard home load <2s
3. Pages/Articles/Media list + search works
4. Settings accès OK
5. Queue/Cron/Logs visible
6. API interne test: `curl -H "X-Catmin-Token: ..." /api/internal/system/version`

### 2.6 Logs/Monitoring check (10m)
1. 0 critical incidents visible en monitoring
2. Failed jobs < 10
3. Recent logs show no ERROR/CRITICAL spam

### 2.7 Database & Backup (5m)
- [ ] Backup production pris (avant déploiement)
- [ ] Migration plan clair (aucune perte donnée)
- [ ] Rollback schema ready

---

## 3) No-Go Conditions (bloquants release)

Toute condition suivante→ Retarder la release d'au moins 24h:

### 3.1 Sécurité critique
- [ ] APP_DEBUG=true en config production
- [ ] Mot de passe admin par défaut (admin/admin12345) encore actif
- [ ] RBAC: une route sensible SANS `catmin.permission` check (ex: `/admin/roles`)
- [ ] Sessions cookie non secure en production
- [ ] 2FA désactivée sur tous les super-admins
- [ ] Token API interne faible/vide

### 3.2 Tests / Stabilité
- [ ] Tout test suite: Y `failed` ou Y `incomplete` ou Y `error`
- [ ] Zéro routes admin de base accessible (404 ou 500)
- [ ] Dashboard fatal error

### 3.3 Monitoring / Ops
- [ ] Failed jobs `> 25` sans explication
- [ ] Webhooks: >5 livrées en "failed" sans retry planifié
- [ ] Erreurs critiques spam >10 en dernière heure

### 3.4 Données / Migration
- [ ] Rollback plan ≠ OK + testé
- [ ] Backup invalide ou ancien >24h

### 3.5 Documentation
- [ ] Pas de DEPLOYMENT.md ou équivalent clair
- [ ] Pas de rollback procedure docuée

---

## 4) Processus de release (jour J)

### 4.1 Pré-déploiement (T-2h)

1. Merger PR finale dans `main`
2. Créer tag: `v2-stable-YYYY-MM-DD` (ex: `v2-stable-2026-03-29`)
3. Lancer checklist 2.2 → 2.7 ci-dessus
4. **Vote d'équipe:** tous checklist OK → GO / KO → NO-GO?

### 4.2 Déploiement (T-1h)

```bash
# Sur production
git checkout v2-stable-2026-03-29
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Vérifier: `curl https://app.com/admin/login` → Page login chargeable

### 4.3 Post-déploiement immédiat (T+5m)

Exécuter "Smoke Tests Post-Release" (partie 5 ci-dessous).

### 4.4 Monitoring renforcé (T+2h à T+48h)

- Alertes SMS/email sur incidents critiques activées
- Équipe on-call disponible
- Logs scrappés pour stack traces non attendues

---

## 5) Smoke Tests Post-Release

Exécuter **dans l'ordre** immédiatement après déploiement:

```bash
# 1. Connectivity
curl -s -o /dev/null -w "%{http_code}" https://app.com/up
# Expected: 200

# 2. Admin login page
curl -s https://app.com/admin/login | grep -q "Connexion admin"
# Expected: trouvé

# 3. API health
curl -s -H "Authorization: Bearer $INTERNAL_TOKEN" \
  https://app.com/api/internal/health | jq .status
# Expected: "ok"

# 4. Internal API
curl -s -H "X-Catmin-Token: $INTERNAL_TOKEN" \
    "https://app.com/api/internal/system/version" | jq .success
# Expected: true

# 5. Database connection (via command)
php artisan tinker --execute "echo DB::connection()->getPdo() ? 'OK' : 'FAIL'"
# Expected: OK

# 6. Queue worker health
php artisan queue:list 2>&1 | tail -1
# Expected: pas d'erreur fatale

# 7. Logs accessible
php artisan tinker --execute "echo \Modules\Logger\Models\SystemLog::count()"
# Expected: >0 ou aucune erreur SQL
```

### Interprétation résultats

**Tous OK:** release réussie, monitoring normal activé.

**≥1 FAIL:** 
- Inspecter logs: `tail -f storage/logs/laravel.log`
- Si <30m, tenter fix mineur
- Si >30m sans résolution → Déclencher rollback (partie 6)

---

## 6) Rollback Procedure (incident release)

### Signaux rollback immédiat

- Database migration error (données perdues/corrompues)
- Dashboard/routes 500 systématique
- Login admin cassé
- Queue bloquée (jobs ne se défilent pas)
- Performance >5x dégradée

### Étapes rollback

1. **Notification:** Déclarer incident, notifier équipe
2. **Revert code:**
   ```bash
   git checkout VERSION_ANTERIEURE
   php artisan down --secret=xyz  # Mode maintenance
   ```
3. **Backup DB post-incident:** avant rollback migration
4. **Rollback DB:**
   ```bash
   php artisan migrate:rollback
   ```
5. **Restart app:** `php artisan up`
6. **Smoke tests:** vérifier version N-1 OK
7. **Postmortem:** analyser logs, documenter root cause
8. **Retry release:** fix + tag + retry 24h plus tard

### Prévention rollback

- Tests migrations en stage
- Backup job avant chaque deploy
- Feature flags pour changements critiques

---

## 7) Versioning Release

### Convention

**Version dashboard:**
- Format: `V2.Y-status-YYYY-MM-DD` dans config (voir prompt 000)
- `V2.5-dev` → `V2.0` quand stable P0/P1
- Puis `V2.1`, `V2.2` si patches/features mineurs

**Git tags:**
- Format: `v2-stable-YYYY-MM-DD` ou `v2.0` (immutable)
- Cherry-picks post-release: `v2.0.1`, `v2.0.2`

**Display:**
- Dashboard footer: `CATMIN V2.0` ou `V2.5-dev`
- No-frame HTTP header: `X-CATMIN-Version: v2-stable-2026-03-29`

---

## 8) Monitoring post-release (24-72h)

Métriques clés à surveiller:

- **Login success rate:** target >99%
- **Admin response time:** target <1s p95
- **Queue depth:** target <10 pending
- **Failed jobs:** target <5/jour
- **API errors 4xx/5xx:** target <1% du total
- **Webhooks success:** target >95%
- **Error logs:** absence spike

Actions si dégradation:

- <5% dégradation: accepté, monitorer
- 5-15% dégradation: investigation, possible rollback si cause trouvée
- >15% dégradation: rollback immédiat

---

## 9) Documentation Release (requise)

Créer ou enrichir:

1. **DEPLOYMENT.md:** procedure technique déploiement
2. **RELEASE_CRITERIA.md:** critères objectifs stable (ce doc)
3. **SMOKE_TESTS.md:** tests post-release détaillés
4. **ROLLBACK_PROCEDURE.md:** procedure rollback complète
5. **MONITORING_POST_RELEASE.md:** KPI et alertes

---

## 10) Decision Tree: Can We Release Now?

```
START: Voulez-vous déclarer V2 stable?

Q1: Tous tests GREEN (0 failed)?
    NO → Fixer tests et retourner Q1
    YES → Q2

Q2: RBAC 100% coverage (99 routes protégées)?
    NO → Ajouter permission checks manquants → Q2
    YES → Q3

Q3: Security guardrails P0 OK (install:check critical_count=0)?
    NO → Fixer guardrails critiques → Q3
    YES → Q4

Q4: 2FA activé ≥1 super-admin + password secure?
    NO → Configurer 2FA/password → Q4
    YES → Q5

Q5: Webhooks anti-replay en place + tests green?
    NO → Ajouter anti-replay/tests → Q5
    YES → Q6

Q6: Monitoring dashboard accessible + 0 critical incidents?
    NO → Fixer monitoring/resolve incidents → Q6
    YES → Q7

Q7: Smoke tests manuels OK (login/dashboard/search/API)?
    NO → Investiguer + repeat Q7
    YES → Q8

Q8: Équipe unanime? (Product + Dev + Ops)
    NO → Discuss blockers → repeat relevant Q
    YES → ✓ RELEASE APPROVED

    NO → ✗ NO-GO: Rescheduler dans 48h
```

---

## 11) Epilogue: V2 Stable Fait Quoi?

Une V2 "stable" CATMIN signifie:

- ✓ Installation sécurisée (all P0 guardrails)
- ✓ Admin fiable (auth, 2FA, RBAC 100%)
- ✓ Webhooks sûrs (anti-replay, logs)
- ✓ Monitoring opérationnel (incidents visibles)
- ✓ CMS minimum viable (Pages/Articles/Media CRUD + recherche)
- ✓ API v2 exploitable (docs, scopes, rate limit)
- ✓ Docs on-board claires
- ✓ Tests non-régression verts

**Pas requis pour V2 stable:**
- Toutes les UX modernes (WYSIWYG full + builder GrapesJS)
- Dashboard métier visuel spectaculaire
- API v2 métier complète (write operations)

**Roadmap post-V2 stable:**
- V2.1: CMS UX + WYSIWYG + builder
- V2.2: Dashboard métier + KPI
- V2.3+: API métier étendue + addons 3e partie

---

## Next Steps

Pour passage vers V2 stable:

1. **Fermer P0** (2j): RBAC 100%, password reset, sessions actives
2. **Fermer P1** (3j): webhooks anti-replay, logs rotation, alerting
3. **Valider avec ce playbook** (1j)
4. **Tag + release** (jour J)
5. **Monitoring 48h** puis déclarer stable

**ETA prédite:** 5-7 jours ouvrés à partir de maintenant.
