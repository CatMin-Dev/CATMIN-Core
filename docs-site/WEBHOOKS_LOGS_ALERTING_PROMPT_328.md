# Prompt 328 - Webhooks, Logs, Alerting (Ops)

## Objectif
Cette note formalise l'exploitation en production des webhooks, logs et alertes opérationnelles CATMIN.

## 1) Convention headers webhook entrants
Headers attendus:
- X-Catmin-Timestamp
- X-Catmin-Nonce
- X-Catmin-Signature
- X-Catmin-Event-Id
- X-Catmin-Webhook-Id (recommande pour lier un webhook DB)

Réponses:
- 200: event accepté
- 202: event dupliqué ignoré (idempotence)
- 401: rejet (token/signature/anti-replay)

## 2) Politique anti-replay et idempotence
Mécanismes:
- Timestamp TTL strict (5 minutes)
- Nonce unique stocké en DB (webhook_nonces) avec expiration
- Event ID unique (webhook_events)

Effet:
- un replay est rejeté
- un event déjà consommé est ignoré proprement
- chaque rejet est loggé sans fuite d'information sensible

## 3) Rotation secrets webhook
Champs webhooks:
- secret: secret actif
- pending_secret: secret de transition
- rotation_status: current/pending
- pending_rotation_at: date bascule

Workflow recommandé:
1. Générer un nouveau secret
2. Le poser en pending_secret
3. Accepter temporairement secret actif + pending
4. Basculer pending vers secret actif
5. Nettoyer l'état pending

## 4) Logs paginés et purge sélective
UI logs admin:
- pagination serveur réelle
- tailles de page: 20/50/100/250/all
- filtres: niveau/canal/event/admin/date/http/texte

Purge sélective:
- endpoint: POST admin/logs/purge
- critères: niveau, canal, période

## 5) Rotation/archivage quotidien
Planification:
- scheduler journalier 02:30
- commande manuelle: php artisan catmin:logs:rotate

Stratégie:
- archive des logs anciens (system_logs_archive)
- compression contexte (gz + base64)
- suppression des logs sources archivés
- purge des archives dépassant la rétention

Configuration:
- CATMIN_LOG_RETENTION_DAYS (defaut 14)
- CATMIN_LOG_ARCHIVE_RETENTION_DAYS (defaut 90)

## 6) Alerting opérationnel
Niveau 1 (UI admin):
- page alertes
- compteurs et liste incidents récents
- acquittement manuel

Niveau 2 (notifications techniques):
- email optionnel (CATMIN_ALERT_EMAIL_TO)
- webhook optionnel (CATMIN_ALERT_WEBHOOK_URL)

Événements couverts:
- webhook_failed
- webhook_retrying
- critical_error
- job_failed
- health_check_failed

## 7) Paramètres utiles
- CATMIN_WEBHOOK_INCOMING_TOKEN
- CATMIN_WEBHOOK_INCOMING_SECRET
- CATMIN_WEBHOOK_INCOMING_ID
- CATMIN_ALERT_EMAIL_TO
- CATMIN_ALERT_WEBHOOK_URL
- CATMIN_LOG_RETENTION_DAYS
- CATMIN_LOG_ARCHIVE_RETENTION_DAYS
