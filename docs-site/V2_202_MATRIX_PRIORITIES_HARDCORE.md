# Matrice des Manques et Priorites - Hardcore

## Echelle utilisee

- Priorite: P0 / P1 / P2 / P3 / P4
- Effort: S / M / L / XL
- Type: bug / manque fonctionnel / manque structurel / manque securite / manque documentation / manque raccordement

## Matrice de pilotage

| ID | Domaine | Feature | Type | Etat actuel | Criticite | Impact business | Impact securite | Impact UX admin | Dependances | Effort | Priorite | Lot recommande |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| V2-001 | Securite | Permissions fines sur routes sensibles | manque securite | Middleware fin presque limite a Users | Critique | Moyen | Tres fort | Fort | RBAC session deja present | L | P0 | Lot 1 |
| V2-002 | Roles & permissions | CRUD Roles complet | manque fonctionnel | Listing seulement | Critique | Fort | Fort | Fort | role model existant | M | P0 | Lot 1 |
| V2-003 | Securite | Auth durcie, sessions, anti brute force | manque securite | Base simple | Critique | Moyen | Tres fort | Moyen | auth existante | L | P0 | Lot 1 |
| V2-004 | Webhooks | Dispatcher reellement relie aux events | manque raccordement | Dispatcher present, liaison non prouvee | Critique | Fort | Moyen | Moyen | event bus existant | M | P0 | Lot 2 |
| V2-005 | Tests | Matrice pages/routes/actions/permissions | manque structurel | Non formalisee | Critique | Fort | Fort | Fort | audit 201 | M | P1 | Lot 2 |
| V2-006 | API | API externe securisee | manque fonctionnel | Absente | Haute | Fort | Fort | Moyen | auth/tokens | XL | P1 | Lot 3 |
| V2-007 | API | Normalisation erreurs et reponses | manque structurel | Interne seulement | Haute | Moyen | Moyen | Moyen | API externe | M | P1 | Lot 3 |
| V2-008 | Shop | Commandes / clients / statuts | manque fonctionnel | Non livres | Haute | Tres fort | Faible | Fort | produits existants | XL | P1 | Lot 4 |
| V2-009 | Facturation | Factures HTML/PDF | manque fonctionnel | Absent | Haute | Fort | Faible | Moyen | commandes | L | P1 | Lot 4 |
| V2-010 | Email | Templates pro, envois systeme, journaux | manque fonctionnel | Base partielle | Haute | Fort | Faible | Fort | mailer module | L | P1 | Lot 4 |
| V2-011 | Logs/Audit | Audit enrichi, retention, filtres | manque structurel | Socle present | Moyenne | Moyen | Fort | Moyen | logger existant | M | P2 | Lot 3 |
| V2-012 | Observabilite | Queue/Cron exploitables | manque fonctionnel | Monitoring surtout | Moyenne | Moyen | Faible | Moyen | queue/cron modules | M | P2 | Lot 3 |
| V2-013 | Docs integrees | Help center admin | manque fonctionnel | Absent | Moyenne | Moyen | Faible | Fort | docs-site existant | L | P2 | Lot 5 |
| V2-014 | Stabilité | Tests de non-regression | manque structurel | Faibles | Moyenne | Fort | Moyen | Moyen | matrice routes | L | P2 | Lot 5 |
| V2-015 | Securite | Gestion 401/403/419 normalisee | manque securite | Partielle | Moyenne | Moyen | Fort | Moyen | auth hardening | M | P2 | Lot 2 |
| V2-016 | Webhooks | UI supervision / replay / etat | manque fonctionnel | Basique | Moyenne | Moyen | Faible | Moyen | raccordement reel | M | P2 | Lot 3 |
| V2-017 | API | Journalisation appels externes | manque structurel | Interne minimale | Moyenne | Moyen | Moyen | Faible | API externe | M | P2 | Lot 3 |
| V2-018 | Shop | Variantes / stock avance | manque fonctionnel | Non livre | Basse | Moyen | Faible | Moyen | socle shop | L | P3 | Lot 4 |
| V2-019 | Docs integrees | Recherche et viewer markdown evolues | manque fonctionnel | Absent | Basse | Faible | Faible | Moyen | help center mini | M | P3 | Lot 5 |
| V2-020 | Observabilite | Monitoring performance | manque structurel | Non observe comme bloc complet | Basse | Moyen | Faible | Faible | health/logs | L | P3 | Lot 5 |

## Synthese par priorite

### P0 critique

- V2-001 Permissions fines routes sensibles
- V2-002 CRUD Roles complet
- V2-003 Auth durcie / sessions / anti brute force
- V2-004 Webhooks reels relies au metier

### P1 haute priorite

- V2-005 Matrice pages/routes/actions/permissions
- V2-006 API externe securisee
- V2-007 Normalisation reponses API
- V2-008 Shop reel commandes/clients
- V2-009 Facturation
- V2-010 Mailer pro

### P2 importante

- V2-011 Audit enrichi
- V2-012 Queue/Cron exploitables
- V2-013 Help center admin
- V2-014 Tests de non regression
- V2-015 Gestion 401/403/419
- V2-016 Supervision webhooks
- V2-017 Logs API externes

### P3 amelioration

- V2-018 Stock / variantes avances
- V2-019 Viewer docs evolue
- V2-020 Monitoring performance

## Ordre reel de travail conseille

1. Lot 1 - gouvernance et securite de base
2. Lot 2 - raccordements et verifications critiques
3. Lot 3 - ouverture et exploitation API / observabilite
4. Lot 4 - bloc business shop / facturation / email
5. Lot 5 - docs integrees, qualite, monitoring avance

## Note de pilotage

Le point cle n'est pas d'empiler les features. Le point cle est de fermer les faux positifs d'abord: RBAC partiel, roles incomplets, webhooks peut-etre non relies, shop surevalue. Tant que ces zones ne sont pas clarifiees, toute V2 risque de s'appuyer sur un etat plus flatteur que reel.
