# CATMIN Versioning and Git Discipline (R005)

OBLIGATIONS ABSOLUES

- Mettre a jour le versioning a chaque modification significative
- Respecter le format de version defini par CATMIN
- Committer chaque etape de travail coherente
- Push sur le depot approprie
- Ne jamais oublier commit/push
- Ne jamais travailler sans versionner
- Ne jamais pousser sur le mauvais repo

## Format

MAJOR.MINOR.PATCH-STAGE.NUMBER

Exemples:
- 0.4.0-dev.1
- 0.4.0-dev.2
- 0.4.0-rc.1
- 1.0.0

## Regles

- Versionner a chaque etape significative
- Committer chaque etape coherente
- Pousser sur le bon depot
- Pas de gros changements sans commit intermediaire

## Repos

- Core prive: loader, auth, settings core, dashboard shell, security
- Modules prive: bridges, modules maitres, services, widgets/snippets
- Repos publics: uniquement apres validation manuelle
