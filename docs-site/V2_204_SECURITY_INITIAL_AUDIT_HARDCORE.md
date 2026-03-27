# Audit securite initial - Hardcore

## Resume executif

Le socle V1 n'est pas nu, mais il reste plusieurs risques reels avant un bloc securite V2 propre:

- RBAC partiel sur les routes sensibles
- absence visible de rate limiting applicatif
- login admin base sur credentials `.env` sans 2FA
- token API interne accepte aussi en query string
- webhook entrant logue le payload complet
- rendu HTML brut de certains flash messages

## Matrice des risques

| Vecteur | Surface exposee | Niveau de risque | Fichiers touches | Remediation V2 proposee | Urgence |
| --- | --- | --- | --- | --- | --- |
| Permissions inegales | Admin routes hors Users | Critique | `modules/*/routes.php`, `app/Http/Middleware/EnsureCatminPermission.php`, `modules/Users/routes.php` | etendre permission middleware route par route | P0 |
| Login admin simple | `/admin/login` sans rate limiting ni 2FA | Critique | `app/Http/Controllers/Admin/AuthController.php`, `resources/views/admin/pages/login.blade.php` | anti brute-force, politique session, 2FA | P0 |
| Rate limiting absent | login, endpoints internes, webhook entrant | Critique | `routes/web.php`, `routes/api.php`, `modules/Webhooks/routes.php` | ajouter throttle/RateLimiter par surface | P0 |
| Secret/token en query string | API interne token via `?token=` | Haut | `app/Http/Middleware/EnsureCatminApiToken.php` | supprimer query token, header only | P1 |
| Logs sensibles | payload webhook entrant logue en entier | Haut | `modules/Webhooks/Controllers/WebhookIncomingController.php` | filtrer / minimiser logs entrants | P1 |
| XSS/reflexion HTML | flash messages modules via `{!! session(...) !!}` | Haut | `resources/views/admin/pages/modules/index.blade.php` | echapper les messages ou whitelist stricte | P1 |
| Credentials legacy .env | admin username/password simples en config | Haut | `config/catmin.php`, `app/Http/Controllers/Admin/AuthController.php` | migrer vers auth admin plus robuste / hash en DB | P1 |
| Webhook auth faible | webhook entrant par token d'URL seulement | Moyen | `modules/Webhooks/Controllers/WebhookIncomingController.php` | HMAC/verif signature optionnelle ou obligatoire | P2 |
| 2FA absente | admin auth | Moyen | surface auth admin globale | ajouter TOTP / recovery flow | P2 |
| Erreurs sensibles | messages bruts potentiels selon exceptions renvoyees | Moyen | surfaces redirect/back error diverses | normaliser erreurs utilisateur | P2 |
| Uploads | surface media | Moyen | `modules/Media/Controllers/Admin/MediaController.php`, `modules/Media/Services/MediaAdminService.php` | poursuivre durcissement MIME/contenu/scan | P2 |
| Session hijacking | session admin custom en session Laravel | Moyen | `AuthController`, `EnsureCatminAdminAuthenticated` | renforcer config session, idle timeout, IP/UA strategy si necessaire | P2 |

## Detail par axe

### 1. Auth admin

Etat actuel:

- validation basique username/password
- comparaison avec credentials issus de `config('catmin.admin.*')`
- regeneration de session apres login
- invalidation + regenerateToken au logout

Point positif:

- regeneration de session et invalidation logout sont bien presentes

Point faible:

- pas de rate limiting
- pas de 2FA
- secret admin encore base sur variables d'environnement simples

Evidence:

- `app/Http/Controllers/Admin/AuthController.php`
- `config/catmin.php`

### 2. CSRF

Etat actuel:

- les formulaires Blade admin lus utilisent majoritairement `@csrf`
- l'application repose sur le middleware web Laravel standard

Point positif:

- pas de lacune evidente de token CSRF sur les formulaires principaux lus

Point faible:

- audit dedie necessaire pour couverture exhaustive des formulaires et actions destructives

Evidence:

- `resources/views/admin/pages/login.blade.php`
- `resources/views/admin/pages/modules/index.blade.php`
- `modules/*/Views/*.blade.php`

### 3. XSS / rendu HTML

Etat actuel:

- la plupart des vues Blade utilisent l'echappement standard
- une exception notable existe dans la page modules qui injecte `session('success')` et `session('error')` avec `{!! !!}` dans un script

Risque:

- si un message injecte contient du HTML controle, la surface XSS augmente inutilement

Evidence:

- `resources/views/admin/pages/modules/index.blade.php`

### 4. Permissions / elevation de privilege

Etat actuel:

- middleware `catmin.permission` existe
- usage tres visible dans Users uniquement

Risque:

- elevation fonctionnelle par acces a des routes non couvertes finement

Evidence:

- `bootstrap/app.php`
- `modules/Users/routes.php`
- ensemble des autres `modules/*/routes.php`

### 5. API endpoints

Etat actuel:

- API interne systeme protegee par token
- middleware accepte header `X-Catmin-Token` ou query param `token`

Risque:

- le query param peut fuiter plus facilement dans logs, historiques, proxies

Evidence:

- `app/Http/Middleware/EnsureCatminApiToken.php`
- `routes/api.php`

### 6. Secrets / config

Etat actuel:

- secrets et passwords reposent sur `.env`
- backup DB reconstruit une commande `mysqldump` avec password en argument

Risque:

- fuite potentielle en process listing sur certains environnements pour le dump DB

Evidence:

- `app/Services/BackupService.php`
- `config/catmin.php`

### 7. Webhooks

Etat actuel:

- incoming webhook valide un token d'URL
- payload complet et signature recue sont journalises
- outgoing webhook signe HMAC si secret defini

Risque:

- logging excessif de donnees entrantes
- auth entrante encore assez simple

Evidence:

- `modules/Webhooks/Controllers/WebhookIncomingController.php`
- `modules/Webhooks/Services/WebhookDispatcher.php`

### 8. Uploads

Etat actuel:

- whitelist extensions, limite taille, verification extension, noms randomises

Point positif:

- socle upload plus propre que la moyenne V1

Point faible:

- pas de scan contenu, pas de quarantaine, pas de verification profonde mime/contenu

Evidence:

- `modules/Media/Controllers/Admin/MediaController.php`
- `modules/Media/Services/MediaAdminService.php`

## Zones plutot sures

- session regeneree apres login
- session invalidee au logout
- mots de passe Users hashes via modele/Hash::make
- formulaires admin principaux avec CSRF observe
- uploads deja durcis par whitelist/limites
- comparaisons sensibles avec `hash_equals` sur login/API/webhooks

## Zones incompletes ou risquee

- middleware permission non uniforme
- absence de throttling observable
- 2FA absente
- login admin legacy `.env`
- logs webhooks trop bavards
- API token via query param
- rendu HTML brut de flash messages modules

## Priorites de remediation V2

### P0

- generaliser les permissions fines
- ajouter rate limiting login/API/webhooks
- renforcer auth admin (sessions + anti brute force)

### P1

- supprimer token API en query string
- sanitiser/reduire les logs entrants webhook
- supprimer le rendu HTML brut non necessaire dans les flashs
- sortir du modele admin password simple dans `.env`

### P2

- 2FA
- signature entrante webhook plus stricte
- politique mot de passe et rotation session
- audit detaille formulaires / validations
