# Documentation System 347

## Modele retenu

CATMIN conserve une approche hybride legere:

- source primaire = fichiers Markdown
- index calcule a partir des fichiers
- metadonnees documentaires via front matter optionnel
- HTML rendu mis en cache

Exemple de front matter supporte:

```md
---
title: Mailer Reliability
version: V2.5
status: current
category: ops
tags: mailer,retry,queue
summary: Pipeline d'envoi fiable.
---
```

## Recherche

La recherche tient compte de:

- titre
- slug
- module
- version
- statut
- categorie
- tags
- contenu markdown brut

Filtres supportes dans l'interface:

- module
- version
- statut
- categorie

## Versioning documentaire

Valeurs recommandées:

- `current`
- `legacy`
- `draft`
- `deprecated`

Le champ `version` reste libre mais la convention CATMIN recommandee est `V2.5`, `V2`, `V3`.

## Navigation

Chaque document affiche:

- badges version/statut/categorie
- tags
- documents lies calcules par module/version/categorie/tags

## Publication externe

La publication Discord embarque maintenant aussi:

- version
- categorie
- resume si disponible

## Ajouter une doc

1. Creer un `.md` dans `docs-site/`, `modules/*/docs/` ou `modules/*/HELP.md`
2. Ajouter un front matter si vous voulez exploiter versioning/filtres/recherche avancee
3. Ouvrir `Admin > Documentation`
4. Rechercher et verifier les metadonnees

## Recommandations X10 couvertes

- recherche utile quasi full-text: oui
- versioning documentaire: oui
- structuration categorie/module/version: oui
- docs liees: oui
- publication externe mieux preparee: oui
- cache HTML/index pragmatique: oui

## Limites restantes

- pas d'edition inline admin
- pas de persistance DB des metadonnees
- pas d'index full-text externe dedie

Ces choix sont volontaires pour rester simples et maintenables sur CATMIN V2.5.