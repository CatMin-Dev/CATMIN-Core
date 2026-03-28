<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CatminAddonMakeCommand extends Command
{
    protected $signature = 'catmin:addon:make
        {name : Nom lisible de l\'addon}
        {slug : Slug machine (kebab-case)}
        {--description= : Description courte}
        {--addon-version=1.0.0 : Version initiale}
        {--depends=core : Dependances modules (liste separee par virgules)}
        {--category=feature : Type/categorie addon}
        {--author=CATMIN Team : Auteur de l\'addon}
        {--enable : Activer immediatement l\'addon}
        {--path= : Repertoire cible (defaut: config catmin.addons.path)}
        {--force : Ecraser si le dossier existe deja}';

    protected $description = 'Generer un squelette addon CATMIN complet et maintenable';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $slug = Str::lower(trim((string) $this->argument('slug')));
        $description = trim((string) ($this->option('description') ?: 'Addon CATMIN genere automatiquement.'));
        $version = trim((string) $this->option('addon-version'));
        $category = trim((string) $this->option('category'));
        $author = trim((string) $this->option('author'));
        $enabled = (bool) $this->option('enable');

        if (!$this->isValidSlug($slug)) {
            $this->error('Slug invalide. Format attendu: kebab-case (ex: my-addon).');
            return self::FAILURE;
        }

        if ($version === '' || !preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->error('Version invalide. Format attendu: X.Y.Z');
            return self::FAILURE;
        }

        $depends = $this->parseDepends((string) $this->option('depends'));
        if ($depends === []) {
            $depends = ['core'];
        }

        $addonsBasePath = $this->resolveAddonsPath();
        $addonPath = rtrim($addonsBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $slug;

        if (File::exists($addonPath)) {
            if (!(bool) $this->option('force')) {
                $this->error("Le dossier addon existe deja: {$addonPath}");
                return self::FAILURE;
            }

            File::deleteDirectory($addonPath);
        }

        File::ensureDirectoryExists($addonsBasePath);

        $studlySlug = Str::studly(str_replace(['-', '_'], ' ', $slug));
        $classBase = preg_replace('/\s+/', '', $studlySlug) ?: 'GeneratedAddon';
        $controllerClass = $classBase . 'AdminController';
        $viewNamespace = 'addon_' . str_replace('-', '_', $slug);
        $routeName = 'admin.addon.' . str_replace('-', '_', $slug) . '.index';

        $this->createDirectories($addonPath);
        $this->writeFile($addonPath . '/addon.json', $this->buildAddonJson(
            name: $name,
            slug: $slug,
            description: $description,
            version: $version,
            author: $author,
            enabled: $enabled,
            depends: $depends,
            category: $category
        ));
        $this->writeFile($addonPath . '/routes.php', $this->buildRoutesPhp($slug, $classBase, $controllerClass, $viewNamespace));
        $this->writeFile($addonPath . '/Controllers/Admin/' . $controllerClass . '.php', $this->buildControllerPhp($classBase, $controllerClass, $viewNamespace, $name, $slug));
        $this->writeFile($addonPath . '/Views/admin/index.blade.php', $this->buildAdminView($name, $slug, $category, $version));
        $this->writeFile($addonPath . '/Services/' . $classBase . 'Service.php', $this->buildServicePhp($classBase, $name, $slug));
        $this->writeFile($addonPath . '/config.php', $this->buildConfigPhp($slug, $category));
        $this->writeFile($addonPath . '/hooks.php', "<?php\n\nreturn [];\n");
        $this->writeFile($addonPath . '/Docs/README.md', $this->buildDocsReadme($name, $slug, $description, $depends, $category, $routeName));
        $this->writeFile($addonPath . '/Assets/css/addon.css', "/* {$slug} addon styles */\n");
        $this->writeFile($addonPath . '/Assets/js/addon.js', "// {$slug} addon scripts\n");
        $this->writeFile($addonPath . '/Migrations/.gitkeep', "\n");
        $this->writeFile($addonPath . '/Models/.gitkeep', "\n");

        $customPath = trim((string) $this->option('path'));
        if ($customPath === '') {
            AddonManager::clearCache();

            if (!AddonManager::exists($slug)) {
                $this->error('Addon genere mais non detecte par AddonManager.');
                return self::FAILURE;
            }
        }

        $this->info('Addon genere avec succes.');
        $this->line('Nom: ' . $name);
        $this->line('Slug: ' . $slug);
        $this->line('Chemin: ' . $addonPath);
        $this->line('Active: ' . ($enabled ? 'oui' : 'non'));
        $this->line('Dependances: ' . implode(', ', $depends));
        $this->line('Route admin: ' . $routeName);

        return self::SUCCESS;
    }

    protected function resolveAddonsPath(): string
    {
        $customPath = trim((string) $this->option('path'));
        if ($customPath !== '') {
            return str_starts_with($customPath, '/') ? $customPath : base_path($customPath);
        }

        return base_path((string) config('catmin.addons.path', 'addons'));
    }

    protected function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * @return array<int, string>
     */
    protected function parseDepends(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn (string $value) => trim(Str::lower($value)))
            ->filter(fn (string $value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function createDirectories(string $addonPath): void
    {
        $directories = [
            'Controllers/Admin',
            'Models',
            'Services',
            'Views/admin',
            'Migrations',
            'Assets/css',
            'Assets/js',
            'Docs',
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists($addonPath . '/' . $directory);
        }
    }

    /**
     * @param array<int, string> $depends
     */
    protected function buildAddonJson(
        string $name,
        string $slug,
        string $description,
        string $version,
        string $author,
        bool $enabled,
        array $depends,
        string $category
    ): string {
        $manifest = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'version' => $version,
            'author' => $author,
            'enabled' => $enabled,
            'dependencies' => $depends,
            'depends_modules' => $depends,
            'category' => $category,
            'routes' => true,
            'has_migrations' => true,
            'has_assets' => true,
            'has_views' => true,
            'requires_core' => true,
        ];

        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    protected function buildRoutesPhp(
        string $slug,
        string $classBase,
        string $controllerClass,
        string $viewNamespace
    ): string {
        $routePath = '/addons/' . $slug;

        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

require_once __DIR__ . '/Controllers/Admin/{$controllerClass}.php';

View::addNamespace('{$viewNamespace}', __DIR__ . '/Views');

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('{$routePath}', [\Addons\\{$classBase}\\Controllers\\Admin\\{$controllerClass}::class, 'index'])
            ->name('addon.{$this->slugToRouteKey($slug)}.index');
    });
PHP;
    }

    protected function buildControllerPhp(
        string $classBase,
        string $controllerClass,
        string $viewNamespace,
        string $name,
        string $slug
    ): string {
        $escapedName = addslashes($name);

        return <<<PHP
<?php

namespace Addons\\{$classBase}\\Controllers\\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class {$controllerClass} extends Controller
{
    public function index(): View
    {
        return view('{$viewNamespace}::admin.index', [
            'addonName' => '{$escapedName}',
            'addonSlug' => '{$slug}',
        ]);
    }
}
PHP;
    }

    protected function buildAdminView(string $name, string $slug, string $category, string $version): string
    {
        return <<<BLADE
<div style="padding: 24px; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;">
    <h1 style="margin: 0 0 12px 0; font-size: 24px;">{$name}</h1>
    <p style="margin: 0 0 8px 0; color: #555;">Addon CATMIN genere automatiquement.</p>
    <ul style="margin: 0; padding-left: 20px; color: #333;">
        <li><strong>slug</strong>: {$slug}</li>
        <li><strong>category</strong>: {$category}</li>
        <li><strong>version</strong>: {$version}</li>
    </ul>
</div>
BLADE;
    }

    protected function buildServicePhp(string $classBase, string $name, string $slug): string
    {
        return <<<PHP
<?php

namespace Addons\\{$classBase}\\Services;

class {$classBase}Service
{
    /**
     * @return array<string, string>
     */
    public function info(): array
    {
        return [
            'name' => '{$name}',
            'slug' => '{$slug}',
        ];
    }
}
PHP;
    }

    protected function buildConfigPhp(string $slug, string $category): string
    {
        return <<<PHP
<?php

return [
    'slug' => '{$slug}',
    'category' => '{$category}',
];
PHP;
    }

    /**
     * @param array<int, string> $depends
     */
    protected function buildDocsReadme(
        string $name,
        string $slug,
        string $description,
        array $depends,
        string $category,
        string $routeName
    ): string {
        $dependsText = $depends === [] ? '- aucune' : '- ' . implode("\n- ", $depends);

        return <<<MD
# {$name}

## Role
{$description}

## Point d'entree
- Route admin: `{$routeName}`
- Fichier routes: `routes.php`
- Controleur: `Controllers/Admin/*AdminController.php`

## Dependances modules
{$dependsText}

## Categorie
- {$category}

## Prochaines etapes
- Ajouter les ecrans metier dans `Views/admin`.
- Ajouter les services metier dans `Services`.
- Ajouter les migrations necessaires dans `Migrations`.
- Connecter les hooks/events via `hooks.php`.
MD;
    }

    protected function writeFile(string $path, string $content): void
    {
        File::put($path, $content);
    }

    protected function slugToRouteKey(string $slug): string
    {
        return str_replace('-', '_', $slug);
    }
}
