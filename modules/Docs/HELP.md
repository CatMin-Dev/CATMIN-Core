# Docs — Aide

## Vue d'ensemble

Le module **Docs** fournit une documentation embarquée directement dans l'interface CATMIN. Il indexe automatiquement les fichiers Markdown du projet et les rend consultables.

## Accès rapide

- **Interface** : Admin → Documentation

## Sources de documentation

Les documents proviennent de deux sources :

1. **Documentation globale** : fichiers `.md` dans le répertoire `docs-site/`
2. **Aide par module** : fichier `HELP.md` à la racine de chaque module (`modules/*/HELP.md`)

## Recherche

La barre de recherche permet de trouver des documents par mots-clés dans le titre et le contenu.

## Filtrer par module

Utiliser le sélecteur « Filtrer par module » pour n'afficher que la documentation d'un module spécifique.

## Publication Discord

La fiche d'un document peut être publiée dans Discord via webhook:

- activer **Paramètres > Docs > Publication Discord activée**
- renseigner le webhook Discord
- ouvrir une documentation puis cliquer **Publier Discord**

Le message publié contient le titre, le module et un extrait du document.

## Ajouter de la documentation

### Aide d'un module

Créer un fichier `HELP.md` dans le répertoire du module :

```
modules/MonModule/HELP.md
```

Le fichier sera automatiquement indexé et accessible depuis la documentation.

### Dossier docs d'un module

Pour plusieurs fichiers par module, créer un sous-répertoire `docs/` :

```
modules/MonModule/docs/
    guide.md
    configuration.md
    api.md
```

### Documentation globale

Ajouter des fichiers `.md` dans `docs-site/` pour la documentation générale (architecture, guides développeur, etc.).

## QA Final Gate V2

Le guide QA final est disponible dans:

- `docs-site/QA_FINAL_GATE_340.md`
- `docs-site/MONITORING_CENTER_341.md`
- `docs-site/PERFORMANCE_PROFILING_342.md`
- `docs-site/SECURITY_HARDENING_343.md`
- `docs-site/API_GOVERNANCE_345.md`

Commande de gate:

```bash
php artisan catmin:qa:final-gate --save
```

## Syntaxe Markdown supportée

Le module supporte Markdown standard (CommonMark) avec les extensions :
- Tableaux
- Code avec coloration (blocs ` ``` `)
- Liens, images
- Listes ordonnées / non-ordonnées
- Citations (blockquote)
