# Settings System — Prompt 329

## Objectif

Transformer les settings CATMIN d'un simple store de 6 clés en **centre de configuration produit** couvrant 7 familles fonctionnelles.

---

## Convention de nommage

Les clés suivent le schéma `<groupe>.<sous-clé>` :

| Groupe       | Préfixe         | Exemple                        |
|--------------|-----------------|--------------------------------|
| Site         | `site.`         | `site.name`, `site.timezone`   |
| Admin        | `admin.`        | `admin.theme`, `admin.session_timeout` |
| Sécurité     | `security.`     | `security.login_lock_attempts` |
| Mailer       | `mailer.`       | `mailer.from_email`            |
| Shop         | `shop.`         | `shop.currency`, `shop.invoice_prefix` |
| Ops          | `ops.`          | `ops.alert_email`, `ops.log_retention_days` |
| Docs         | `docs.`         | `docs.enabled`, `docs.local_source` |

Aucune clé floue ou sans préfixe ne doit être introduite.

---

## Architecture

### Modèle `Setting`

Table `settings`, colonnes :

| Colonne            | Type       | Rôle                                  |
|--------------------|------------|---------------------------------------|
| `key`              | string     | Clé unique (`site.name`)              |
| `label`            | string     | Libellé lisible (UI)                  |
| `value`            | text       | Valeur stockée en texte               |
| `type`             | string     | `string`, `integer`, `boolean`, `email`, `url`, `text` |
| `group`            | string     | Famille (`site`, `admin`, ...)        |
| `description`      | text       | Description longue                    |
| `is_public`        | boolean    | Exposé côté frontend                  |
| `is_editable`      | boolean    | Protège les clés système              |
| `options`          | text JSON  | Valeurs autorisées (select)           |
| `validation_rules` | string     | Règles Laravel compactes              |

### `SettingService` (App\Services)

Façade statique principale :

```php
SettingService::get('mailer.from_email');
SettingService::get('ops.alert_email', 'fallback@example.com');
SettingService::put('shop.currency', 'USD', 'string', 'shop', 'Devise', false, 'Devise');
SettingService::group('shop'); // Collection de toutes les clés shop.*
SettingService::forgetCache(); // Invalide le cache
```

**Important** : toutes les valeurs sont cachées indéfiniment et invalidées automatiquement à chaque `put()` / `delete()`.

### `SettingsAdminService` (Modules\Settings\Services)

Fournit 7 méthodes `xyzPanel()` et `updateXyzPanel(array $payload)` :

```php
$service->sitePanel();        // → array avec les valeurs courantes
$service->updateSitePanel($validated); // → void, appelle SettingService::put()

$service->adminPanel();
$service->securityPanel();
$service->mailerPanel();
$service->shopPanel();
$service->opsPanel();
$service->docsPanel();
```

---

## Panneaux admin (UI Settings)

URL : `/admin/settings/manage`

Les 7 onglets correspondent aux 7 familles. Chaque onglet soumet vers sa propre route PUT dédiée :

| Onglet   | Route name                    | URL cible              |
|----------|-------------------------------|------------------------|
| Site     | `admin.settings.update.site`  | `/admin/settings/site` |
| Admin    | `admin.settings.update.admin` | `/admin/settings/admin` |
| Sécurité | `admin.settings.update.security` | `/admin/settings/security` |
| Mail     | `admin.settings.update.mailer` | `/admin/settings/mailer` |
| Shop     | `admin.settings.update.shop`  | `/admin/settings/shop` |
| Ops      | `admin.settings.update.ops`   | `/admin/settings/ops`  |
| Docs     | `admin.settings.update.docs`  | `/admin/settings/docs` |

Chaque route dispose de **validations dédiées** dans `SettingsController`.

---

## Settings critiques

| Clé                               | Utilisé par                  | Sensible |
|-----------------------------------|------------------------------|----------|
| `ops.alert_email`                 | `AlertingService`            | Non      |
| `ops.alert_webhook_url`           | `AlertingService`            | Non      |
| `ops.log_retention_days`          | `LogMaintenanceService`      | Non      |
| `ops.log_archive_retention_days`  | `LogMaintenanceService`      | Non      |
| `security.webhook_nonce_ttl`      | `WebhookSecurityService` (futur) | Non  |
| `mailer.from_email`               | Mailer                       | Non      |
| `shop.currency`                   | Génération factures          | Non      |

---

## Ajouter un nouveau setting

1. Choisir la famille (groupe) ou en créer une nouvelle
2. Nommer la clé : `<groupe>.<sous-clé>`
3. Appeler `SettingService::put()` depuis un seeder ou service
4. Ajouter le champ dans la vue du panel correspondant
5. Ajouter la validation dans `SettingsController::updateXyz()`
6. Documenter ici

---

## Services consommateurs

Tous les services doivent lire depuis `SettingService` en priorité, avec `config()` en fallback :

```php
$email = (string) (SettingService::get('ops.alert_email') ?: config('catmin.alerting.email_to', ''));
```

Cela garantit que les changements admin sont pris en compte sans redémarrage.

---

## Seed

```bash
php artisan db:seed --class=SettingSeeder
```

Idempotent — `updateOrCreate` sur la clé. Ne supprime pas les valeurs existantes.

---

## Tests

```bash
php artisan test tests/Unit/Settings/
```

Voir `tests/Unit/Settings/SettingServiceTest.php` et `SettingsAdminServiceTest.php`.
