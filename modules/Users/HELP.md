# Users — Aide

## Vue d'ensemble

Le module **Users** gère les utilisateurs administrateurs et leurs rôles dans CATMIN.

## Accès rapide

- **Utilisateurs** : Admin → Administration → Utilisateurs
- **Rôles** : Admin → Administration → Rôles

## Utilisateurs

Actions disponibles :
- Créer un utilisateur (nom, email, mot de passe, rôle)
- Désactiver / réactiver un compte
- Modifier les informations et le rôle

## Rôles et permissions

CATMIN utilise un système RBAC (Role-Based Access Control). Chaque rôle regroupe un ensemble de **permissions**.

Les permissions sont organisées par module : `module.shop.list`, `module.mailer.config`, `module.users.create`, etc.

Pour attribuer un rôle à un utilisateur, aller dans l'édition de l'utilisateur → champ « Rôle ».

## Sécurité

- Les mots de passe sont hashés (Bcrypt)
- L'authentification admin utilise une session dédiée
- Chaque action sensible vérifie la permission correspondante
