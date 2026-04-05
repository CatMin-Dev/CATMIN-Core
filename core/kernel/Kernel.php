<?php

declare(strict_types=1);

namespace Core\kernel;

use Core\http\Request;
use Core\http\Response;
use Core\logs\Logger;
use Core\router\Router;

final class Kernel
{
    public function __construct(private readonly Router $router) {}

    public function handle(Request $request): Response
    {
        $content = $this->router->dispatch($request);

        if ($content !== null) {
            return new Response($content);
        }

        $view = match (CATMIN_AREA) {
            'admin' => CATMIN_ADMIN . '/views/dashboard.php',
            default => CATMIN_FRONT . '/views/home.php',
        };

        if (!is_file($view)) {
            Logger::error('Missing fallback view', ['view' => $view, 'area' => CATMIN_AREA]);
            return new Response('View not found', 500);
        }

        ob_start();
        require $view;
        $output = (string) ob_get_clean();

        return new Response($output, 200, [
            'X-Robots-Tag' => CATMIN_AREA === 'front' ? 'noindex, nofollow' : 'noindex, nofollow',
        ]);
    }
}
