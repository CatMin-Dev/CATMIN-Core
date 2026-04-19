# CAT Contract Demo

Documentation interne du module socle de demonstration contractuelle CATMIN.

## Objectif du module
Ce module sert de reference pour valider un module admin conforme au contrat CATMIN V1:
- chargement par manifest
- routes admin/settings
- integration menu/sidebar
- permissions
- settings
- assets
- release (checksums + signature)

Il est volontairement simple pour servir de base de duplication (ex: DEMO2) sans logique metier cachee.

## Statut de publication
- embarque dans le core `0.7.0-RC.1`
- release standalone autorisee sur le depot prive modules uniquement
- publication publique differee jusqu'aux tests de portabilite complets

## Contrat interne du menu (obligatoire)

### Sidebar principale
Entree declaree dans `manifest.json > navigation.sidebar`:
- `target`: zone de menu (`sidebar.main`)
- `group`: groupe logique (`demo`)
- `group_label_i18n`: label FR/EN du groupe
- `group_icon`: icone du groupe
- `group_order`: ordre du groupe
- `key`: identifiant unique de l'entree (`contract-demo`)
- `label`: texte visible
- `icon`: icone de l'entree
- `route`: chemin relatif admin (`contract-demo`)
- `order`: ordre de l'entree
- `permission`: permission requise (`example.read`)

### Sidebar settings
Entree declaree dans `manifest.json > navigation.settings_sidebar`:
- `target`: zone settings (`sidebar.settings`)
- `key`: identifiant unique (`contract-demo-settings`)
- `label`, `icon`, `route`, `order`, `permission`

### Regle critique pour l'etat actif (affichage parfait)
Le `activeNav` defini dans les routes doit correspondre a la `key` de l'entree sidebar.

Exemple actuel:
- `routes/admin.php` -> `activeNav = contract-demo`
- `manifest.navigation.sidebar[0].key = contract-demo`

Si ce couplage est faux, le menu peut:
- ne pas marquer la page active
- replier le groupe comme si aucune page n'etait ouverte
- donner un ressenti de bug UI alors que la route fonctionne

## Checklist complete "affichage parfait"

1. Unicite des routes
- verifier que chaque `path` route admin/settings est unique entre modules actifs
- effet: evite les collisions de route et erreurs 500

2. Unicite des noms de route
- verifier les `name` des routes
- effet: evite collisions dans la collection de routes et comportements non deterministes

3. Couplage menu actif
- `activeNav` route == `navigation.*.key`
- effet: groupe ouvert, item actif visuel, UX coherente

4. Coherence route menu
- `navigation.*.route` doit pointer vers un chemin reelement expose
- effet: clic sidebar -> bonne page

5. Permission coherente
- `navigation.*.permission` doit exister dans `permissions.php`
- effet: item visible uniquement pour les roles autorises

6. Cible menu correcte
- `sidebar.main` pour menu principal, `sidebar.settings` pour settings
- effet: affichage dans la bonne section

7. Ordres et groupes
- `group_order` / `order` sans conflit majeur
- effet: menu stable, predictible, lisible

8. Labels i18n
- fournir labels FR/EN si possible
- effet: rendu propre selon locale

9. Layout admin officiel
- routes doivent utiliser le layout admin core
- effet: style uniforme, sidebar stable, topbar intacte

10. Integrite release a chaque changement
- toute modif du module => regenerer `release/checksums.json` puis `release/signature.json`
- effet: module trusted, activation possible en politiques strictes

## Ce que le module peut declarer, injecter, ecouter, emettre

### 1) Navigation / menu
Utilite:
- exposer des entrees UI dans menu principal et settings

Effets:
- rend le module navigable sans patch core

Implications:
- cles et routes doivent rester synchronisees
- permissions doivent etre explicites

### 2) Routes
Routes declarees:
- `routes/admin.php`
- `routes/settings.php`

Utilite:
- points d'entree HTTP admin

Effets:
- affichage pages module dans le layout admin

Implications:
- toute collision de `path` avec un autre module provoque un echec runtime

### 3) Permissions
Source:
- `permissions.php`

Utilite:
- controle d'acces fin par action

Effets:
- la sidebar et les pages sont filtrees selon role/permission

Implications:
- suppression/renommage d'une permission casse l'acces tant que non aligne partout

### 4) Settings
Source:
- `settings.php`

Utilite:
- configuration runtime (ex: enabled, title)

Effets:
- personnalisation du comportement sans modifier le code

Implications:
- types et defaults doivent rester stables pour eviter regressions

### 5) Services bootstrap
Source:
- `bootstrap.services` dans manifest

Utilite:
- enregistrer des services module au chargement

Effets:
- disponibilite de logique reutilisable par le module

Implications:
- classes manquantes ou invalides peuvent casser le boot module

### 6) Assets admin/front
Source:
- `assets.admin` et `assets.front` dans manifest

Utilite:
- charger CSS/JS du module

Effets:
- personnalisation visuelle et interactions UI

Implications:
- chemins invalides = erreurs d'integrite et rendu incomplet

### 7) Events listen/dispatch
Source:
- `events.listen`
- `events.dispatch`

Utilite:
- reagir au cycle de vie admin
- exposer des evenements metier module

Effets:
- couplage faible avec autres briques

Implications:
- nommage stable requis pour eviter integrations cassees

### 8) Notifications
Source:
- `notifications.provider`

Utilite:
- declarer les notifications module

Effets:
- flux d'information vers canaux admin

Implications:
- provider absent/invalide = notifications indisponibles

### 9) Healthchecks
Source:
- `healthchecks.provider`

Utilite:
- verification rapide de la sante du module

Effets:
- diagnostic plus rapide en cas d'incident

Implications:
- sans healthcheck, triage plus lent

### 10) UI inject
Source:
- `ui.inject`

Etat actuel:
- vide (`[]`) dans ce module

Utilite potentielle:
- injection de fragments UI sur des ancres autorisees du core

Implications:
- une injection non maitrisee peut perturber cohérence UI/UX
- toujours garder un ciblage explicite et minimal

### 11) Langues / i18n
Possibilites d'ajout de langues:
- labels de groupe via `group_label_i18n` dans `navigation.sidebar`
- labels d'entree via `label_i18n` (quand present dans l'entree)
- fallback possible via `label_fr` / `label_en` puis `label`
- textes d'interface via systeme de traduction `__('cle.i18n')` dans routes/vues

Utilite:
- afficher un menu et des pages coherents selon la locale active
- eviter le hardcode de textes FR/EN dans les vues

Effets:
- meilleure lisibilite multilingue
- reduction des divergences entre menu, titre, breadcrumb et contenu

Implications:
- toute nouvelle langue doit couvrir au minimum les labels de navigation critiques
- en absence de traduction, le fallback est utilise (donc il faut un `label` robuste)
- melanger hardcode et i18n dans un meme ecran provoque une UX incoherente

Controle "affichage parfait" specifique langues:
1. verifier que chaque entree sidebar a au moins un label de fallback non vide
2. verifier les paires FR/EN sur les groupes menus modules
3. verifier que titres/breadcrumbs utilisent la meme logique i18n que la sidebar
4. verifier la locale active sur le runtime admin avant validation UX
5. verifier qu'aucune chaine critique ne reste hardcodee sans raison

### 12) Base de donnees SQL (exemple concret)
Etat actuel du module DEMO:
- migration UP: `migrations/001_create_example_tables.php`
- migration DOWN: `migrations/down/001_drop_example_tables.php`
- table de demo: `cat_contract_demo_records`

Structure de table de demonstration:
- `id` (PK)
- `title` (texte court)
- `status` (etat fonctionnel, indexe)
- `created_at`
- `updated_at`

Comment faire proprement avec une BDD dans un module CATMIN:
1. creer un fichier de migration UP dans `migrations/`
2. creer un fichier de migration DOWN dans `migrations/down/`
3. utiliser SQL relationnel explicite (colonnes, index, contraintes)
4. garder des noms de table stables et prefixes metier clairs
5. tester install (UP) et desinstall destructive (DOWN)

Effets:
- installation module peut provisionner la persistence metier
- desinstallation destructive peut retirer proprement les tables module

Implications:
- sans migration DOWN, la desinstallation destructive est refusee
- SQL doit rester compatible avec le driver DB configure
- modifier un schema impose de versionner et tester la migration associée
- ne jamais utiliser JSON persistant comme pseudo-table metier

## Capacites actuellement actives dans ce module
- bootstrap: oui
- routes.admin: oui
- routes.settings: oui
- routes.front: non
- routes.api: non
- routes.ajax: non
- navigation.sidebar.main: oui
- navigation.sidebar.settings: oui
- permissions: oui
- settings: oui
- notifications: oui
- healthchecks: oui
- assets.admin: oui
- ui.inject: non
- i18n sidebar (group_label_i18n): oui
- i18n labels detaillees de pages: partiel (a renforcer)
- bdd sql de demonstration: oui (migrations up/down)

## Limites volontaires
- pas de logique metier complexe
- pas de route publique front
- pas de route API/AJAX
- pas d'injection topbar dans l'etat actuel

## Procedure obligatoire apres creation/modification/suppression de fichiers
Toujours faire dans cet ordre:
1. regenerer `release/checksums.json`
2. regenerer `release/signature.json`
3. verifier integrite/signature/trust runtime

Sans cette procedure, le module peut etre considere tampered/non trusted.

## Fichiers de reference
- `docs/integration.md`
- `docs/capabilities-matrix.md`
- `docs/CHANGELOG.md`
