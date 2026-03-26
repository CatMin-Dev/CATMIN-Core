# 013 — Cartographie du dossier `dashboard/content/`

## Méthode
- Liste des fichiers présents dans `dashboard/content/`.
- Croisement avec la whitelist `allowedPages` de `dashboard/index.php`.
- Classification fonctionnelle + qualification d’usage (démo/base/secondaire).

## Synthèse rapide
- Pages présentes dans `content/`: 28
- Pages whitelistées dans `index.php`: 31
- Pages présentes ET adressables via `index.php`: 28
- Slugs whitelistés mais absents dans `content/`: `fixed_footer`, `fixed_sidebar`, `level2`

## Inventaire détaillé

| Fichier | Rôle fonctionnel | Catégorie | Utilisée par `index.php` | Nature |
|---|---|---|---|---|
| `dashboard.html` | Tableau de bord principal (widgets, KPIs, snippets UI) | dashboard | Oui | Base réutilisable |
| `calendar.html` | Agenda / planning | pages diverses | Oui | Démo utile |
| `chartjs.html` | Exemples graphiques Chart.js | data/charts | Oui | Bibliothèque UI |
| `echarts.html` | Exemples graphiques ECharts | data/charts | Oui | Bibliothèque UI |
| `other_charts.html` | Variantes de visualisation supplémentaires | data/charts | Oui | Démo secondaire |
| `form.html` | Formulaires standards | formulaires | Oui | Base réutilisable |
| `form_advanced.html` | Composants de formulaires avancés | formulaires | Oui | Bibliothèque UI |
| `form_buttons.html` | Styles/états de boutons | formulaires | Oui | Démo utile |
| `form_upload.html` | Upload de fichiers | formulaires | Oui | Base réutilisable |
| `form_validation.html` | Validation des formulaires | formulaires | Oui | Base réutilisable |
| `form_wizards.html` | Parcours multi-étapes (wizard) | formulaires | Oui | Démo utile |
| `tables.html` | Tableaux simples | tableaux | Oui | Base réutilisable |
| `tables_dynamic.html` | Tableaux dynamiques (tri/recherche/export) | tableaux | Oui | Base réutilisable |
| `e_commerce.html` | Composants e-commerce/dashboard commerce | e-commerce | Oui | Démo utile |
| `invoice.html` | Gabarit facture | e-commerce | Oui | Base réutilisable |
| `pricing_tables.html` | Grilles tarifaires | e-commerce | Oui | Démo secondaire |
| `profile.html` | Page profil utilisateur | profil | Oui | Base réutilisable |
| `projects.html` | Liste/progression projets | pages diverses | Oui | Démo utile |
| `project_detail.html` | Détail d’un projet | pages diverses | Oui | Démo utile |
| `contacts.html` | Carnet/fiche de contacts | pages diverses | Oui | Démo utile |
| `inbox.html` | Messagerie/inbox | pages diverses | Oui | Démo secondaire |
| `general_elements.html` | Collection d’éléments UI génériques | pages diverses | Oui | Bibliothèque UI |
| `icons.html` | Catalogue d’icônes | pages diverses | Oui | Bibliothèque UI |
| `media_gallery.html` | Galerie médias | pages diverses | Oui | Démo utile |
| `typography.html` | Exemples typographiques | pages diverses | Oui | Bibliothèque UI |
| `widgets.html` | Bibliothèque de widgets | pages diverses | Oui | Bibliothèque UI |
| `map.html` | Composants cartographiques | pages diverses | Oui | Démo utile |
| `plain_page.html` | Page squelette minimale | pages diverses | Oui | Base réutilisable |

## Écarts entre whitelist et fichiers réels
Slugs autorisés dans `index.php` mais sans fichier correspondant dans `content/`:
- `fixed_footer`
- `fixed_sidebar`
- `level2`

Impact actuel:
- ces routes restent techniquement sélectionnables,
- mais aboutissent au fallback 404 interne de `index.php` (alerte dans `<main>`).

## Lecture patrimoine (orientation CATMIN)
- Le dossier `content/` doit être considéré comme une bibliothèque de snippets/pages de référence.
- Les fichiers ne doivent pas être migrés massivement vers Blade à ce stade.
- Priorité: réutilisation ciblée des blocs utiles par fonctionnalités métier futures.
