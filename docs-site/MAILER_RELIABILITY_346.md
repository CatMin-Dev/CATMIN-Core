# Mailer Reliability 346

## Objectif

Le prompt 346 renforce l'exploitation du mailer CATMIN avec:

- statuts d'envoi plus explicites
- retry automatique gouverne
- preparation d'un fallback provider
- relance manuelle ciblee depuis l'admin
- remontes monitoring / alerting sur echec massif
- sandbox testable sans polluer les vrais destinataires

## Statuts d'envoi

- `pending`: cree mais pas encore parti
- `queued`: en attente de job queue
- `sending`: tentative en cours
- `retrying`: nouvelle tentative planifiee
- `sent`: envoye avec succes
- `failed`: echec terminal

## Retry

Configuration disponible dans l'admin mailer:

- `retry_max_attempts`
- `retry_backoff_seconds`
- `fallback_driver`
- `failure_alert_threshold`

Strategie:

- backoff exponentiel a partir de `retry_backoff_seconds`
- pas de retry infini
- certains messages typiquement permanents (`invalid address`, `recipient address rejected`, `550`) deviennent `failed` directement
- si un `fallback_driver` est configure, il est utilise a partir de la tentative suivante

## Relance manuelle

Depuis l'ecran mailer admin:

- filtrer par statut/template/source/test
- ouvrir le journal recent
- relancer un envoi `failed` ou `retrying`

## Sandbox

Si `sandbox_mode` est actif:

- tous les emails sont rediriges vers `sandbox_recipient`
- `original_recipient` est conserve dans l'historique
- la vue mailer indique la redirection

## Alerting

Quand les statuts `failed` ou `retrying` depassent le seuil horaire configure:

- une alerte exploitation est creee via `AlertingService`
- le monitoring mailer voit deja ces echecs sur 24h

## Diagnostic rapide

1. Ouvrir `Admin > Mailer`
2. Filtrer `status=failed` ou `retrying`
3. Lire `error_message`, `failure_class`, `driver`, `next_retry_at`
4. Relancer si le probleme etait temporaire
5. Verifier queue + monitoring si les echecs se multiplient

## Recommandations X10 couvertes

- envoi trace par historique detaille: oui
- retry automatique gouverne: oui
- statuts explicites: oui
- UI utile pour exploitation: oui
- sandbox protegee: oui
- base fallback provider: oui
- alerting seuil de fails: oui

## Limites restantes

- pas encore de multi-provider dynamique complet
- pas encore de correlation UI directe avec `failed_jobs`
- pas encore de classification provider-specifique riche par code erreur

Ces trois points restent des candidats naturels si vous poussez plus loin la version X10.