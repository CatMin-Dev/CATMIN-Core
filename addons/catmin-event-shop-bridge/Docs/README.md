# CATMIN Event Shop Bridge

Pont d'integration dedie entre `cat-event` et `catmin-shop`.

## Objectif
- vendre des billets evenementiels via Shop
- emettre les billets uniquement apres paiement
- annuler automatiquement les billets quand une commande est annulee
- synchroniser stock Shop et capacite Event sans couplage direct sale

## Architecture
- addon autonome
- depend de `cat-event` et `catmin-shop`
- ecoute les evenements `shop.order.paid` et `shop.order.cancelled` via `hooks.php`
- maintient ses propres tables de mapping et d'idempotence

## Tables
- `event_shop_bridge_ticket_types`
- `event_shop_bridge_order_links`

## Flux
1. creation d'un type de billet bridge
2. creation automatique d'un produit Shop associe
3. paiement commande Shop
4. emission participant + ticket Event cote bridge
5. annulation commande -> annulation billet selon regle

## Garanties
- idempotence par `source_key = order_id:order_item_id:unit_index`
- gestion de rupture capacite avec statut `failed_capacity`
- logs d'integration sur les actions sensibles