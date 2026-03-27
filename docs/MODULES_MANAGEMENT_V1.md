# Gestion des modules - V1

## Vue d'ensemble
La première version de la gestion des modules (V1) fournit une interface administrateur simple pour activer et désactiver les modules optionnels du système CATMIN.

## Fonctionnalités implémentées

### Interface d'administration
- Liste complète de tous les modules déclarés
- État d'activation/désactivation visible pour chaque module
- Colonne "Type" distinguant les modules système des modules optionnels
- Colonne "Dépendances" listant les modules requis
- Colonne "Actions" avec boutons d'activation/désactivation

### Logique de validation
**Activation d'un module :**
- Vérifie que toutes les dépendances sont activées
- Retourne une erreur explicite si une dépendance est manquante

**Désactivation d'un module :**
- Vérifie qu'aucun module activé ne dépend du module à désactiver
- Empêche la désactivation des modules système critiques (core)
- Retourne une erreur explicite si des modules dépendent du module

### Persistance
- Les changements d'état sont persistés dans les fichiers `module.json` de chaque module
- Les changements prennent effet après rechargement du site

## Limitations de la V1

### Modules système non désactivables
- Le module **core** ne peut pas être désactivé (protégé comme module système)
- À l'avenir, une liste configurable de modules système pourra être définie

### Pas de résolution récursive des dépendances
- L'activation d'un module ne désactive pas automatiquement les modules qui en dépendent
- La désactivation d'un module ne désactive pas les dépendants
- L'admin doit gérer manuellement l'ordre d'activation/désactivation

### Routes non rechargées automatiquement
- Après activation/désactivation, les routes du module deviennent disponibles/indisponibles au prochain rechargement de la page ou relance du serveur
- Un message de confirmation invite l'admin à recharger si nécessaire

### Pas de bakcup automatique
- La config de base n'est pas sauvegardée avant modification
- L'admin est responsable de la gestion de version via Git

### Pas de validation d'état au démarrage
- Si un module est marqué comme activé mais essentiel manque, le système ne le détecte pas automatiquement
- Dépend de la vigilance de l'admin lors du démarrage

## Comportement attendu

### Après activation d'un module
1. L'interface confirme le succès
2. Les routes du module seront disponibles après rechargement
3. L'état est persisté dans `module.json`

### Après désactivation d'un module
1. L'interface confirme le succès
2. Les routes du module seront supprimées après rechargement
3. Les références au module dans la navigation disparaîtront

## Points de vigilance pour le futur
- **V2 devrait** implémenter une désactivation récursive optionnelle
- **V2 devrait** ajouter un cache warmer pour précharger les routes dispos
- **V2 devrait** permettre une configuration déclarative des modules critiques
- **V2 devrait** logger les changements d'état des modules
- **Considérer** un système d'hooks pour notifier les modules d'une activation/désactivation

## Structure du code

### Routes
- `POST /admin/modules/{slug}/enable` → `DashboardController@enableModule`
- `POST /admin/modules/{slug}/disable` → `DashboardController@disableModule`

### Service
- `ModuleManager::enable($slug)` — Active un module
- `ModuleManager::disable($slug)` — Désactive un module
- `ModuleManager::all()` — Liste tous les modules
- `ModuleManager::enabled()` — Liste les modules activés

### Vue
- `resources/views/admin/pages/modules/index.blade.php` — Interface de gestion

## Essai recommandé

1. Accéder à `/admin/modules`
2. Essayer d'activer un module optionnel (ex: blog)
3. Essayer de désactiver le module core (doit échouer)
4. Essayer de désactiver un module si d'autres en dépendent (doit échouer)
5. Vérifier que les changements sont persistés après reconnexion

## Archivage

Cette documentation est généricte avec le prompt 046 : Activation/désactivation des modules depuis l'admin.
