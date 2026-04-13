# CAT Authors Bridge

Bridge transversal de profils auteurs pour CATMIN.

## Périmètre

- Profils auteurs éditoriaux liés aux comptes admin existants
- Liaison auteur → entités (articles, pages, contenus)
- Registre manuel des rôles admin "auteur-capables" (aucune automation)
- Sélecteur auteur embarquable dans modules maîtres
- Widgets auteur (badge, card, bio, identité inline)

## Dépendances

- **Forte** : `cat-seo-meta`
- **Optionnelle** : `cat-tags`, `cat-categories`

## Rôles autorisés

Le module permet de **signaler manuellement** quels rôles admin sont des rôles auteurs.
Aucune création automatique de rôle. Aucune affectation automatique de permissions.
Il s'agit uniquement d'un registre de référence : l'administrateur coche les rôles
appropriés, et les modules consommateurs peuvent interroger ce registre pour
filtrer els utilisateurs éligibles.

## Tables SQL

- `mod_cat_author_profiles` — profils auteurs
- `mod_cat_author_links` — liaisons entité → auteur
- `mod_cat_author_roles` — registre rôles signalés auteurs

## Section admin

Ce module apparaît dans la section **Organisation** de la navigation admin.
