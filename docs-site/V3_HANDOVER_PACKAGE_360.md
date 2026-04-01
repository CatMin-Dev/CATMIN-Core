# V3 Handover Package Starter (Prompt 360)

Objectif: preparer le demarrage V3 sans lancer de refonte technique immediate.

## 1. Positionnement

- V2: stabilisation + maintenance
- V3: evolution UI/UX majeure ulterieure
- Regle: aucune contamination de scope V2 en fin de cycle

## 2. Dette UX connue (input V3)

- coherence visuelle inegale entre pages admin
- experience CMS encore utilitaire sur certains parcours
- densite d information de dashboards perfectible
- ergonomie composants legacy a moderniser

## 3. Opportunites UI/UX V3

- systeme design unifie admin
- navigation contextuelle plus claire
- dashboard role-based plus actionnable
- workflows edition/publishing plus fluides
- composants media et formulaires modernises

## 4. Composants candidats redesign

- sidebar/header admin
- cards KPI et widgets dashboard
- listing tables (filtres, bulk actions, inline edits)
- formulaires settings et contenus
- modales picker media et previews

## 5. Contraintes a respecter

V3 doit conserver:

- securite et guardrails prod
- modele RBAC + mapping route/permission
- contrat extensions modules/addons
- chaines install/update/recovery stables

## 6. Registre des reports V3

Template de report:

- Titre:
- Motif de report V2:
- Valeur attendue V3:
- Risque si ignore:
- Dependances:
- Priorite candidate V3 (P0/P1/P2):

## 7. Branching et tags recommandes

- `release/v2-stable` pour finalisation freeze
- `maintenance/v2` pour hotfix post-freeze
- `next/v3-ui-ux` pour preparation ulterieure
- tags: `v2-stable-YYYY-MM-DD`, puis `v2.0.x` pour hotfix

## 8. Handover minimal a fournir avant kickoff V3

- baseline V2 stable finalisee
- KPI incidents post-release 30 jours
- top 10 douleurs UX reelles observees
- liste priorisee des epics UI/UX V3
- risques architecture a surveiller en refonte
