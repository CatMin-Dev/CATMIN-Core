# Frontend Helpers Pages + Settings (Prompt 038)

## Helpers ajoutes

- `setting($key, $default = null)`
  - lecture simple d'un setting CATMIN.
- `page_by_slug($slug, $onlyPublished = true)`
  - recuperation d'une page via son slug (module Pages actif + table presente).
- `admin_url_safe($name, $parameters = [], $fallbackPath = null)`
  - generation URL admin avec verification de route et fallback propre.
- `frontend_context($overrides = [])`
  - expose un payload frontend utile:
    - `site_name`
    - `site_url`
    - `frontend_enabled`
    - `admin_login_url`
    - `admin_home_url`
    - `enabled_modules`

## Utilisation

Ces helpers sont pensés pour un usage direct dans Blade ou dans du PHP applicatif minimal.

Exemples:

- `setting('site.name', 'CATMIN')`
- `page_by_slug('home')`
- `admin_url_safe('login')`
- `$ctx = frontend_context();`

## Notes de conception

- base volontairement minimale
- logique centralisee pour eviter la dispersion
- prete pour etendre le frontend libre CATMIN sans surcharger le noyau
