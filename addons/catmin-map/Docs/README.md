# CATMIN Map

Addon de gestion des localisations géographiques.

## Tables
- `geo_categories` : catégories de lieux (couleur, icône)
- `geo_locations` : lieux avec coordonnées GPS + intégrations event/shop/pages

## Routes
- `GET /admin/map` : carte interactive Leaflet/OSM
- `GET /admin/map/locations` : liste paginée + filtres
- `GET /admin/map/locations/create` : formulaire création
- `GET /admin/map/locations/{id}/edit` : formulaire édition (avec mini-carte)
- `GET /admin/map/categories` : gestion catégories
- `GET /admin/map/api/points` : GeoJSON API (module.map.api)
