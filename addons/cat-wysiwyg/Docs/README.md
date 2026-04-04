# CAT WYSIWYG Addon

Addon Marketplace pour piloter l'editeur CATMIN:

- fonctions toolbar activables/desactivables
- snippets configurables
- activation sur n'importe quel champ texte via patterns scope.field

## Route admin

- `admin/addons/cat-wysiwyg`

## Permissions

- `addon.cat_wysiwyg.menu`
- `addon.cat_wysiwyg.config`

## Exemples de rules champs

- `pages.create.content`
- `pages.edit.content`
- `articles.*.excerpt`
- `*.*.content`

## Snippets vs Blocs

- **Snippet**: morceau court reutilisable (CTA, citation, separateur, encart info).
- **Bloc**: section structuree plus lourde (hero, 2 colonnes, image+texte, FAQ, event/product card).

Les snippets/blocs sont centralises via:

- `App\Services\Editor\SnippetRegistry`
- `App\Services\Editor\BlockRegistry`

Definitions configurees dans `config/catmin_editor.php` avec filtres:

- `scopes` (`pages.*.content`, `shop.*.description`, ...)
- `requires_modules`
- `requires_addons`
- `permissions`

## Auto-scoping editeur (Prompt 429)

Le moteur de resolution des champs est compose de:

- `App\Support\Editor\EditorFieldDefinition`
- `App\Services\Editor\EditorFieldRegistry`
- `App\Services\Editor\EditorFieldResolver`
- `App\Services\Editor\FieldEditorIntegrationService`

Le helper Blade/PHP `editor_field($scope, $field)` retourne la config finale.

### Modes

- `simple`: textarea classique
- `rich`: WYSIWYG maison sans panel structure
- `rich+assets`: WYSIWYG + media/snippets
- `structured`: WYSIWYG + snippets + blocs + panneau lateral

### Mappings de base

- `Pages.content` -> `structured`
- `Articles.content` -> `structured`
- `Articles.excerpt` -> `rich`
- `Event.description` -> `structured` (si addon `cat-event` actif)
- `Shop.description` -> `rich+assets` (si addon `catmin-shop` actif)
- `*notes*` -> `simple`

### Declaration module/addon

Un module/addon peut injecter ses declarations via config (`field_definitions`, `snippet_registry`, `block_registry`) ou via providers runtime:

- `EditorFieldRegistry::registerProvider(...)`
- `SnippetRegistry::registerProvider(...)`
- `BlockRegistry::registerProvider(...)`

Si une dependance requise est absente (module/addon desactive), la definition est ignoree et le resolver applique un fallback securise.
