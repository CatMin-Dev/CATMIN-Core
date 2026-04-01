# V2 Stable Freeze Framework (Prompt 360)

Ce document formalise le point de bascule entre V2 stable et futur chantier V3.

## 1. Perimetre V2 stable (IN)

Blocs consideres dans V2 stable:

- securite/hardening prod (guardrails, headers, secrets)
- couverture RBAC des routes sensibles
- auth admin (login, reset, 2FA, sessions)
- webhooks securises + logs/retry
- observabilite (logs, monitoring, health, alerting)
- fiabilite queue/cron
- CMS minimum operationnel (pages/articles/media)
- API externe securisee et documentee
- modules shop/mailer operationnels
- chaine ops complete (install, update, rollback, recovery, packaging)

## 2. Obligatoire avant freeze

- `catmin:validate:v2-plus` sans blocker critique
- `catmin:qa:final-gate` en statut READY
- `catmin:release:check` sans critical
- documentation release/freeze/handover complete
- baseline V2 publiee

## 3. Hors scope V2 (OUT)

Report explicite vers V3:

- refonte majeure UI/UX admin
- redesign visuel complet dashboard
- composants visuels radicaux non critiques
- optimisations cosmétiques non prioritaires
- scope gaming/FiveM et integrations associees

Justification des reports:

- reduction du risque de regression de fin de cycle
- prevention du scope creep
- separation claire stabilisation V2 vs innovation V3

## 4. Regle d admission V2 (anti-derive)

Une feature ne peut entrer en V2 que si:

1. elle ferme un gap P0/P1/P2 connu
2. elle n introduit pas de dette majeure
3. elle est testable et documentable dans le cycle actuel

Sinon: report obligatoire en backlog V3 avec rationale.

## 5. Checklist freeze avec preuves

- securite: output `catmin:install:check --json`
- RBAC: output `catmin:audit-rbac --json`
- release: output `catmin:release:check --json`
- gate final: output `catmin:qa:final-gate --json`
- integrite globale: output `catmin:validate:v2-plus --json`
- readiness freeze: output `catmin:freeze:v2 --json`

## 6. RACI de decision freeze

- Product owner: valide le scope final V2
- Tech lead: valide criteres techniques et blockers
- Ops: valide deploy/rollback/monitoring
- Release manager: prononce GO/NO-GO final

## 7. Validation finale de freeze

Freeze autorise seulement si:

- release gate passe
- blockers critiques fermes
- docs de transition a jour
- backlog V3 separe et explicite
- baseline V2 referencee pour support/hotfix
