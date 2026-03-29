# Health Score 348

## Objectif

Le score global de sante fournit une lecture synthetique du systeme CATMIN.

Il ne remplace pas:

- le monitoring detaille
- les logs
- les checks metier
- le jugement ops

Il sert a prioriser.

## Lecture du score

- `90-100` : Excellent
- `75-89` : Stable
- `55-74` : Warning
- `<55` : Critical

Le score est accompagne d'un statut operationnel distinct (`ok`, `warning`, `degraded`, `critical`) base sur le plus mauvais domaine remonte.

## Pondération retenue

Le score part de `100` puis applique des penalites par domaine.

Poids actuels:

- securite: `18`
- base de donnees: `16`
- modules critiques: `12`
- queue: `12`
- logs critiques: `12`
- performance: `10`
- webhooks: `10`
- storage: `8`
- mailer: `8`

Multiplicateurs par statut:

- `ok` = `0`
- `warning` = `0.35`
- `degraded` = `0.7`
- `critical` = `1`

Exemple:

- un domaine `queue` en `warning` retire `ceil(12 * 0.35) = 5` points
- un domaine `security` en `critical` retire `18` points

## Recommandations automatiques

Les recommandations sont derivees des domaines qui penalise le plus le score.

Chaque recommandation expose:

- le facteur principal
- la mesure et le seuil si disponibles
- l'action de correction si une route admin existe

## Historique et tendance

Le monitoring center s'appuie sur les snapshots existants pour montrer:

- score courant
- variation par rapport au snapshot precedent
- changement de statut significatif

## Extensibilité module

Des contributeurs supplementaires peuvent etre declares dans:

```php
config('catmin.health_score.contributors')
```

Chaque classe declaree doit exposer une methode `contribute(): array` qui retourne des checks compatibles avec le monitoring.

## Limites

- le score reste une aide a la lecture, pas une verite absolue
- certains domaines peuvent etre absents si le module n'est pas installe
- la confiance affichee depend du nombre de domaines effectivement evalues
- la partie API externe a ete explicitement ignoree et n'entre pas dans cette evolution