# 012 — Cartographie du dossier `dashboard/components/`

## Périmètre
Fichiers analysés:
- `dashboard/components/header.php`
- `dashboard/components/aside.php`
- `dashboard/components/topnav.php`
- `dashboard/components/footer.php`

Inclusion actuelle:
- Tous sont inclus depuis `dashboard/index.php`.

---

## 1) `header.php`

### Rôle exact
- Ouvre le document HTML (`<!DOCTYPE html>`, `<html>`, `<head>`, début `<body>`).
- Prépare les classes de body dynamiques selon la page (`$pageForBody`, `$bodyClassAttr`).
- Charge les assets CSS/JS globaux (Bootstrap, Font Awesome, Bootstrap Icons, thèmes CATMIN, importmap, `main-minimal.js`).
- Déclare des en-têtes de sécurité côté meta (CSP, referrer policy, permissions policy, etc.).

### Où il est inclus
- `dashboard/index.php` via `include_once 'components/header.php';`

### Dépendances CSS/JS supposées
- CSS: `assets/css/bootstrap-5.3.8/css/bootstrap.css`, `assets/css/themes.css`, `assets/css/catmin.css`, libs vendors.
- JS: `assets/js/vendor/browser-globals-shim.js`, importmap vendors, `assets/js/main-minimal.js`.

### HTML structurel important
- Oui: définit l’ossature HTML globale et ouvre les wrappers `.container.body > .main_container`.

### Logique PHP / variables
- Oui:
  - lit `$currentPage` (injectée par `index.php`)
  - calcule `$pageForBody`, `$bodyLayoutClasses`, `$bodyClassAttr`

### Futur Blade
- Candidat naturel à un partial Blade de layout (`admin/partials/head` + ouverture layout),
  mais à migrer avec prudence car il centralise des dépendances critiques.

---

## 2) `aside.php`

### Rôle exact
- Rend la sidebar gauche (branding, profil, navigation hiérarchique, footer d’actions sidebar).
- Porte une grande partie de la navigation fonctionnelle `index.php?page=...`.

### Où il est inclus
- `dashboard/index.php` via `include 'components/aside.php';`

### Dépendances CSS/JS supposées
- Dépend des classes de thème/navigation (`left_col`, `nav_title`, `side-menu`, `child_menu`, etc.).
- Dépend des icônes Bootstrap Icons et Font Awesome chargées dans `header.php`.

### HTML structurel important
- Oui: structure complète de la colonne gauche et menu principal.

### Logique PHP / variables
- Oui (minimale):
  - `$asideLayoutClass` dépend de `$currentPage === 'fixed_sidebar'`.

### Futur Blade
- Très bon candidat pour un partial Blade (`admin/partials/aside`) avec conservation initiale de la structure HTML.

---

## 3) `topnav.php`

### Rôle exact
- Rend la top navigation (toggle menu, messages/alerts, menu utilisateur).

### Où il est inclus
- `dashboard/index.php` via `include 'components/topnav.php';`

### Dépendances CSS/JS supposées
- Dépend du style Bootstrap/nav + classes thème dashboard.
- Dépend du JS Bootstrap pour dropdowns (`data-bs-toggle="dropdown"`).

### HTML structurel important
- Oui: barre supérieure structurante pour toutes les pages admin.

### Logique PHP / variables
- Non significative (principalement HTML statique).

### Futur Blade
- Candidat direct à un partial Blade (`admin/partials/topnav`) sans complexité PHP.

---

## 4) `footer.php`

### Rôle exact
- Ferme la structure HTML globale ouverte par `header.php`.
- Rend le footer visuel (licence/crédits).
- Contient une grande partie des scripts inline d’initialisation UI/dashboard (date pickers, charts, widgets météo, etc.).

### Où il est inclus
- `dashboard/index.php` via `include 'components/footer.php';`

### Dépendances CSS/JS supposées
- Dépend de bibliothèques exposées globalement (Chart.js, TempusDominus, etc.) via `header.php`/importmap.
- Dépend de nombreux IDs/elements présents dans les pages `content/*.html`.

### HTML structurel important
- Oui: fermeture de la structure + scripts de fin de page.

### Logique PHP / variables
- Faible côté PHP pur, mais logique JavaScript importante et centralisée.

### Futur Blade
- Peut devenir un partial Blade, mais migration à phaser:
  1. conserver le footer tel quel,
  2. extraire progressivement les scripts inline vers modules dédiés,
  3. réduire le couplage aux IDs legacy.

---

## Conclusion technique
Le dossier `components/` est le noyau d’assemblage visuel/structurel du dashboard legacy. La stratégie la plus sûre est de:
- le conserver intact dans la phase de fondation,
- créer des équivalents Blade progressifs,
- éviter toute migration brutale du JS centralisé de `footer.php`.
