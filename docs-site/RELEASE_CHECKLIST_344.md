# Release Checklist 344 — Pré-release V2 Stable

**Jour J-1:** Freeze code + Préparation  
**Jour J:** Exécution checklist + Release  
**Jour J+1:** Monitoring 24h-48h  

---

## ✓ Sécurité P0 (OBLIGATOIRE)

```bash
# 1. Vérifier guardrails sécurité
php artisan catmin:install:check --json | jq '.checks.security_guardrails'
# Résultat attendu: critical_count=0

# 2. Vérifier APP_DEBUG
grep "APP_DEBUG=false" .env.production
# OK: contient "false"
```

- [ ] `APP_DEBUG=false` en .env.production
- [ ] Mot de passe admin: `>= 12 chars` et **pas dans liste** {admin, admin123, admin12345, password, password123, changeme}
- [ ] 2FA activé sur ≥1 super-admin: `SELECT two_factor_enabled FROM admin_users WHERE is_super_admin=1 AND is_active=1;`
- [ ] Aucun **critical** guardrail

---

## ✓ RBAC P0 (OBLIGATOIRE)

```bash
# Routes sensibles check
php artisan test tests/Feature/Admin/Rbac/ --no-coverage -q
# Résultat: OK ou 0 failures
```

- [ ] 100% routes sensibles protégées par `catmin.permission`
- [ ] Pas d'accès `admin.users.*`, `admin.roles.*`, `admin.settings.*`, `admin.modules.*` sans permis
- [ ] Tests d'autorisation **GREEN**

---

## ✓ Tests & Stabilité (OBLIGATOIRE)

```bash
# Run test suite
php artisan test --no-coverage 2>&1 | tail -5
# Résultat: "Tests: X passed, 0 failed"
```

- [ ] `php artisan test`: **0 failed, 0 incomplete**
- [ ] Zéro warnings DEPRECATION
- [ ] Zéro MYSQL/DB errors

---

## ✓ Logs & Monitoring (FORT. RECOMMANDÉ)

```bash
# Vérifier incidents critiques
php artisan tinker --execute "echo \Modules\Logger\Models\MonitoringIncident::where('status', '=', 'critical')->count();"
# Résultat: 0
```

- [ ] Dashboard Logger accessible `/admin/logger`
- [ ] 0 incident **critical** ouvert
- [ ] Failed jobs (queues) < 10
- [ ] Erreurs dernière 1h < 20

---

## ✓ Smoke Manual (15-20 min avant release)

1. [ ] Login admin `/admin/login` → réussit
2. [ ] Dashboard `/admin` → charge < 2s
3. [ ] Pages listing `/admin/content/pages` → affiche + search works
4. [ ] Settings `/admin/settings` → accès + sauvegarde OK
5. [ ] API test:
   ```bash
   curl -s "http://localhost:8000/api/v2/pages/published?page=1" | jq .success
   # Résultat: true
   ```
6. [ ] Queue listing visible
7. [ ] Logs listing accessible

---

## ✓ Database & Backup (5 min avant release)

- [ ] **Backup prod pris** (date/heure)
- [ ] Migrations ready à appliquer (zéro breaking changes)
- [ ] Rollback script préparé (script_rollback.sh dans dossier deploy)

---

## ✗ NO-GO Conditions (Bloquants absolus)

**Une seule de ces conditions → RETARDER release:**

```bash
# Pré-check automatique
php artisan catmin:release:check --json | jq '.summary.critical'
# Si > 0 → NO-GO
```

- [ ] Tout Critical guardrail ouvert (sécurité, password, RBAC)
- [ ] Tout test **failed**
- [ ] Login admin **impossible**
- [ ] Failed jobs > 25 sans explication
- [ ] Webhooks: >5 livrées "failed" sans retry planifié
- [ ] Erreurs critiques spam > 10/heure
- [ ] Rollback script **absent ou non testé**
- [ ] Backup > 24h

---

## ℹ️ Exécution Release (Jour J)

```bash
# 1. Merge & Tag
git merge origin/main
git tag v2-stable-YYYY-MM-DD
git push origin v2-stable-YYYY-MM-DD

# 2. Déploiement
git checkout v2-stable-YYYY-MM-DD
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# 3. Vérif post-déploiement (5 min après)
php artisan catmin:release:check
curl http://app.com/up
```

---

## ℹ️ Monitoring Post-Release (24-48h)

- [ ] Login success rate > 99%
- [ ] Response time p95 < 1s
- [ ] Failed jobs < 5/day
- [ ] API errors < 1% total
- [ ] Aucune spike d'erreurs critiques

**Si dégradation > 15%:** Déclencher ROLLBACK immédiat.

---

## ℹ️ Decision Final

```
Total checks: ?/? ✓
Criticals: 0 ✓
Warnings acceptable: ✓
Équipe unanime: ✓

→ RELEASE APPROVED ✓
```

**Responsible:** `___________`  
**Date:** `___________`  
**Release tag:** `___________`

---

**ℹ️ Plus d'info:** Voir `docs-site/RELEASE_PLAYBOOK_344.md`
