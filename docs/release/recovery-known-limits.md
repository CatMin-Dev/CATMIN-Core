# CATMIN V1 - Recovery et Limites Connues (046)

## Objectif
Ce document fixe le cadre officiel de récupération CATMIN V1.
Il définit clairement ce qui est récupérable, ce qui impose restauration/réinstallation, et le périmètre de support réaliste.

## Position Officielle V1
- CATMIN V1 est stable en usage normal.
- CATMIN V1 ne promet pas d'auto-réparation complète après destruction profonde.
- En cas d'altération lourde (fichiers core + DB), la restauration ou la réinstallation est la voie officielle.
- Sans sauvegarde DB et fichiers, aucune continuité de service n'est garantie.

## Niveaux d'incidents
1. Mineur: cache/temp/UI/paramètre non critique.
2. Moyen: module KO, permissions incorrectes, route cassée.
3. Majeur: fichiers core manquants, boot/installateur cassé.
4. Critique: DB vide/supprimée, tables critiques absentes, `.env` perdu + configuration perdue.

## Limites connues V1
1. Base vide/supprimée: CATMIN ne redémarre pas proprement sans restauration/réinstallation.
2. Fichiers core supprimés/modifiés: reupload release propre requis.
3. Pas d'auto-healing complet du core.
4. Résilience orientée cas réalistes d'exploitation, pas sabotage profond.
5. Sauvegarde obligatoire pour garantir la reprise.

## Cas pratiques de recovery
### A. Fichiers core corrompus, DB intacte
1. Sauvegarder l'état actuel si possible.
2. Conserver `.env`, lock install, données storage utiles.
3. Réuploader une release propre.
4. Vérifier permissions.
5. Vérifier login admin + dashboard + pages critiques.

### B. Cache/temp corrompus
1. Purger cache/temp.
2. Relancer.
3. Vérifier logs.

### C. Config `.env` incorrecte
1. Vérifier credentials DB.
2. Vérifier route admin.
3. Vérifier droits storage.
4. Refaire un health check.

### D. DB vide/supprimée
Option 1: restaurer sauvegarde DB valide.  
Option 2: réinstaller proprement si aucune sauvegarde.

### E. SuperAdmin perdu
- Pas de reset email standard SuperAdmin.
- Procédure officielle: hard reset documenté.

### F. Module problématique
1. Désactiver module depuis UI si possible.
2. Sinon corriger son état via DB/état module.
3. Vérifier logs.

## Procédure minimale officielle
1. Observer le symptôme.
2. Vérifier logs.
3. Vérifier health check.
4. Vérifier DB.
5. Vérifier `.env`.
6. Vérifier intégrité des fichiers.
7. Restaurer fichiers si nécessaire.
8. Restaurer DB si nécessaire.
9. Réinstaller si la restauration est impossible.

## Supportable vs non-supportable
### Supportable
- Incident mineur/moyen.
- Fichiers core restaurables avec DB saine.
- Module isolé défectueux.

### Non-supportable sans restauration/réinstallation
- Base supprimée sans backup.
- Destruction combinée DB + core.
- Corruption massive sans possibilité de rollback.

## Bonnes pratiques officielles
- Sauvegardes DB régulières.
- Archive ZIP de release stable conservée hors serveur.
- Aucun patch manuel du core en production.
- Test des modules en préproduction.
- Contrôle health check après modifications.
- Journalisation des opérations critiques.

