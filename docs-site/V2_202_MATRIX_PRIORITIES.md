# Matrice des Manques et Priorites V2

## Legende

- Critique: bloque ou expose fortement le produit
- Important: impact fort sur la qualite ou la vendabilite
- Utile: ameliore nettement l'exploitation
- Secondaire: peut attendre sans casser la trajectoire V2

## Matrice priorisee

| Domaine | Sujet | Etat actuel | Niveau | Priorite | Pourquoi |
| --- | --- | --- | --- | --- | --- |
| Securite | Couverture RBAC route par route | Tres partielle hors Users | Critique | P0 | Risque d'acces trop large aux zones sensibles |
| Roles / Permissions | CRUD Roles complet | Incomplet | Critique | P0 | Impossible de gouverner proprement le RBAC |
| Webhooks | Liaison events -> dispatch sortant | Partielle / non prouvee | Critique | P0 | Risque de fonctionnalite trompeuse |
| Securite | Durcissement auth / sessions / brute force | Base seulement | Critique | P0 | Bloc securite V2 central |
| API | API externe securisee | Absente | Important | P1 | Axe produit V2 non livre |
| Shop | Commandes / clients / factures | Absent ou tres partiel | Important | P1 | Fort enjeu business futur |
| Email | Mailer professionnalise | Partiel | Important | P1 | Templates et flux encore incomplets |
| Tests | Matrice pages/routes/actions/permissions | Absente | Important | P1 | Necessaire pour limiter les regressions |
| Logs / Audit | Audit trail enrichi / retention | Base presente | Utile | P2 | Traçabilite utile mais socle deja la |
| Observabilite | Queue/Cron plus operationnels | Partiel | Utile | P2 | Pilotage d'exploitation a renforcer |
| Docs | Help center embarque | Absent | Utile | P2 | Important pour adoption admin |
| Stabilité | Tests de non regression V2 | Faibles | Utile | P2 | Besoin d'industrialiser la qualite |
| Webhooks | UX admin / supervision webhooks | Partiel | Utile | P2 | A consolider apres le branchement reel |
| API | Normalisation erreurs / versionning externe | Absent | Utile | P2 | A faire apres ouverture de l'API externe |
| Shop | Variantes / paiement / workflow avance | Absent | Secondaire | P3 | A traiter apres le socle commerce |
| Docs | Confort lecteur markdown / recherche | Absent | Secondaire | P3 | Vient apres la doc embarquee minimale |

## Plan de lots recommande

### Lot 1 - Securite et gouvernance

- RBAC complet
- Roles CRUD
- auth/sessions/anti brute force
- permissions sur routes sensibles

### Lot 2 - Raccordement et verite fonctionnelle

- webhooks reels
- matrice pages/routes/actions/tests
- normalisation erreurs sensibles

### Lot 3 - API et exploitation

- API externe securisee
- logs/audit renforces
- observabilite queue/cron

### Lot 4 - Business modules

- shop reel
- mailer pro
- facturation

### Lot 5 - Support produit

- docs embarquees
- help center
- stabilisation finale

## Decision pratique

Ordre reel recommande:

1. P0 securite + RBAC
2. P1 raccordements critiques
3. P1 API / shop / email selon enjeu business
4. P2 observabilite / docs / tests
5. P3 confort et industrialisation avancee
