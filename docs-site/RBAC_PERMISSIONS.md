# RBAC CATMIN (V1 progressif)

## Objectif

Introduire un vrai RBAC sans refonte brutale:

- roles
- permissions
- associations user <-> roles
- controle progressif des menus et routes admin

## Structure retenue

- Table `roles` (permissions JSON)
- Table pivot `user_roles`
- Permissions portees par les roles

## Convention permissions

Format unique:

- `module.<slug>.<action>`

Actions de base:

- `menu`
- `list`
- `create`
- `edit`
- `delete`
- `config`

Exemples:

- `module.users.menu`
- `module.users.list`
- `module.users.create`
- `module.users.edit`
- `module.users.config`

## Integration progressive

1. Login admin:
- la session stocke roles + permissions resolues
- compte admin legacy conserve `*` (compatibilite)

2. Navigation:
- un item peut declarer `permission` dans `config/catmin.php`
- item masque si permission absente

3. Routes:
- middleware `catmin.permission:<permission>`
- applique en premier sur le module Users

## Commande utilitaire

```bash
php artisan catmin:rbac:sync
```

Cree/synchronise des roles systeme de base:

- `super-admin`
- `editor`
- `viewer`

## Coherence Users/navigation

- Users reste la source d'association user <-> roles
- Navigation s'aligne automatiquement sur les permissions
- extension aux autres modules en ajoutant `permission` + middleware route
