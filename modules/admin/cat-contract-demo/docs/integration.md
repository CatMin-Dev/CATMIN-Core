# Integration

Le module est charge par le loader core via manifest.json V1.

## Contrat utilise
- `navigation.sidebar[*]` pour la sidebar principale
- `navigation.settings_sidebar[*]` pour la sidebar settings
- `routes.admin` pour la page admin
- `routes.settings` pour la page settings
- `permissions.file` pour l'enregistrement/purge des permissions au cycle activation/desactivation

## Layout officiel
Les vues module ne doivent pas contourner le layout admin core. Les handlers routes utilisent le layout admin CATMIN existant.

## Ce module ne fait pas
- aucune injection topbar
- aucune route publique
- aucune API
- aucun AJAX
