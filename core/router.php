<?php

declare(strict_types=1);

require_once __DIR__ . '/request.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/route-context.php';
require_once __DIR__ . '/route-collection.php';
require_once __DIR__ . '/route-resolver.php';
require_once __DIR__ . '/route-dispatcher.php';
require_once __DIR__ . '/middleware-pipeline.php';
require_once __DIR__ . '/route-guard.php';
require_once __DIR__ . '/exceptions/RouteNotFoundException.php';
require_once __DIR__ . '/exceptions/MethodNotAllowedException.php';
require_once __DIR__ . '/exceptions/ForbiddenRouteException.php';
require_once __DIR__ . '/exceptions/InvalidRouteDefinitionException.php';
require_once __DIR__ . '/exceptions/MiddlewareAbortException.php';
require_once __DIR__ . '/support/pattern-matcher.php';
require_once __DIR__ . '/support/url-generator.php';
require_once __DIR__ . '/support/route-helpers.php';
require_once __DIR__ . '/module-loader.php';
require_once __DIR__ . '/error-dispatcher.php';

final class Router
{
    private static ?self $runtime = null;

    private RouteCollection $collection;
    private bool $routesLoaded = false;

    public function __construct(?RouteCollection $collection = null)
    {
        $this->collection = $collection ?? new RouteCollection();
    }

    public static function runtime(): self
    {
        if (self::$runtime === null) {
            self::$runtime = new self();
        }

        return self::$runtime;
    }

    public function get(string $path, mixed $handler, array $options = []): void
    {
        $this->collection->get($path, $handler, $options);
    }

    public function post(string $path, mixed $handler, array $options = []): void
    {
        $this->collection->post($path, $handler, $options);
    }

    /** @param array<int, string> $methods */
    public function match(array $methods, string $path, mixed $handler, array $options = []): void
    {
        $this->collection->match($methods, $path, $handler, $options);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->collection->group($attributes, fn (RouteCollection $collection) => $callback($this));
    }

    public function name(string $name): self
    {
        $this->collection->name($name);

        return $this;
    }

    public function middleware(array|string $middlewares): self
    {
        $this->collection->middleware($middlewares);

        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->collection->prefix($prefix);

        return $this;
    }

    public function fallback(mixed $handler): void
    {
        $this->collection->fallback($handler);
    }

    public static function dispatch(): void
    {
        $response = self::runtime()->dispatchRequest(Request::capture());
        $response->send();
    }

    public function dispatchRequest(Request $request): Response
    {
        $this->loadRoutesIfNeeded();

        $zone = defined('CATMIN_AREA') ? (string) CATMIN_AREA : 'front';
        $adminPath = $this->adminPath();

        $coreRequest = $request->toCoreRequest();
        $security = new Core\security\SecurityManager($coreRequest, $zone);
        $security->boot();

        try {
            $resolver = new RouteResolver();
            $context = $resolver->resolve($request, $this->collection, $zone, $adminPath);

            $dispatcher = new RouteDispatcher(new RouteGuard(), $security, $zone, $adminPath);
            $coreResponse = $dispatcher->dispatch($context, $coreRequest);
        } catch (MethodNotAllowedException $e) {
            $coreResponse = (new CoreErrorDispatcher())->response(405, [
                'title' => 'Méthode non autorisée',
                'message' => 'Méthode HTTP non autorisée pour cette route.',
            ], ['Allow' => implode(', ', $e->allowedMethods())]);
        } catch (RouteNotFoundException) {
            $fallback = $this->collection->fallbackHandler();
            if ($fallback !== null) {
                $coreResponse = Core\http\Response::html((string) $fallback($coreRequest), 404);
            } else {
                $coreResponse = (new CoreErrorDispatcher())->response(404);
            }
        } catch (ForbiddenRouteException|MiddlewareAbortException) {
            $coreResponse = (new CoreErrorDispatcher())->response(403);
        } catch (Throwable $e) {
            Core\logs\Logger::error('Routing dispatch failure', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $coreResponse = (new CoreErrorDispatcher())->response(500);
        }

        return Response::fromCoreResponse($security->apply($coreResponse));
    }

    public static function urlGenerator(): UrlGenerator
    {
        $router = self::runtime();
        $router->loadRoutesIfNeeded();

        return new UrlGenerator($router->collection, $router->adminPath());
    }

    private function loadRoutesIfNeeded(): void
    {
        if ($this->routesLoaded) {
            return;
        }

        foreach (['front', 'admin', 'install'] as $zone) {
            $this->loadZoneRoutes($zone);
            $this->loadModuleRoutes($zone);
        }

        $this->routesLoaded = true;
    }

    private function loadZoneRoutes(string $zone): void
    {
        $file = match ($zone) {
            'admin' => CATMIN_ADMIN . '/routes.php',
            'install' => CATMIN_INSTALL . '/routes.php',
            default => CATMIN_FRONT . '/routes.php',
        };

        if (!is_file($file)) {
            return;
        }

        $routes = require $file;
        $this->ingestRoutes($routes, $zone, null);
    }

    private function loadModuleRoutes(string $zone): void
    {
        $loader = new CoreModuleLoader();
        foreach ($loader->loadableForZone($zone) as $module) {
            $routesFile = (string) ($module['routes_file'] ?? '');
            if ($routesFile === '' || !is_file($routesFile)) {
                continue;
            }
            $slug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            $routes = require $routesFile;
            $this->ingestRoutes($routes, $zone, $slug !== '' ? $slug : null);
        }
    }

    private function ingestRoutes(mixed $routes, string $zone, ?string $module): void
    {
        if (is_array($routes)) {
            $this->collection->addFromDefinitions($routes, $zone, $module);
            return;
        }

        if (is_callable($routes)) {
            $routes($this);
            return;
        }

        throw new InvalidRouteDefinitionException('Routes definition must be array or callable for zone: ' . $zone);
    }

    private function adminPath(): string
    {
        $candidate = trim((string) config('security.admin_path', 'admin'), '/');
        $forbidden = ['', '.', '..', 'install', 'core', 'modules', 'storage', 'public'];

        if (in_array(strtolower($candidate), $forbidden, true) || str_contains($candidate, '..')) {
            return 'admin';
        }

        return $candidate;
    }
}
