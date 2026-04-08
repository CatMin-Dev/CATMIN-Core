# Guide Recovery Superadmin

## Regle de base
Le mot de passe superadmin ne se recupere pas via interface publique.

## Objectif
Restaurer un acces admin de maniere tracee et securisee.

## Pre-requis
- acces shell/FTP serveur
- acces DB
- operateur autorise

## Procedure recovery recommandee
1. Activer mode maintenance (option admin autorise).
2. Sauvegarder DB avant intervention.
3. Identifier le compte superadmin (table users + role super-admin).
4. Regenerer un hash mot de passe fort cote serveur.
5. Mettre a jour l'utilisateur cible en DB.
6. Forcer deconnexion sessions actives si necessaire.
7. Reconnecter superadmin et faire rotation des secrets critiques.
8. Desactiver maintenance.
9. Journaliser l'operation (date, auteur, raison, actions).

## Hard reset rules
- Interdit sans sauvegarde prealable.
- Interdit sans tracabilite ecrite.
- Interdit de reutiliser un ancien mot de passe.
- Obligation de revoir les roles admin apres recovery.

## Post-recovery
- verifier logs securite
- verifier settings critiques
- verifier que `install/` reste lock
- lancer controle d'integrite modules actifs
