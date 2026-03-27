# Roadmap V2 et Limites V1 CATMIN

Ce document separe clairement:

- ce qui est considere pret en V1
- ce qui est volontairement reporte
- une trajectoire V2 realiste

## 1. Perimetre V1 assume

La V1 fournit une base exploitable:

- noyau Laravel integre CATMIN
- systeme modules + addons
- migrations extensions
- RBAC progressif
- API interne de base
- events/hooks de base
- CLI operationnelle
- checks installation/systeme
- logging + audit initial

## 2. Limites volontaires V1

Ces limites sont connues et assumees:

- pas de marketplace addons
- pas d'auto-update totalement autonome
- pas de wizard web d'installation complet
- pas de dashboard health visuel avance
- pas de pipeline backup/restore one-click
- pas de moteur de recherche admin global
- pas de scan antivirus integre uploads
- pas de retention/archivage log automatise

## 3. Points reportes a plus tard

### V2 priorite haute

- gestion avancee des roles/permissions (UI complete RBAC)
- workflow complet backup + restauration assistee
- update manager plus robuste (pre-checks, rollback guide)
- observabilite: health dashboard + alertes

### V2 priorite moyenne

- packaging addons signe + checksums
- gestion dependances addons plus stricte
- import/export settings avec diff interactif
- audit enrichi (filtres avances, export, retention)

### V2 priorite basse

- templates frontend preconfigures
- experience multi-projet plus outillee
- assistants UI supplementaires (onboarding)

## 4. Ce qui est pret vs conceptuel

Pret en V1:

- commandes CLI de base
- architecture modulaire fonctionnelle
- maintenance mode CATMIN frontend
- health checks reutilisables
- docs techniques principales

Encore conceptuel / incomplet:

- automatisation de deploiement bout-en-bout
- gestion de cycle de vie addon enterprise
- securite uploads niveau entreprise (scan, quarantine)

## 5. Plan de travail V2 propose

## Phase 1 (stabilisation)

- fiabilite update/backup/restore
- durcissement securite et audit
- outillage diagnostic en continu

## Phase 2 (experience admin)

- interfaces RBAC avancees
- tableaux de bord health/audit
- workflows assistes (maintenance, migration, release)

## Phase 3 (ecosysteme)

- distribution addons plus industrialisee
- conventions publication/versionning renforcées
- guide integrateur avance et templates de reference

## 6. Regle de communication produit

Pour eviter toute confusion:

- annoncer explicitement ce qui est V1 stable
- qualifier comme experimental ce qui ne l'est pas encore
- ne pas presenter les cibles V2 comme deja livrees

