# 009 — État des lieux des assets et protection du design

## Objectif
Stabiliser le design actuel pendant la fondation CATMIN, sans refonte CSS/JS agressive.

## Inventaire actuel

### Dashboard (`dashboard/assets`)
- CSS: 48 fichiers
- JS: 45 fichiers
- Zones principales:
  - `dashboard/assets/css/` (styles core + vendors + thèmes)
  - `dashboard/assets/js/` (scripts core/modules/vendors)

### Frontend (`frontend/assets`)
- CSS: 1 fichier (`style.css`)
- JS: 1 fichier (`app.js`)

## Doublons potentiels repérés (basename)

### CSS
Doublons de noms détectés dans `dashboard/assets`:
- `catmin.css`
- `custom.css`
- `main.css`
- `themes.css`

Interprétation:
- les doublons proviennent des dossiers de backup (`dashboard/assets/css/backup/...`) et des versions actives.
- ces doublons sont conservés volontairement à ce stade (pas de nettoyage destructif).

### JS
- Aucun doublon de nom détecté dans `dashboard/assets` (hors périmètres externes).

## Zones à nettoyer plus tard (sans action immédiate)
- Rationaliser les backups CSS (`dashboard/assets/css/backup/*`) après sécurisation complète du pipeline.
- Clarifier les rôles de `main.css`, `themes.css`, `catmin.css`, `custom.css` avant toute fusion.
- Réduire progressivement les dépendances legacy non utilisées après cartographie fine page par page.

## Règles de protection appliquées
- Aucun refactoring visuel de masse.
- Aucune suppression de classes existantes.
- Aucune rupture des dépendances CSS/JS.
- Priorité donnée à la stabilité visuelle.

## Conclusion
L’état des assets est cartographié et stable. Le design existant est explicitement protégé pour la phase de fondation.
