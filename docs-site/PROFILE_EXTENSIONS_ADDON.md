# Profile Extensions Addon

## Objectif
Extension profil optionnelle pour centraliser les donnees de contact et adresse sans gonfler le core.

## Addon
- slug: `catmin-profile-extensions`
- dossier: `addons/catmin-profile-extensions`

## Schema
Table: `user_profiles_extended`

Champs principaux:
- `user_id` (nullable)
- `admin_user_id` (nullable)
- `phone`, `mobile`
- `company_name`
- `address_line_1`, `address_line_2`, `postal_code`, `city`, `state`, `country_code`
- `identity_type`, `identity_number`
- `preferred_contact_method`, `contact_opt_in`

## UI admin
Le profil admin intègre une carte `Profil etendu` avec edition des champs via:
- route `admin.profile.extensions.update`

## Services
### Addon service
`Addons\\CatminProfileExtensions\\Services\\ProfileExtensionService`

- `upsertForAdminUser(int $adminUserId, array $payload)`
- `forAdminUser(int $adminUserId)`
- `toArrayForAdminUser(int $adminUserId)`

### Resolver core (fallback-safe)
`App\\Services\\ProfileExtensionResolverService`

- `forAdminUser(int $adminUserId): array`
- `contactPhoneForAdmin(int $adminUserId): ?string`
- `billingAddressForAdmin(int $adminUserId): array`
- `contactPreferencesForAdmin(int $adminUserId): array`

Le resolver garantit un fallback meme si addon desactive/indisponible.

## Consommation module (CRM / Booking / Event / Shop / Mailer)
Toujours passer par le resolver core pour eviter le couplage direct addon.

```php
$resolver = app(\App\Services\ProfileExtensionResolverService::class);

$phone = $resolver->contactPhoneForAdmin($adminUserId);
$address = $resolver->billingAddressForAdmin($adminUserId);
$prefs = $resolver->contactPreferencesForAdmin($adminUserId);
```

## Confidentialite
- donnees strictement admin (pas exposees automatiquement par API)
- validation forte au write
- edition protegee par middleware permission admin
