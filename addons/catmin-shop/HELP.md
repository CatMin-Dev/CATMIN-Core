# Shop — Aide

## Vue d'ensemble

Le module **Shop** permet de gérer un catalogue produit, les commandes, les clients et la facturation depuis l'interface CATMIN.

## Accès rapide

- **Interface** : Admin → Commerce → Shop
- **Paramètres facture** : Admin → Commerce → Shop → Paramètres facture

## Produits

Chaque produit possède : nom, SKU, prix, prix promotionnel, stock, statut (actif / inactif), description courte et longue, image principale, catégorie.

**Filtres disponibles** : par statut, par catégorie.

## Catégories

Gérer les catégories de produits via le sous-menu « Catégories ». Chaque catégorie a un nom, un slug et une description optionnelle.

## Commandes

Liste de toutes les commandes avec statut (`pending`, `confirmed`, `shipped`, `delivered`, `cancelled`).

**Transitions de statut** : cliquer sur « Changer statut » depuis la vue détail d'une commande.

Lors d'une transition de statut, un e-mail est envoyé au client via le module Mailer (template `shop_order_status`).

## Clients

Répertoire des clients avec historique des commandes par client.

## Factures

Une facture est générée automatiquement à la confirmation d'une commande.

### Paramètres facture

Configurer les informations de l'émetteur (votre société) depuis **Paramètres facture** :
- Nom et adresse de la société
- SIRET, N° TVA
- IBAN (pour règlement par virement)
- Logo (URL publique)
- Pied de facture (mentions légales, etc.)
- Délai de paiement par défaut

### Accéder à une facture

Depuis la vue détail d'une commande → bouton « Voir la facture ».

## Templates e-mail

Les e-mails liés au Shop sont gérés depuis le module **Mailer** :
- `shop_order_created` : confirmation de commande
- `shop_order_status` : mise à jour de statut

Pour personnaliser ces e-mails, aller dans **Intégrations → Mailer** et éditer le template correspondant.
