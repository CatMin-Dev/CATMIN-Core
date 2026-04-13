# CAT Authors Bridge

Extension editoriale 1:1 des comptes admin pour CATMIN.

## Périmètre

- Comptes auteurs editoriaux relies aux comptes admin existants
- Liaison auteur → entités (articles, pages, contenus)
- Metadonnees editoriales dediees : nom, prenom, nom d affichage, slug, bio, visibilite
- Reseaux sociaux dynamiques avec ajout et suppression
- Sélecteur auteur embarquable dans modules maîtres
- Widgets auteur (badge, card, bio, identité inline)

## Dépendances

- **Forte** : `cat-seo-meta`
- **Optionnelle** : `cat-tags`, `cat-categories`

## Tables SQL

- `mod_cat_author_profiles` — extension auteur 1:1 des comptes admin
- `mod_cat_author_links` — liaisons entité → auteur

## Section admin

Ce module apparaît dans la section **Organisation** de la navigation admin.
