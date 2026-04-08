# Hard Reset SuperAdmin

Le reset superadmin ne se fait pas via ecran public.

Procedure:
1. activer maintenance
2. backup DB
3. regenerer hash mot de passe
4. mettre a jour le compte superadmin en base
5. invalider sessions si necessaire
6. desactiver maintenance et journaliser l'action

Voir details: `docs/admin/guide-recovery-superadmin.md`
