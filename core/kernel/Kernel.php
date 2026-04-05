<?php

declare(strict_types=1);

namespace Core\kernel;

use Core\http\Request;
use Core\http\Response;
use Core\logs\Logger;
use Core\router\Router;
use Core\security\SecurityManager;
use Core\support\PathManager;
use Throwable;

final class Kernel
{
    private bool $booted = false;

    public function __construct(
        private readonly Router $router,
        private readonly PathManager $paths = new PathManager()
    ) {}

    public function handle(Request $request): Response
    {
        $this->boot();

        $security = new SecurityManager($request, CATMIN_AREA);
        $security->boot();

        $denied = $security->enforceIpWhitelist();
        if ($denied instanceof Response) {
            return $security->apply($denied);
        }

        try {
            $response = $this->router->dispatch($request, CATMIN_AREA);
        } catch (Throwable $exception) {
            Logger::error('Kernel dispatch failure', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $response = Response::text('Internal Server Error', 500);
        }

        return $security->apply($response);
    }

    private function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach (['front', 'admin', 'install'] as $group) {
            $routesFile = $this->paths->routesFile($group);
            if (!is_file($routesFile)) {
                continue;
            }

            $routes = require $routesFile;
            if (is_array($routes)) {
                $this->router->loadGroup($group, $routes);
            }
        }

        $this->booted = true;
    }
}
