# Procedures de Secours

## Objectif
Avoir une sequence operationnelle en incident majeur.

## Procedure A - Panne applicative critique
1. Activer maintenance.
2. Capturer logs et etat systeme.
3. Confirmer acces DB et stockage.
4. Evaluer rollback (si release recente).
5. Corriger ou restaurer.
6. Desactiver maintenance apres validation.

## Procedure B - Incident securite suspecte
1. Restreindre acces admin (IP whitelist si necessaire).
2. Rotater credentials sensibles.
3. Auditer comptes admin et sessions.
4. Extraire logs preuves (horodates).
5. Corriger faille, deployer patch.
6. Rediger rapport incident.

## Procedure C - Recovery superadmin
- Suivre strictement `guide-recovery-superadmin.md`.
- Conserver trace complete de l'intervention.

## Procedure D - Verification post-secours
- login admin nominal
- pages critiques accessibles
- settings critiques coherents
- cron et maintenance dans l'etat attendu
- aucun warning critique dans logs
