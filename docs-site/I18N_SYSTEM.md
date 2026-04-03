# Système i18n CATMIN — Documentation

## Vue d'ensemble

CATMIN supporte un système de traduction FR/EN intégré, modulaire et progressif. Les traductions sont gérées via les fichiers PHP standards de Laravel dans `lang/`, complétées par des namespaces par module et addon.

**Locales supportées :** `fr` (défaut), `en`

---

## 1. Structure des fichiers de langue

### Core (`lang/`)

```
lang/
  fr/
    core.php        ← actions, navigation, états communs
    auth.php        ← authentification, login, 2FA
    users.php       ← gestion des utilisateurs, profil
    roles.php       ← rôles et permissions
    settings.php    ← paramètres système
    logger.php      ← journaux, monitoring, alertes
    queue.php       ← file d'attente, jobs
    cron.php        ← tâches planifiées
    webhooks.php    ← webhooks sortants
    notifications.php ← centre de notifications
  en/
    (mêmes fichiers en anglais)
```

### Usage dans les vues Blade et PHP

```php
// Dans une vue Blade
{{ __('core.save') }}
{{ __('users.title') }}
{{ __('auth.login_button') }}

// Avec paramètres
{{ __('core.ago', ['time' => '5 minutes']) }}
{{ __('users.locked_until', ['until' => $date]) }}

// Directive Blade
@lang('core.cancel')
```

---

## 2. Gestion de la locale

### Priorité de résolution

```
AdminUser::metadata['locale']  →  session 'catmin_admin_locale'  →  config('app.locale')
```

### `LocaleService`

Service central : `App\Services\LocaleService`

```php
// Résoudre la locale pour un utilisateur
$locale = LocaleService::resolve($adminUser);

// Appliquer une locale (sette App::setLocale + Carbon)
LocaleService::apply('fr');

// Persister sur un utilisateur
LocaleService::persistForUser($adminUser, 'en');

// Tester si une locale est supportée
LocaleService::isSupported('fr'); // true
LocaleService::isSupported('de'); // false

// Obtenir la map {code => label}
LocaleService::localeOptions(); // ['fr' => 'Français', 'en' => 'English']
```

### Middleware `SetAdminLocale`

Appliqué automatiquement sur toutes les routes admin authentifiées via l'alias `catmin.locale` (configuré dans `config/catmin.php`).

Il n'y a **rien à ajouter** manuellement – la locale est appliquée avant chaque requête.

---

## 3. Sélecteur de langue dans le profil

Chaque administrateur peut choisir sa langue depuis **Admin → Profil** (carte "Langue de l'interface"). Le choix est persisté dans `admin_users.metadata['locale']` et appliqué à la prochaine requête.

Route : `PUT /admin/profile/locale` → `admin.profile.locale`
Méthode : `AdminProfileController::updateLocale()`

Pour lire la locale d'un utilisateur :

```php
$user->getLocale(); // 'fr' ou 'en'
```

---

## 4. Convention pour les modules

Chaque module peut embarquer ses propres traductions dans `modules/{ModuleDir}/lang/`.

### Structure attendue

```
modules/Notifications/
  lang/
    fr/
      notifications.php
    en/
      notifications.php
```

Ou format fichier unique :

```
modules/Users/
  lang/
    fr.php
    en.php
```

### Namespace automatique

Le `ModuleLangLoader` (appelé depuis `AppServiceProvider::boot()`) enregistre automatiquement le namespace `module_{slug}` pour chaque module ayant un dossier `lang/`.

```php
// Utilisation
__('module_notifications::notifications.title')
__('module_users::users.create')
```

Le namespace est : `'module_' . str_replace('-', '_', strtolower($slug))`

---

## 5. Convention pour les addons

Même pattern via `AddonLangLoader` :

```
addons/cat-blog/
  lang/
    fr/
      blog.php
    en/
      blog.php
```

Namespace : `addon_{slug}` (tirets remplacés par underscores)

```php
__('addon_cat_blog::blog.title')
__('addon_cat_address::address.city')
```

### Déclaration dans module.json / addon.json (informatif)

```json
{
  "name": "My Addon",
  "slug": "my-addon",
  "lang": ["fr", "en"]
}
```

Le champ `lang` est documentatif — le chargement est automatique via les loaders.

---

## 6. Commandes Artisan

### `php artisan catmin:i18n:scan`

Scanner les fichiers pour toutes les clés `__('...')` utilisées.

```bash
php artisan catmin:i18n:scan
php artisan catmin:i18n:scan --path=modules/Notifications
php artisan catmin:i18n:scan --format=json
```

### `php artisan catmin:i18n:missing`

Détecter les clés présentes dans la locale de référence (FR) mais absentes dans les autres.

```bash
php artisan catmin:i18n:missing
php artisan catmin:i18n:missing --locale=en
php artisan catmin:i18n:missing --reference=fr --locale=en
```

### `php artisan catmin:i18n:sync`

Générer des stubs pour les clés manquantes (dry-run par défaut).

```bash
php artisan catmin:i18n:sync               # dry-run
php artisan catmin:i18n:sync --write       # écrit les stubs
php artisan catmin:i18n:sync --locale=en --write
```

---

## 7. Dates localisées

Carbon est configuré avec la locale active lors de l'appel de `LocaleService::apply()`. Dans les vues :

```php
// Date humaine dans la locale active
$notification->created_at->diffForHumans()   // "il y a 5 minutes" (fr) / "5 minutes ago" (en)
$notification->created_at->isoFormat('LLL')  // "3 avril 2026 14:30" (fr)
```

Pour les formats explicites, utilisez `now()->translatedFormat('d F Y')` ou `Carbon::now()->locale(app()->getLocale())->isoFormat('LL')`.

---

## 8. Zones traduites et couverture actuelle

| Zone            | FR | EN | Namespace          |
|-----------------|----|----|--------------------|
| Core UI         | ✅ | ✅ | `core`             |
| Auth / 2FA      | ✅ | ✅ | `auth`             |
| Utilisateurs    | ✅ | ✅ | `users`            |
| Rôles           | ✅ | ✅ | `roles`            |
| Paramètres      | ✅ | ✅ | `settings`         |
| Journaux        | ✅ | ✅ | `logger`           |
| Queue           | ✅ | ✅ | `queue`            |
| Cron            | ✅ | ✅ | `cron`             |
| Webhooks        | ✅ | ✅ | `webhooks`         |
| Notifications   | ✅ | ✅ | `notifications`    |

### Zones à couvrir progressivement

- Vues Blade existantes (migration progressive — les vues actuelles ont des chaînes hardcodées en FR)
- Media, Pages, Articles, Menus, Blocks
- Modules/addons tiers
- Messages de validation spécifiques

---

## 9. Stratégie fallback

1. Si une clé n'existe pas dans la locale active → Laravel retourne la locale fallback (`config('app.fallback_locale')`, défaut `en`)
2. Si absente aussi dans le fallback → Laravel retourne la clé elle-même (ex: `"core.save"`)
3. Aucun crash — le fallback est garanti propre

Pour éviter les clés brutes à l'affichage en production, veillez à toujours avoir les clés dans **au moins** `en/`.

---

## 10. Ajouter une traduction à un module — pas à pas

1. Créer `modules/MonModule/lang/fr/mon_module.php` et `lang/en/mon_module.php`
2. Retourner un tableau PHP standard :
   ```php
   <?php
   return [
       'title' => 'Mon module',
       'created' => 'Élément créé.',
   ];
   ```
3. Utiliser dans une vue : `__('module_mon_module::mon_module.title')`
4. Vérifier les clés manquantes : `php artisan catmin:i18n:missing --path=modules/MonModule/lang`

---

## 11. Classes et fichiers clés

| Fichier | Rôle |
|---------|------|
| `app/Services/LocaleService.php` | Résolution, application et persistance de locale |
| `app/Http/Middleware/SetAdminLocale.php` | Middleware appliquant la locale à chaque requête |
| `app/Services/ModuleLangLoader.php` | Enregistrement namespaces traduction des modules |
| `app/Services/AddonLangLoader.php` | Enregistrement namespaces traduction des addons |
| `app/Console/Commands/CatminI18nScanCommand.php` | `catmin:i18n:scan` |
| `app/Console/Commands/CatminI18nMissingCommand.php` | `catmin:i18n:missing` |
| `app/Console/Commands/CatminI18nSyncCommand.php` | `catmin:i18n:sync` |
| `lang/fr/` + `lang/en/` | Fichiers de traduction core |
