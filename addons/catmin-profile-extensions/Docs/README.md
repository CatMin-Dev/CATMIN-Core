# CATMIN Profile Extensions

Addon optionnel pour enrichir les profils avec:
- telephone / mobile
- adresse postale complete
- societe
- identite simple
- preferences de contact

## Table
- `user_profiles_extended`

## Integration
- Route update admin: `admin.profile.extensions.update`
- Service principal: `Addons\\CatminProfileExtensions\\Services\\ProfileExtensionService`
- Resolver core pour autres modules: `App\\Services\\ProfileExtensionResolverService`

## Consumption (modules)
Utiliser le resolver core pour eviter le couplage dur:

```php
$resolver = app(\App\Services\ProfileExtensionResolverService::class);

$contactPhone = $resolver->contactPhoneForAdmin($adminUserId);
$billing = $resolver->billingAddressForAdmin($adminUserId);
$preferences = $resolver->contactPreferencesForAdmin($adminUserId);
```

Le resolver assure un fallback propre meme si l'addon est desactive.
