# Guide Support Technique

## Public
Support N1/N2 en charge du diagnostic incident CATMIN.

## Process support standard
1. Qualifier l'incident (impact, perimetre, urgence).
2. Reproduire sur environnement de test si possible.
3. Collecter preuves: logs, captures, timestamp, utilisateur.
4. Verifier etat systeme (DB, permissions runtime, maintenance).
5. Appliquer correction ou escalader au mainteneur core.
6. Documenter la resolution.

## Checklist diagnostic rapide
- Application accessible: front/admin
- DB reachable et credentials valides
- Dossiers runtime ecriture:
  - `storage/`
  - `cache/`
  - `logs/`
  - `sessions/`
  - `tmp/`
- Etat maintenance
- Integrite `.env` et coherence settings DB

## Sources d'information
- `storage/logs/catmin.log`
- `logs/catmin.log`
- Table `core_logs`
- Table `core_backups`
- Table `core_settings`

## Escalade mainteneur core
Escalader immediatement si:
- erreur 500 recurrente
- corruption donnees
- echec migration DB
- suspicion faille securite
- lockout admin global
