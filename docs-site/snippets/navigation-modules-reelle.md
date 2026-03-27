# Navigation Modules Reelle (Prompt 035)

## Principe

La sidebar reste visuellement identique, mais son contenu est maintenant pilote par l'etat des modules.

## Logique appliquee

- Generation centralisee dans AdminNavigationService.
- Chaque item determine son module requis via:
  - item.module (si defini)
  - item.match_module
  - item.parameters.module
  - inférence route -> module pour les routes admin Users/Settings
- Si le module requis est desactive, l'item est masque.
- Protection additionnelle: si la route admin ciblee n'existe pas, l'item est ignore pour eviter les liens casses.

## Compatibilite evolutive

- Les futurs modules peuvent brancher leurs items en fournissant module, match_module ou parameters.module.
- Pas de duplication de logique dans les vues Blade.
- Aucune refonte HTML/CSS de la sidebar.
