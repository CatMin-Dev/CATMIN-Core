# CATMIN Slider — Documentation

## Vue d'ensemble

L'addon `catmin-slider` permet de gérer des sliders, carrousels et grilles de contenu dans CATMIN. Trois modes d'affichage distincts sont supportés, chacun avec des paramètres configurables depuis l'admin.

---

## Types de slider

### `fullwidth` — Pleine largeur (hero banner)
- Défilement Bootstrap Carousel natif
- Occupe 100% de la largeur disponible
- **Hauteur définissable** : `px`, `vh`, `rem`, `%`
- Autoplay, intervalle, flèches nav, indicateurs de position
- Légende (titre, sous-titre, CTA) sur chaque slide

### `carousel` — Carrousel continu
- Défilement CSS infini, aucun JS requis
- Idéal pour logos, marques, photos
- **Hauteur des items définissable**
- Vitesse de défilement configurable (ms)
- Espacement entre items configurable
- Pause au survol
- Fade sur les bords gauche/droit

### `grid` — Grille multi-colonnes
- **4, 5 ou 6 colonnes** (fullwidth, responsive CSS Grid)
- **Hauteur de cellule définissable**
- Effet hover zoom sur les images
- Overlay titre + CTA au survol
- Lien CTA par cellule

---

## Structure BDD

### `sliders`
| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| name | string | Nom affiché en admin |
| slug | string unique | Identifiant technique |
| description | text nullable | Description interne |
| type | enum | fullwidth \| carousel \| grid |
| is_active | boolean | Actif/Inactif |
| starts_at | timestamp nullable | Début de diffusion |
| ends_at | timestamp nullable | Fin de diffusion |
| settings | json nullable | Paramètres spécifiques au type |

### `slider_items`
| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | PK |
| slider_id | FK → sliders | Slider parent |
| title | string nullable | Titre de l'élément |
| subtitle | string nullable | Sous-titre |
| content | text nullable | Contenu HTML/texte |
| cta_label | string nullable | Texte du bouton CTA |
| cta_url | string nullable | URL du CTA |
| media_id | int nullable | Référence media CATMIN |
| media_url | string nullable | URL image directe |
| link_type | enum nullable | page \| article \| event \| product \| url |
| link_id | int nullable | ID de la ressource liée |
| position | smallint | Ordre d'affichage |
| is_active | boolean | Actif/Inactif |
| starts_at | timestamp nullable | Début d'affichage |
| ends_at | timestamp nullable | Fin d'affichage |
| payload | json nullable | Données libres |

---

## Paramètres selon le type

### fullwidth
```json
{
  "height": "500px",
  "autoplay": true,
  "interval": 5000,
  "show_controls": true,
  "show_indicators": true
}
```

### carousel
```json
{
  "height": "120px",
  "scroll_speed": 3000,
  "gap": "24px"
}
```

### grid
```json
{
  "columns": 4,
  "height": "300px"
}
```

---

## Afficher un slider (frontend)

### Méthode 1 — Via SliderRenderService

```php
use Addons\CatminSlider\Services\SliderRenderService;

$render = app(SliderRenderService::class)->forSlug('mon-slider');

if ($render !== null) {
    // $render['type']    → 'fullwidth', 'carousel', 'grid'
    // $render['view']    → nom de la vue à inclure
    // $render['items']   → array d'items structurés
    // $render['height']  → hauteur configurée
    // $render['columns'] → (grid only)
}
```

### Méthode 2 — Inclusion directe dans Blade

```blade
{{-- Fullwidth --}}
@include('catmin-slider::render.fullwidth', ['data' => $renderData])

{{-- Carrousel continu --}}
@include('catmin-slider::render.carousel', ['data' => $renderData])

{{-- Grille --}}
@include('catmin-slider::render.grid', ['data' => $renderData])
```

### Méthode 3 — Automatique via le type

```blade
@if($renderData)
    @include($renderData['view'], ['data' => $renderData])
@endif
```

---

## Lier un slide à un module

Un élément peut pointer vers une ressource existante :

```php
$sliderService->addItem($slider, [
    'title'     => 'Événement du mois',
    'media_url' => 'https://…',
    'link_type' => 'event',   // page | article | event | product | url
    'link_id'   => 42,
]);
```

La résolution de l'URL finale (ex: `route('events.show', $link_id)`) est à la charge du module frontend consommateur.

---

## Activation par période

Un slider et ses éléments supportent `starts_at` / `ends_at` :

```php
$slider->isCurrentlyActive();     // vérifie is_active + dates
$item->isCurrentlyActive();       // idem pour un élément
$slider->activeItems()->get();    // items actifs avec période valide
```

---

## Permissions

| Permission | Description |
|------------|-------------|
| `slider.index` | Voir et éditer les sliders |
| `slider.create` | Créer un slider |
| `slider.update` | Modifier les paramètres et items |
| `slider.delete` | Supprimer un slider |
| `slider.publish` | Activer / désactiver |

---

## Migrations

```bash
php artisan migrate --path=addons/catmin-slider/Migrations --force
```

---

## Tests

```bash
php artisan test tests/Unit/Slider/SliderServiceTest.php
```

Couvre : CRUD slider, CRUD items, réordonnancement, périodes, rendu des trois types, colonnes grille (clamping), items actifs/inactifs, merged settings.
