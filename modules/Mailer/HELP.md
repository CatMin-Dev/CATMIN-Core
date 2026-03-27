# Mailer — Aide

## Vue d'ensemble

Le module **Mailer** centralise tous les envois d'e-mails depuis CATMIN. Il permet de définir des templates réutilisables avec variables dynamiques, d'envoyer des e-mails de test, de gérer les envois via une queue et de consulter l'historique complet.

## Accès rapide

- **Interface** : Admin → Intégrations → Mailer
- **Route** : `/admin/mailer/manage`

## Templates

Chaque template possède :
- **Code** : identifiant technique (`shop_order_created`, `system_test`, etc.)
- **Sujet** : objet de l'e-mail, avec variables `{{ variable }}`
- **Corps HTML / texte** : contenu du mail, avec variables `{{ variable.nested }}`
- **Variables disponibles** : liste des variables supportées
- **Payload exemple** : JSON pour pré-visualiser le rendu

### Syntaxe des variables

Les variables s'écrivent `{{ nom }}` ou `{{ objet.propriete }}` dans le sujet et le corps.

Exemple avec payload `{ "order": { "number": "ORD-001", "total": 99.90 } }` :

```
Votre commande {{ order.number }} d'un montant de {{ order.total }} € est confirmée.
```

## Templates système créés par défaut

| Code | Usage |
|------|-------|
| `system_test` | Test de configuration |
| `shop_order_created` | Confirmation de commande |
| `shop_order_status` | Mise à jour statut commande |

## Envoi de test

Depuis l'interface principale, remplir le formulaire « Email de test » :
1. Choisir le template
2. Saisir l'adresse e-mail destinataire
3. Optionnel : renseigner un payload JSON pour peupler les variables
4. Cocher « Passer par la queue » si besoin de tester la queue

## Configuration

- **Driver** : smtp, mailgun, ses ou log (log écrit dans `storage/logs/laravel.log`)
- **From email / From name** : expéditeur utilisé pour tous les envois
- **Reply-to** : adresse de réponse

## Historique des envois

Le journal liste tous les envois (statut `sent`, `failed`, `queued`), le destinataire, le template utilisé, le driver et les éventuels messages d'erreur.

## Intégration Shop

Le module Shop utilise automatiquement les templates Mailer :
- Confirmation de commande → template `shop_order_created`
- Changement de statut commande → template `shop_order_status`
