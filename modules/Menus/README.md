# Menus Module

Systeme de menus dynamiques CATMIN (V1).

## Capacites V1

- gestion de menus (`menus`)
- gestion d'items (`menu_items`)
- hierarchie simple via `parent_id`
- types d'items: `url` et `page`
- affichage frontend via helper `menu_tree($location)`

## Limites V1

- pas de drag/drop
- pas de suppression d'items/menu (seulement activation/desactivation)
- pas de mega-menu avance

## Routes admin

- `admin.menus.manage`
- `admin.menus.create`
- `admin.menus.store`
- `admin.menus.edit`
- `admin.menus.update`
- `admin.menus.toggle_status`
- `admin.menus.items.store`
- `admin.menus.items.toggle_status`
