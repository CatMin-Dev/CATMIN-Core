# Events & Hooks CATMIN (V1)

## Principe

CATMIN utilise une base d'events/hooks simple, construite sur le mécanisme Laravel `Event`.

- Bus central: `App\\Services\\CatminEventBus`
- Chargement hooks: `App\\Services\\CatminHookLoader`
- Fichiers d'extension optionnels:
  - `modules/<Module>/hooks.php`
  - `addons/<addon>/hooks.php`

## Events de base V1

- `catmin.module.enabled`
- `catmin.module.disabled`
- `catmin.content.created`
- `catmin.content.updated`
- `catmin.user.created`
- `catmin.setting.updated`

## Comment ecouter un event

Dans `hooks.php` d'un module/addon:

```php
use App\\Services\\CatminEventBus;

CatminEventBus::listen(CatminEventBus::CONTENT_CREATED, function (array $payload): void {
    // logique custom
});
```

## Exemples d'emission deja branches

- Activation/desactivation module: `ModuleManager`
- Creation utilisateur: `UsersAdminService`
- Modification setting: `SettingService::put`
- Creation/mise a jour contenu: `PagesAdminService`, `ArticleAdminService`

## Pourquoi c'est utile

- Extensibilite reelle pour modules/addons
- Peu de complexite (pas de doublon avec Laravel)
- Evolution progressive vers listeners classes/queued jobs si necessaire
