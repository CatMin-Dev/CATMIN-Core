# System Check 349

## Objectif

Fournir un validateur environnement/installation exploitable directement depuis l'admin.

## Emplacement

- Page admin: `System Check`
- Route: `admin/system-check`

## Checks couverts

- PHP version (>= 8.1)
- extensions: `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `openssl`
- permissions `storage` et `bootstrap/cache`
- connexion DB
- integrite `.env`
- coherence `APP_ENV` / `APP_DEBUG`
- presence et validite `APP_KEY`
- etat queue
- activite cron

## Statuts

- `OK`
- `WARNING`
- `ERROR`

Un check `ERROR` critique bloque l'installation (`blocked=true`).

## Actions UI

- bouton `Recheck` (rejoue le diagnostic + snapshot monitoring)
- bouton `Export JSON diagnostic`

## Integration score 348

La page expose aussi le score global systeme (prompt 348):

- score
- label
- confiance
- statut monitoring

## Notes

- La partie API externe est ignoree volontairement.
- Le systeme reste base sur checks lisibles et recommandations actionnables.