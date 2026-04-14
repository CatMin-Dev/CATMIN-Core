# CATMIN V1 - Final Release Checklist (047)

## Règle
Chaque point doit être évalué: `OK`, `NOK`, `N/A`.

## A. Structure core
- [ ] Boot system fonctionne
- [ ] Routing fonctionne
- [ ] Admin path fonctionne
- [ ] Pas d'accès direct non autorisé `/core`
- [ ] Pas d'accès direct non autorisé `/storage`
- [ ] Pas d'accès direct non autorisé `/database`
- [ ] Front minimal fonctionne
- [ ] Noindex front actif

## B. Installateur
- [ ] Installateur accessible sur instance vierge
- [ ] Préchecks fonctionnels
- [ ] Steps séquentielles respectées
- [ ] Documents légaux affichés
- [ ] Validation DB OK
- [ ] Création SuperAdmin OK
- [ ] Exécution complète OK
- [ ] Recovery codes générés
- [ ] Rapport final généré
- [ ] Lock final actif
- [ ] `/install` inaccessible après finalisation

## C. Database
- [ ] Install MySQL validée
- [ ] Install SQLite validée
- [ ] Migrations initiales OK
- [ ] Seeders initiaux OK
- [ ] Version DB enregistrée
- [ ] Tables critiques présentes

## D. Auth
- [ ] Login email OK
- [ ] Login username OK
- [ ] Logout OK
- [ ] Lockout OK
- [ ] Reauth OK
- [ ] Rotation session OK
- [ ] SuperAdmin protégé
- [ ] Hard reset documenté

## E. Security
- [ ] Headers sécurité présents
- [ ] CSRF valide
- [ ] Session hardening valide
- [ ] Routes admin protégées
- [ ] Install lock protégé
- [ ] IP rules testées si activées
- [ ] Pas de fuite technique brute en prod

## F. UI core
- [ ] Design system cohérent
- [ ] Layout admin stable
- [ ] Auth screens stables
- [ ] Dashboard stable
- [ ] CRUD staff stable
- [ ] Rôles/permissions stables
- [ ] Module manager stable
- [ ] Settings UI stable
- [ ] Logs/security UI stable
- [ ] Backup/maintenance UI stable

## G. Fonctions / CTA
- [ ] Aucun CTA principal manquant
- [ ] Actions secondaires présentes
- [ ] Boutons create/add présents
- [ ] Boutons save/cancel présents
- [ ] Empty states présents
- [ ] Breadcrumbs cohérents
- [ ] Navigation active correcte

## H. Monitoring / health / failsafe
- [ ] Health check fonctionne
- [ ] Monitoring dashboard fonctionne
- [ ] Pages erreur présentes
- [ ] Failsafe global fonctionnel
- [ ] Maintenance mode fonctionnel
- [ ] Logs incidents exploitables

## I. Modules core / loader
- [ ] Scan modules fonctionne
- [ ] Validation manifest fonctionne
- [ ] Dépendances vérifiées
- [ ] Activation module valide OK
- [ ] Collision critique détectée proprement

## J. Settings / versioning / update
- [ ] Settings engine fonctionne
- [ ] Cache settings fonctionne
- [ ] Fallback settings cohérent
- [ ] Version core cohérente
- [ ] Version DB cohérente
- [ ] Historique version à jour
- [ ] Stratégie update documentée

## K. Packaging standalone
- [ ] ZIP final généré
- [ ] ZIP sans fichiers parasites
- [ ] Décompression propre
- [ ] Arborescence correcte
- [ ] Docs minimum incluses
- [ ] Logos + `odin-color.css` présents
- [ ] `.env.example` présent
- [ ] README install présent

## L. Test terrain
- [ ] Test local (WAMP/XAMPP) OK
- [ ] Test hébergement PHP simple OK
- [ ] Install réelle OK
- [ ] Connexion admin réelle OK
- [ ] Dashboard réel OK
- [ ] CRUD réel OK
- [ ] ZIP réimportée et retestée OK

## M. Recovery / limites
- [ ] Recovery fichiers documenté
- [ ] Recovery DB documenté
- [ ] Hard reset SuperAdmin documenté
- [ ] Limites connues V1 documentées
- [ ] Position officielle incidents destructifs validée

## N. Legal / docs
- [ ] Disclaimer fourni
- [ ] Cadre légal V1 défini
- [ ] Position licence/core clarifiée
- [ ] Documentation technique minimale prête
- [ ] Documentation admin/support/recovery prête

## Verdict
- `READY` si aucun point bloquant en `NOK`.
- `NOT READY` sinon (lister les blocants).

## Sortie de validation
- Version candidate:
- Date:
- Mode de diffusion:
- Archive de référence:

