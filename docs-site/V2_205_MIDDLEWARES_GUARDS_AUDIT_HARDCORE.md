# Inventaire middlewares et guards - Hardcore

## Resume executif

CATMIN s'appuie sur un noyau simple:

- guard Laravel standard `web`
- quatre middlewares custom CATMIN
- beaucoup de routes modules sous `web + catmin.admin`
- tres peu de routes sous permission fine explicite

Conclusion immediate:

- le systeme est lisible et simple
- il n'est pas encore gouverne par un vrai maillage guard/permission complet

## 1. Inventaire reel

### Guards observes

Dans `config/auth.php`:

- guard `web` (session)
- provider `users`

Constat:

- aucun guard dedie `catmin`
- l'admin CATMIN repose sur une session custom et non sur un guard Laravel distinct

### Middlewares custom observes

- `EnsureCatminAdminAuthenticated`
- `EnsureCatminPermission`
- `EnsureCatminApiToken`
- `EnsureCatminFrontendAvailable`

Aliases declares dans `bootstrap/app.php`:

- `catmin.admin`
- `catmin.permission`
- `catmin.api-token`
- `catmin.frontend.available`

## 2. Zones plutot sures

### Admin web principal

Etat actuel:

- la quasi-totalite des routes modules admin passent par `web` + `catmin.admin`

Lecture positive:

- il y a un filtre central simple et coherent pour empecher l'acces anonyme aux modules admin

Evidence:

- `modules/Articles/routes.php`
- `modules/Pages/routes.php`
- `modules/Media/routes.php`
- `modules/Menus/routes.php`
- `modules/Blocks/routes.php`
- `modules/Settings/routes.php`
- `modules/Queue/routes.php`
- `modules/Cron/routes.php`
- `modules/Webhooks/routes.php`

### API interne sensible

Etat actuel:

- les endpoints systeme sensibles passent par `catmin.api-token`

Lecture positive:

- les endpoints system/status/version/health ne sont pas totalement publics

Evidence:

- `routes/api.php`
- `app/Http/Middleware/EnsureCatminApiToken.php`

### Frontend maintenance

Etat actuel:

- le frontend est filtre via `catmin.frontend.available`

Lecture positive:

- le comportement est isole du mode maintenance natif Laravel

Evidence:

- `routes/web.php`
- `app/Http/Middleware/EnsureCatminFrontendAvailable.php`

## 3. Zones incompletes

### Absence de guard admin dedie

Etat actuel:

- l'admin utilise un flag session (`catmin_admin_authenticated`) plutot qu'un vrai guard Laravel dedie

Risque:

- logique auth admin moins standard
- evolutions futures (2FA, policies, provider distinct, multi-admin) plus difficiles

Evidence:

- `config/auth.php`
- `app/Http/Middleware/EnsureCatminAdminAuthenticated.php`
- `app/Http/Controllers/Admin/AuthController.php`

### Permission middleware peu deploye

Etat actuel:

- recherche globale des routes montre une application fine du middleware permission surtout dans `modules/Users/routes.php`

Risque:

- un admin authentifie peut acceder a de nombreuses surfaces sans permission module/action explicite

Evidence:

- `modules/Users/routes.php`
- ensemble des autres `modules/*/routes.php`

### Routes publiques techniques

Etat actuel:

- `modules/Core/routes.php` est sous middleware `web` seulement
- webhook entrant est sous `web` seulement avec token URL, sans throttle observe

Risque:

- surfaces publiques techniques a encadrer plus strictement

Evidence:

- `modules/Core/routes.php`
- `modules/Webhooks/routes.php`

## 4. Risques principaux

1. guard admin trop custom et trop leger pour une V2 durcie
2. permissions fines non homogenes
3. absence de throttling visible sur login et endpoints sensibles
4. endpoints techniques publics ou semi-publics pas assez encapsules
5. risque d'incoherence future si le RBAC se complexifie sans revoir les fondations

## 5. Correctifs V2 recommandes

### Priorite haute

- definir une strategie claire: conserver le modele session custom ou migrer vers un guard admin dedie
- etendre `catmin.permission` a toutes les routes sensibles
- ajouter du throttling sur login, API interne et webhook entrant

### Priorite moyenne

- rationaliser le naming des middlewares
- documenter la politique d'usage middleware par domaine
- proteger plus explicitement les routes core techniques

## 6. Conclusion

L'architecture middleware actuelle est simple et comprenable. C'est une bonne base V1. Mais elle reste trop legere pour porter seule une V2 avec securite forte, RBAC reel et surfaces API externes. Le vrai manque n'est pas le nombre de middlewares, c'est l'homogeneite de leur application et l'absence d'un modele admin plus standardise.
