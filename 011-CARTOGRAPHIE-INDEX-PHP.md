# 011 — Cartographie complète de `dashboard/index.php`

## Fichier analysé
- `dashboard/index.php`

## Vue d’ensemble
`index.php` est le routeur d’entrée du dashboard legacy. Il ne contient pas de logique métier lourde; il orchestre l’assemblage de la page via:
1. sélection d’une page demandée (`$_GET['page']`),
2. validation/sanitation de cette valeur,
3. includes des composants de layout,
4. include dynamique du contenu central depuis `content/`.

## Includes/Requires détectés
- `include_once 'components/header.php';`
- `include 'components/aside.php';`
- `include 'components/topnav.php';`
- `include $contentFile;` (dynamique sur `content/*.html`)
- `include 'components/footer.php';`

Aucun `require` / `require_once` direct dans `index.php` (hors `include_once` pour header).

## Paramètres GET/POST utilisés
- `GET`: `page`
- `POST`: aucun

## Logique de routage actuelle

### 1) Whitelist des pages autorisées
`$allowedPages` contient les slugs de pages acceptées (`dashboard`, `form`, `widgets`, etc.).

### 2) Détermination de la page courante
- Source: `$_GET['page'] ?? 'dashboard'`
- Normalisation: `strtolower($currentPage)`

### 3) Protection/sanitation
- Regex stricte: `^[a-z0-9_\-]+$`
- Vérification d’appartenance à la whitelist avec `in_array(..., true)`
- Si invalide: fallback forcé vers `dashboard`

### 4) Résolution du contenu central
- Chemin: `__DIR__ . '/content/' . $currentPage . '.html'`
- Si fichier lisible: include du HTML
- Sinon: `http_response_code(404)` + message d’alerte dans `<main>`

## Dépendances vers `components/`
- `header.php`: head HTML, assets CSS/JS, ouverture structure globale
- `aside.php`: sidebar/navigation
- `topnav.php`: top navigation
- `footer.php`: fermeture structure + scripts communs

## Dépendances vers `content/`
- Dépendance centrale: `content/<page>.html`
- Le mécanisme clé de sélection du contenu est la variable `$currentPage`.

## Dépendances vers `assets/`
- Pas de référence `assets/*` directement dans `index.php`.
- Dépendances assets indirectes via `components/header.php` (styles/scripts) et potentiellement `footer.php` (scripts runtime).

## Construction finale de la page
1. Préparation/validation de `$currentPage`
2. Includes layout (`header`, `aside`, `topnav`)
3. Rendu contenu central dans `<main class="right_col">`
4. Include `footer`

## Pages réellement servies
- Celles présentes dans la whitelist ET disponibles dans `content/`.
- Si whitelist OK mais fichier absent, sortie fallback 404 dans le contenu principal.

## Mécanisme qui choisit le contenu central
- Variable clé: `$currentPage`
- Formule de résolution: `$contentFile = __DIR__ . '/content/' . $currentPage . '.html';`
- Include conditionnel sur existence/lisibilité.

## Conclusion intégration Laravel
`index.php` agit comme un routeur/composeur legacy stable et prévisible. La stratégie Laravel la plus sûre est de reproduire ce pipeline (whitelist → composants partagés → include du contenu central), puis de remplacer progressivement les maillons sans rupture visuelle.
