# CATMIN V1 - Topbar Bridges / I18N / Notifications / Apps (049-052)

## Topbar système

La topbar est découpée en partials dédiés:

- search
- language
- notifications
- apps launcher
- settings shortcut
- theme switcher
- fullscreen
- profile actions

Bridge central: `core/topbar-bridge.php`

## I18N

- moteur léger FR/EN (`core/i18n-*`)
- helper global `__()`
- sélecteur de langue topbar
- persistance session/cookie
- fallback propre
- support modules via namespace `module.{slug}.{key}`

## Notifications bridge

- table centrale `core_notification_center`
- repository + dispatcher + presenter + bridge
- cloche topbar (non lues + liste récente)
- page admin `/notifications`
- mark read / mark all read

## Apps launcher

- table `core_apps`
- settings dédiée `/settings/apps`
- CRUD simple (ajout/toggle/suppression)
- rendu topbar en grille 3 colonnes
- support type (`internal|external`) et target (`_self|_blank`)
