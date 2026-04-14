# 037 - Matrice de Tests Core CATMIN

## Règles de statut
- `OK`
- `NOK`
- `PARTIEL`
- `NON TESTÉ`
- `BLOQUÉ`

## Criticité
- `BLOQUANT`
- `MAJEUR`
- `MINEUR`

## Règle release
- 0 `BLOQUANT` en `NOK`
- groupes critiques majoritairement `OK`
- `MINEUR` différable uniquement si tracé

## Fiche standard
- ID
- Domaine
- Sous-domaine
- Criticité
- Précondition
- Action
- Résultat attendu
- Résultat observé
- Statut
- Remarques

## Groupes couverts
- A: Structure / Boot / Routing
- B: Installateur
- C: Auth admin
- D: Security core
- E: Database / Migrations / Version DB
- F: Module loader
- G: Settings engine
- H: UI / UX core
- I: Release ZIP

## Cas critiques minimum
- A-001, A-002, A-003, A-006, A-007
- B-001, B-002, B-005, B-009, B-010, B-012
- C-001, C-002, C-004, C-005, C-007
- D-001, D-002, D-003, D-006
- E-001, E-002, E-003, E-004
- I-001, I-002, I-003, I-004

## Politique d'anomalie
- `BLOQUANT`: stop RC tant que non corrigé
- `MAJEUR`: fix en round RC, justification si report
- `MINEUR`: accepté si documenté dans registre
