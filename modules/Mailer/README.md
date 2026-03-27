# Mailer Module

Module Mailer CATMIN: couche email centralisee pour templates dynamiques, previews, tests d'envoi, queue et journal complet.

## Portee actuelle

- configuration d'envoi (driver, from, reply-to, activation)
- templates email editables avec variables dynamiques
- payload exemple et preview rendu dans l'admin
- email de test manuel depuis l'admin
- journal d'envoi avec statut, driver, source, tentatives et erreurs
- dispatch centralise sync ou queue
- templates systeme et shop fournis par defaut
- integration du shop sur le mailer central

## Points techniques

- service central: `Modules\\Mailer\\Services\\MailerAdminService`
- job queue: `Modules\\Mailer\\Jobs\\SendTemplatedMailJob`
- mailable generique: `Modules\\Mailer\\Mail\\TemplatedMail`
- journal: table `mailer_history`
- templates: table `mailer_templates`

## Hors scope

- campagne newsletter complete
- segmentation avancee
- constructeur drag and drop
- export analytics pousse
