<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

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
        {--permissions= : Permissions addon (liste separee par virgules)}
        {--enable : Activer immediatement l\'addon}
        {--with-events : Generer stubs Events et Listeners}
        {--with-ui-hooks : Generer un exemple de hook UI admin}
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
        $withEvents = (bool) $this->option('with-events');
        $withUiHooks = (bool) $this->option('with-ui-hooks');
        $defaultPermission = 'addon.' . str_replace('-', '_', $slug) . '.menu';
        $permissions = $this->parseDepends((string) ($this->option('permissions') ?: $defaultPermission));

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

        $this->createDirectories($addonPath, $withEvents);

        $emittedEvents = [
            'addon.' . str_replace('-', '_', $slug) . '.configured',
        ];
        $listenedEvents = ['setting.updated'];
        $uiHooks = $withUiHooks ? ['after:admin.topbar'] : [];

        $this->writeFile($addonPath . '/addon.json', $this->buildAddonJson(
            name: $name,
            slug: $slug,
            description: $description,
            version: $version,
            author: $author,
            enabled: $enabled,
            depends: $depends,
            category: $category,
            emittedEvents: $emittedEvents,
            listenedEvents: $listenedEvents,
            uiHooks: $uiHooks,
            permissions: $permissions
        ));
        $this->writeFile($addonPath . '/routes.php', $this->buildRoutesPhp($slug, $classBase, $controllerClass, $viewNamespace));
        $this->writeFile($addonPath . '/Controllers/Admin/' . $controllerClass . '.php', $this->buildControllerPhp($classBase, $controllerClass, $viewNamespace, $name, $slug));
        $this->writeFile($addonPath . '/Views/admin/index.blade.php', $this->buildAdminView($name, $slug, $category, $version));
        $this->writeFile($addonPath . '/Services/' . $classBase . 'Service.php', $this->buildServicePhp($classBase, $name, $slug));
        $this->writeFile($addonPath . '/config.php', $this->buildConfigPhp($slug, $category));
        $this->writeFile($addonPath . '/hooks.php', $this->buildHooksPhp($name, $slug, $withUiHooks));
        $this->writeFile($addonPath . '/Docs/README.md', $this->buildDocsReadme($name, $slug, $description, $version, $depends, $category, $routeName, $permissions, $emittedEvents, $listenedEvents, $uiHooks));
        $this->writeFile($addonPath . '/Assets/css/addon.css', "/* {$slug} addon styles */\n");
        $this->writeFile($addonPath . '/Assets/js/addon.js', "// {$slug} addon scripts\n");
        $this->writeFile($addonPath . '/Migrations/.gitkeep', "\n");
        $this->writeFile($addonPath . '/Models/.gitkeep', "\n");

        if ($withEvents) {
            $eventClass = $classBase . 'ConfiguredEvent';
            $listenerClass = 'Log' . $classBase . 'ConfiguredListener';

            $this->writeFile($addonPath . '/Events/' . $eventClass . '.php', $this->buildEventClassPhp($classBase, $eventClass, $slug));
            $this->writeFile($addonPath . '/Listeners/' . $listenerClass . '.php', $this->buildListenerClassPhp($classBase, $eventClass, $listenerClass));
        }

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

    protected function createDirectories(string $addonPath, bool $withEvents): void
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

        if ($withEvents) {
            $directories[] = 'Events';
            $directories[] = 'Listeners';
        }

        foreach ($directories as $directory) {
            File::ensureDirectoryExists($addonPath . '/' . $directory);
        }
    }

    /**
     * @param array<int, string> $depends
     * @param array<int, string> $emittedEvents
     * @param array<int, string> $listenedEvents
     * @param array<int, string> $uiHooks
     * @param array<int, string> $permissions
     */
    protected function buildAddonJson(
        string $name,
        string $slug,
        string $description,
        string $version,
        string $author,
        bool $enabled,
        array $depends,
        string $category,
        array $emittedEvents,
        array $listenedEvents,
        array $uiHooks,
        array $permissions
    ): string {
        $manifest = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'version' => $version,
            'author' => $author,
            'required_core_version' => '3.0.0',
            'required_php_version' => '8.2.0',
            'enabled' => $enabled,
            'dependencies' => [],
            'required_modules' => $depends,
            'depends_modules' => $depends,
            'category' => $category,
            'has_routes' => true,
            'has_migrations' => true,
            'has_assets' => true,
            'has_views' => true,
            'has_events' => !empty($emittedEvents) || !empty($listenedEvents) || !empty($uiHooks),
            'requires_core' => true,
            'entrypoints' => [
                'admin_routes' => 'routes.php',
                'docs' => 'Docs/README.md',
            ],
            'homepage' => '',
            'docs_url' => '',
            'changelog' => '',
            'compatibility' => [
                'supports_registry' => true,
                'supports_auto_install' => true,
            ],
            'install_notes' => 'Verifier les dependances modules avant activation.',
            'events_emitted' => $emittedEvents,
            'events_listens' => $listenedEvents,
            'ui_hooks' => $uiHooks,
            'permissions_declared' => $permissions,
            'permissions' => $permissions,
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

    protected function buildHooksPhp(string $name, string $slug, bool $withUiHooks): string
    {
        $template = <<<'PHP'
<?php

use App\Services\CatminEventBus;
use App\Services\CatminHookRegistry;

CatminEventBus::listen(CatminEventBus::SETTING_UPDATED, function (array $payload): void {
    \Log::info('Addon listener __ADDON_SLUG__ recu', [
        'addon' => '__ADDON_NAME__',
        'event' => 'setting.updated',
        'setting_key' => (string) (
            $payload['setting']['key']
            ?? $payload['key']
            ?? 'unknown'
        ),
    ]);
});

__UI_HOOK_BLOCK__
PHP;

        $uiHookBlock = $withUiHooks
            ? "CatminHookRegistry::after('admin.topbar', function (): string {\n    return '<span style=\"margin-left:12px;color:#0f766e;font-size:12px;\">addon __ADDON_SLUG__ hook actif</span>';\n});"
            : '// UI hook generation disabled (--with-ui-hooks not provided).';

        return str_replace(
            ['__ADDON_SLUG__', '__ADDON_NAME__', '__UI_HOOK_BLOCK__'],
            [$slug, addslashes($name), $uiHookBlock],
            $template
        );
    }

    protected function buildEventClassPhp(string $classBase, string $eventClass, string $slug): string
    {
        $template = <<<'PHP'
<?php

namespace Addons\\__CLASS_BASE__\\Events;

class __EVENT_CLASS__
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public array $payload = []
    ) {
        if ($this->payload === []) {
            $this->payload = [
                'addon' => '__ADDON_SLUG__',
                'occurred_at' => now()->toISOString(),
            ];
        }
    }
}
PHP;

        return str_replace(
            ['__CLASS_BASE__', '__EVENT_CLASS__', '__ADDON_SLUG__'],
            [$classBase, $eventClass, $slug],
            $template
        );
    }

    protected function buildListenerClassPhp(string $classBase, string $eventClass, string $listenerClass): string
    {
        $template = <<<'PHP'
<?php

namespace Addons\\__CLASS_BASE__\\Listeners;

use Addons\\__CLASS_BASE__\\Events\\__EVENT_CLASS__;

class __LISTENER_CLASS__
{
    public function handle(__EVENT_CLASS__ $event): void
    {
        \Log::info('Addon event handled', $event->payload);
    }
}
PHP;

        return str_replace(
            ['__CLASS_BASE__', '__EVENT_CLASS__', '__LISTENER_CLASS__'],
            [$classBase, $eventClass, $listenerClass],
            $template
        );
    }

    /**
     * @param array<int, string> $depends
     * @param array<int, string> $permissions
     * @param array<int, string> $emittedEvents
     * @param array<int, string> $listenedEvents
     * @param array<int, string> $uiHooks
     */
    protected function buildDocsReadme(
        string $name,
        string $slug,
        string $description,
        string $version,
        array $depends,
        string $category,
        string $routeName,
        array $permissions,
        array $emittedEvents,
        array $listenedEvents,
        array $uiHooks
    ): string {
        $dependsText = $depends === [] ? '- aucune' : '- ' . implode("\n- ", $depends);
        $permissionsText = $permissions === [] ? '- aucune' : '- ' . implode("\n- ", $permissions);
        $emittedText = $emittedEvents === [] ? '- aucun' : '- ' . implode("\n- ", $emittedEvents);
        $listenedText = $listenedEvents === [] ? '- aucun' : '- ' . implode("\n- ", $listenedEvents);
        $hooksText = $uiHooks === [] ? '- aucun' : '- ' . implode("\n- ", $uiHooks);
        $configText = "- slug\n- category";

        $templatePath = base_path('core/templates/addon-doc-template.md');
        if (!File::exists($templatePath)) {
            throw new RuntimeException('Template de documentation addon introuvable: ' . $templatePath);
        }

        $template = (string) File::get($templatePath);

        return str_replace([
            '__ADDON_NAME__',
            '__ADDON_SLUG__',
            '__ADDON_DESCRIPTION__',
            '__ADDON_VERSION__',
            '__ADDON_CATEGORY__',
            '__ADDON_ROUTE__',
            '__ADDON_DEPENDENCIES__',
            '__ADDON_PERMISSIONS__',
            '__ADDON_EVENTS_EMITTED__',
            '__ADDON_EVENTS_LISTENED__',
            '__ADDON_UI_HOOKS__',
            '__ADDON_CONFIG_KEYS__',
        ], [
            $name,
            $slug,
            $description,
            $version,
            $category,
            $routeName,
            $dependsText,
            $permissionsText,
            $emittedText,
            $listenedText,
            $hooksText,
            $configText,
        ], $template);
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
