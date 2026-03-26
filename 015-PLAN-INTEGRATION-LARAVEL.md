# 015 — Plan d'Intégration Progressive Laravel autour du Dashboard

**Date:** 26 mars 2026  
**Prompt:** 015 — Plan d'intégration Laravel progressif  
**Statut:** Plan validé avant implémentation

---

## 1. Stratégie de Coexistence (Résumé)

### État actuel (Prompt 014-011)
- **Entrée:** `public/dashboard/index.php` (70 lignes, routage par GET `?page=`)
- **Composants:** `header.php`, `aside.php`, `topnav.php`, `footer.php` (importmap + CSS/JS)
- **Contenu:** 28 pages HTML statiques dans `content/<Page>.html`
- **Assets:** 48 CSS + 45 JS dans `dashboard/assets/` (backups inclus)
- **Etat:** Entièrement fonctionnel, zéro régression autorisée

### Objectif coexistence
```
┌──────────────┐
│ PUBLIC USER  │
│ (HTTP)       │
└──────┬───────┘
       │
       ├─────────────────────────────────────┐
       │                                     │
   LEGACY                            NEW LARAVEL
  (dashboard/)                        (routes/)
       │                                     │
       ├→ /dashboard/index.php      ├→ /admin/login
       ├→ /dashboard/page_*.html    ├→ /admin/preview/*
       └→ /dashboard/assets/*       ├→ /admin/access
                                    └→ [futures routes]
```

**Temporaire (3 semaines estimées):** Deux entrées parallèles, zéro migrations de fichiers.  
**Futur (semaine 4+):** Tous les fichiers legacy reste sur disque, mais accessibles uniquement via Laravel.

---

## 2. Point d'Entrée Laravel: `/admin/preview/{page?}`

### Vue actuelle (prompt 010)
```
GET /admin/preview → LegacyPreviewController@showLegacyContent
GET /admin/preview/{page} → LegacyPreviewController@showLegacyContent($page)

Middleware: catmin.admin (EnsureCatminAdminAuthenticated)
Response: Blade view { legacy-preview.blade.php } + $legacyContent (raw HTML)
```

### Améliorations Phase 1 (semaines 1-2)
```
GET /admin/preview
  → Lire whitelist depuis index.php
  → Valider page regex ^[a-z0-9_\-]+$
  → Inclure HTML depuis content/<page>.html
  → Wrapper dans admin/layouts/catmin.blade.php
  → Servir avec headers: Cache-Control: no-cache (dev)
```

**Conteneur de vue (resources/views/admin/pages/legacy-preview.blade.php):**
```blade
@extends('admin.layouts.catmin')

@section('content')
    <div class="legacy-content-wrapper" data-page="{{ $currentPage }}">
        {!! $legacyContent !!}
    </div>
@endsection

@section('scripts')
    <script>
        console.log('Page legacy chargée:', '{{ $currentPage }}');
    </script>
@endsection
```

### Résultat attendu
```
✓ /admin/preview/dashboard → Affiche dashboard.html dans layout Blade
✓ /admin/preview/forms_basic → Affiche forms_basic.html
✓ Tous les 28 fichiers accessible via Laravel
✓ CSS/JS de header.php et footer.php appliqués correctement
✓ Compatibilité 100% visuelle avec legacy index.php
```

---

## 3. Réutilisation des Composants Actuels

### Approche en 2 phases

**Phase 1: Blade Partials (semaines 1-2)**
Convertir les 4 composants PHP → Blade sans modification de contenu:

```blade
<!-- resources/views/admin/partials/head.blade.php -->
@include('admin.partials.head')

<!-- resources/views/admin/partials/aside.blade.php -->
@include('admin.partials.aside')

<!-- resources/views/admin/partials/topnav.blade.php -->
@include('admin.partials.topnav')

<!-- resources/views/admin/partials/footer.blade.php -->
@include('admin.partials.footer')
```

**Phase 2: Blade Components (semaines 3-4)**
Transformer partials → Blade classes:

```blade
<!-- Futur -->
<x-admin.nav />
<x-admin.sidebar />
<x-admin.topbar />
<x-admin.footer />
```

### Fichiers à conserver inchangés
```
dashboard/components/header.php  ← Copie dans resources/views/...
dashboard/components/aside.php   ← Copie dans resources/views/...
dashboard/components/topnav.php  ← Copie dans resources/views/...
dashboard/components/footer.php  ← Copie dans resources/views/...
```

Aucune modification du contenu HTML/JS/importmap jusqu'à Phase 3.

### Vérification CSS/JS
Tous les `<link>` et `<script>` doivent garder paths absolus ou `{{ asset() }}`:
```blade
<!-- OK: asset() résout depuis public/ -->
<link href="{{ asset('dashboard/assets/css/catmin.css') }}" rel="stylesheet">

<!-- OK: laisser importmap inchangé -->
<script type="importmap">
    {"imports": {"@lib/": "/dashboard/assets/js/@lib/"}}
</script>
```

---

## 4. Stratégie de Serveur pour `content/`

### Architecture actuelle
```
index.php → $_GET['page'] validation → include "content/<page>.html"
```

### Architecture Laravel Phase 1
```
GET /admin/preview/{page}
  ↓
LegacyPreviewController@showContent($page)
  ↓
  1. Valider page (whitelist + regex)
  2. Vérifie fichier: content/$page.html existe
  3. Lire contenu brut (pas d'include, file_get_contents)
  4. Passer à Blade comme variable $legacyContent
  5. Afficher dans wrapper avec layout
```

**Code controller (prompt 010 existant):**
```php
// app/Http/Controllers/Admin/LegacyPreviewController.php
public function showLegacyContent($page = 'dashboard')
{
    // Validate page parameter
    if (!preg_match('/^[a-z0-9_\-]+$/', $page)) {
        return redirect('/admin/errors/400');
    }

    // Check whitelist from index.php
    $whitelist = [...]; // 31 pages
    if (!in_array($page, $whitelist)) {
        return redirect('/admin/errors/404');
    }

    // Read file
    $filePath = base_path('dashboard/content/' . $page . '.html');
    if (!file_exists($filePath)) {
        return redirect('/admin/errors/404');
    }

    $content = file_get_contents($filePath);
    return view('admin.pages.legacy-preview', [
        'legacyContent' => $content,
        'currentPage' => $page,
    ]);
}
```

### Transition Phase 2 (semaine 3)
Extraire logique de contenu vers service:
```php
// app/Services/LegacyContentService.php
class LegacyContentService
{
    public function getPage($page) { ... }
    public function getWhitelist() { ... }
    public function validatePage($page) { ... }
}
```

### Transition Phase 3-4 (semaine 4+)
Loader à partir de database au lieu de fichiers:
```php
// Futur: content dans DB
$page = Article::where('slug', $page)->first();
```

---

## 5. Transition vers Blade: Roadmap

### Phase 1 (Semaine 1-2): Foundation [CURRENT]
| Étape | Tâche | Durée | Bloquant |
|-------|-------|-------|----------|
| 1a | Routes `/admin/preview/*` avec controller existant | 15 min | ✗ |
| 1b | Blade partials pour head, aside, topnav, footer | 30 min | ✗ |
| 1c | Tests manual: /admin/preview/dashboard via browser | 15 min | ✗ |
| 1d | Vérifier CSS/JS appliqué correctement | 15 min | ✗ |
| **Total Phase 1** | | **1 heure** | **Non** |

### Phase 2 (Semaine 2-3): Components
| Étape | Tâche | Durée | Bloquant |
|-------|-------|-------|----------|
| 2a | Blade components: `<x-admin.nav>`, `<x-admin.sidebar>` | 1 h | ✗ |
| 2b | Intégrer dans layout / legacy-preview | 30 min | ✗ |
| 2c | Tester 28 pages avec new components | 30 min | ✗ |
| 2d | Minifier CSS/JS duplicates (backups deleted) | 30 min | ✓ (opt) |
| **Total Phase 2** | | **2.5 heures** | **Non** |

### Phase 3 (Semaine 3-4): Database
| Étape | Tâche | Durée | Bloquant |
|-------|-------|-------|----------|
| 3a | Créer migration `articles` table (slug, content) | 30 min | ✗ |
| 3b | Seeder: charger 28 pages HTML → DB | 1 h | ✗ |
| 3c | New LegacyContentService queryer DB | 30 min | ✗ |
| 3d | Tests: /admin/preview/* continue de fonctionner | 30 min | ✗ |
| **Total Phase 3** | | **2.5 heures** | **Non** |

### Phase 4 (Semaine 4+): New Native Pages
| Étape | Tâche | Durée | Bloquant |
|-------|-------|-------|----------|
| 4a | 1ère vue Blade 100% natif (ex: `/blog`) | Variable | ✗ |
| 4b | Modules system intégration (cat-blog, etc) | Variable | ✓ |
| 4c | Afficher natif + legacy côte à côte in dev | Variable | ✗ |
| 4d | Progressive migration: 1 page legacy→native/semaine | Variable | ✗ |

---

## 6. Précautions CSS/JS: Non-Regression Map

### Assets protégés (TOUCHER JAMAIS)
```
✗ dashboard/assets/css/
✗ dashboard/assets/js/
✗ dashboard/assets/img/
✗ dashboard/assets/images/
✗ dashboard/components/
✗ dashboard/content/
```

**Action:** Chaque changement de CSS/JS doit être versionné en Git avec message commit explicite.

### Zones "Suspicious" (Refactor prioritaire mais sans urgence)
```
⚠ footer.php ligne 100-350: ~250 lignes JS inline
   → À extraire vers `dashboard/assets/js/footer-init.js` (Phase 2)
   
⚠ header.php ligne 180-220: importmap complexe
   → À documenter en config (Phase 3)
   
⚠ Fichiers CSS backup: catmin.css.bak, custom.css.old, etc.
   → À nettoyer en Phase 2 cleanup
```

### Validation checklist avant chaque commit
```
[ ] Aucun fichier deleted dans dashboard/
[ ] CSS/JS paths inchangés (relatifs ou {{ asset() }})
[ ] Layout Blade applique header.php intact
[ ] Footer inline scripts exécutés correctement
[ ] Éléments importmap chargent libs JS correctement
[ ] 28 pages accessible via /admin/preview/{page}
[ ] Aucune erreur console 404 sur assets
```

---

## 7. Procédure Technique: Étapes Immédiates (Semaines 1-2)

### 7.1 Routes Finales
```php
// app/routes/web.php - Ajouter:

Route::prefix('/admin')->middleware('catmin.admin')->group(function () {
    // Existing (prompt 010)
    Route::get('/login', [AuthController::class, 'showLogin'])->withoutMiddleware('catmin.admin');
    Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware('catmin.admin');
    Route::get('/logout', [AuthController::class, 'logout']);
    
    // New Phase 1 (prompt 015)
    Route::get('/preview', [LegacyPreviewController::class, 'showLegacyContent']);
    Route::get('/preview/{page}', [LegacyPreviewController::class, 'showLegacyContent']);
});

// Fallback: redirect legacy dashboard to Laravel
Route::get('/dashboard', function () {
    return redirect('/admin/preview/dashboard');
});
```

### 7.2 Validation Route
```bash
php artisan route:list --path=admin
# Output doit montrer:
# GET admin/login
# POST admin/login
# GET admin/logout
# GET admin/preview
# GET admin/preview/{page}
```

### 7.3 Blade Structure
```
resources/views/admin/
├── layouts/
│   └── catmin.blade.php (main container, @yield('content'))
├── partials/
│   ├── head.blade.php (from dashboard/components/header.php)
│   ├── aside.blade.php (from dashboard/components/aside.php)
│   ├── topnav.blade.php (from dashboard/components/topnav.php)
│   └── footer.blade.php (from dashboard/components/footer.php)
└── pages/
    ├── login.blade.php
    ├── legacy-preview.blade.php {!! $legacyContent !!}
    └── errors/
        ├── 400.blade.php
        ├── 403.blade.php
        ├── 404.blade.php
        └── 500.blade.php
```

### 7.4 Controller Update (Existing needs completing)
```php
// app/Http/Controllers/Admin/LegacyPreviewController.php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class LegacyPreviewController extends Controller
{
    // Whitelist from prompt 011 analysis
    protected $whitelist = [
        'dashboard', 'calendar', 'chartjs', 'forms_basic', 'forms_advanced',
        'forms_elements', 'forms_layouts', 'forms_validation', 'forms_wizard',
        'table_bootstrap', 'table_datatable', 'chart_bars', 'chart_lines',
        'chart_mixed', 'ecommerce_cart', 'ecommerce_list', 'ecommerce_summary',
        'profil_page', 'projects_grid', 'projects_list', 'contacts_page',
        'inbox_page', 'general_elements', 'icons', 'media_gallery',
        'typography_page', 'widgets', 'map_embed', 'plain_page'
    ];

    public function showLegacyContent($page = 'dashboard')
    {
        // Validate regex
        if (!preg_match('/^[a-z0-9_\-]+$/', $page)) {
            return redirect('/admin/errors/400');
        }

        // Check whitelist
        if (!in_array($page, $this->whitelist)) {
            return redirect('/admin/errors/404');
        }

        // Read file
        $filePath = base_path('dashboard/content/' . $page . '.html');
        if (!file_exists($filePath)) {
            return redirect('/admin/errors/404');
        }

        $content = file_get_contents($filePath);
        
        return view('admin.pages.legacy-preview', [
            'legacyContent' => $content,
            'currentPage' => $page,
        ]);
    }
}
```

### 7.5 Test Checklist
```
[ ] php artisan serve → 0 errors
[ ] GET /admin/login → login form displayed
[ ] POST /admin/login (credentials) → redirects to /admin/access
[ ] GET /admin/access → redirects to legacy dashboard
[ ] GET /admin/preview/dashboard → displays dashboard.html in Blade layout
[ ] GET /admin/preview/forms_basic → displays forms_basic.html in Blade layout
[ ] GET /admin/preview/nonexistent → redirects to /admin/errors/404
[ ] All CSS/JS paths resolve 200 OK (no 404)
[ ] importmap chargement correct
[ ] Sidebar nav interactable (links via ?page=...)
```

---

## 8. Avertissements & Précautions Critiques

### ✗ Ne JAMAIS faire
```
✗ Supprimer dashboard/
✗ Renommer dashboard/content/*.html
✗ Modifier routes actuelles HTTP GET /dashboard/*
✗ Charger content/ via PHP include() en Laravel
✗ Réécrire CSS/JS asset paths sans {{ asset() }}
✗ Casser le localStorage/sessionStorage de footer.js
```

### ✓ À FAIRE
```
✓ Conserver tous fichiers legacy sur disque
✓ Passer contenu via file_get_contents() non parse
✓ Utiliser {!! $content !!} unescaped dans Blade
✓ Tester visuellement chaque page après changement
✓ Versioner Git chaque petit changement
✓ Documenter toute dépendance CSS/JS découverte
```

### 🔍 Vérifications Post-Implémentation
Après chaque phase, avant de passer à la suivante:
```bash
# 1. Routes OK
php artisan route:list --path=admin | grep preview

# 2. Views existent
ls -la resources/views/admin/pages/legacy-preview.blade.php
ls -la resources/views/admin/layouts/catmin.blade.php

# 3. Git status propre
git status --short

# 4. Browser test
curl http://catmin.local/admin/preview/dashboard | grep -q "DOCTYPE"
```

---

## 9. Résumé Plan: Timeline Estimée

| Phase | Durée | Livrables | Etat Coexistence |
|-------|-------|-----------|------------------|
| **0 (Current)** | Déjà fait | Routes `/admin/*` setup | Legacy 100% autonome |
| **1** | 1 h | Routes `/admin/preview/*` + Blade partials | Legacy + Laravel side-by-side |
| **2** | 2.5 h | Blade components (nav, sidebar) | Legacy + Laravel (comps unified) |
| **3** | 2.5 h | DB storage + LegacyContentService | Legacy + Laravel (content unified) |
| **4** | Variable | 1ère page native, modules system | Progressive migration |
| **Total** | ~6 h | Full Laravel wrapper | Zero broken features |

---

## 10. Prochaines Étapes (Prompts 016+)

Ce plan valide, les prompts suivants implémenteront phase par phase:
- **016:** Database setup (Prisma/Eloquent config)
- **017:** Navigation et routing administration
- **018:** Frontend foundation (Blade structure, CSS reset)
- **019-024:** Module implementations (blog, pages, media, etc)
- **025-030:** SEO, webhooks, advanced integrations

---

## Validation Plan

**Points de validation critique:**
- [x] Stratégie coexistence définie (prompt 015)
- [x] Point d'entrée Laravel établi (`/admin/preview/*`)
- [x] Composants réutilisables identifiés (header/aside/topnav/footer)
- [x] Content serving strategy planifiée (file_get_contents vs DB)
- [x] Blade transition roadmap décrite (4 phases)
- [x] Précautions CSS/JS documentées
- [x] Procédure technique concrète établie
- [x] Timeline réaliste fournie

**Statut:** ✅ **Plan prêt pour implémentation - Pas de blocage détecté**

---

## Notes Archivistiques

Ce document synthétise:
- Prompt 011: Cartographie index.php
- Prompt 012: Cartographie components
- Prompt 013: Cartographie content
- Prompt 014: Inventaire CSS/JS
- Prompt 006: Intégration admin
- Prompt 008: Conventions modules

En créant le plan d'exécution unifiée pour architecture cohérente.
