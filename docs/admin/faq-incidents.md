# FAQ Incidents CATMIN

## 1) Erreur 419 (CSRF)
Cause probable:
- session expiree
- token invalide

Actions:
- recharger la page formulaire
- verifier cookies/session
- verifier configuration session/csrf

## 2) Erreur 429 (too many attempts)
Cause probable:
- trop de tentatives login

Actions:
- attendre la fenetre de cooldown
- verifier source des tentatives (logs)
- auditer brute force potentiel

## 3) Erreur 500 admin
Cause probable:
- erreur applicative
- dependance DB indisponible

Actions:
- lire `storage/logs/catmin.log`
- verifier DB + permissions runtime
- tester route minimale

## 4) Maintenance active sans acces admin
Cause probable:
- `maintenance.allow_admin=0`

Actions:
- corriger setting en DB
- verifier message maintenance

## 5) Installer bloque/non lock coherent
Actions:
- verifier lock installer
- verifier route install
- verifier coherence entre fichier lock et etat runtime

## 6) Backup cree mais restore indisponible
Statut actuel:
- restore simule (base de strategie)

Action:
- utiliser procedure de secours documentee pour restauration effective
