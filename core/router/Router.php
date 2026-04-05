<?php

declare(strict_types=1);

namespace Core\router;

use Core\http\Request;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][rtrim($path, '/') ?: '/'] = $handler;
    }

    public function dispatch(Request $request): ?string
    {
        $method = $request->method();
        $path = rtrim($request->path(), '/') ?: '/';

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            return null;
        }

        return (string) $handler($request);
    }
}
