# 038 - Freeze & Release Candidate Process

## Phase 1 - Feature Freeze
- stop nouveautés
- scope release figé
- seuls correctifs stabilisation autorisés

## Phase 2 - Revue fonctionnelle
- entrée: revue 036 + matrice 037
- sortie: liste patchs avant RC1

## Phase 3 - RC1
- bump version
- build ZIP
- test installation vierge
- test parcours admin critique

## Phase 4 - Fix Round
- autorisé: bugfix sécurité/fonction/layout
- interdit: feature hors scope

## Phase 5 - RC2 (si nécessaire)
- re-test bloquants + zones modifiées
- re-validation install et package

## Phase 6 - Go/No-Go
- GO: 0 bloquant, install/auth/security/zip OK
- NO-GO: tout échec critique non résolu
