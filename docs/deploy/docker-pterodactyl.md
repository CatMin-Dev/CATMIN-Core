# CATMIN Docker / Pterodactyl Strategy

## Objectif
Déploiement reproductible avec image simple, variables claires et volumes runtime.

## Docker local
```bash
docker compose up -d --build
```

Application: `http://localhost:8080`

## Variables minimales
- `APP_ENV`
- `APP_DEBUG`
- `DB_DRIVER`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## Volumes recommandés
- `storage/`
- `cache/`
- `logs/`
- `sessions/`
- `tmp/`
- `database/`
- `modules/`

## Permissions
- owner runtime: `www-data`
- dossiers runtime en écriture

## Reverse proxy hints
- garder `public/` comme document root
- forward `X-Forwarded-Proto` en HTTPS
- conserver les headers sécurité CATMIN

## Adaptation Pterodactyl
- Base image: Dockerfile CATMIN.
- Startup command: `apache2-foreground`.
- Monter un volume persistant pour runtime + modules.
- Fournir les variables DB dans startup environment.
- Exposer le port HTTP interne du conteneur.

## Checklist déploiement
- Installer exécuté et lock validé.
- DB accessible depuis le conteneur.
- Permissions runtime validées.
- Cron système configuré côté panel si nécessaire.
