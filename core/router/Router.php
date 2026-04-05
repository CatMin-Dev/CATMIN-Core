<?php

declare(strict_types=1);

namespace Core\router;

use Core\http\MiddlewareStack;
use Core\http\Request;
use Core\http\Response;

final class Router
{
    private array $routes = [];
    private array $groupContext = [];

    public function group(string $name, string $prefix, array $middlewares, callable $callback): void
    {
        $this->groupContext[$name] = [
            'prefix' => '/' . trim($prefix, '/'),
            'middlewares' => $middlewares,
        ];

        $callback($this);
    }

    public function add(string $group, string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $context = $this->groupContext[$group] ?? ['prefix' => '', 'middlewares' => []];
        $fullPath = $this->normalizePath(($context['prefix'] ?? '') . '/' . trim($path, '/'));

        $this->routes[$group][strtoupper($method)][$fullPath] = [
            'handler' => $handler,
            'middlewares' => array_merge($context['middlewares'], $middlewares),
        ];
    }

    public function get(string $group, string $path, callable $handler, array $middlewares = []): void
    {
        $this->add($group, 'GET', $path, $handler, $middlewares);
    }

    public function post(string $group, string $path, callable $handler, array $middlewares = []): void
    {
        $this->add($group, 'POST', $path, $handler, $middlewares);
    }

    /**
     * @param array<int, array{method?:string,path?:string,handler?:callable,middleware?:array}> $definitions
     */
    public function loadGroup(string $group, array $definitions): void
    {
        foreach ($definitions as $route) {
            $handler = $route['handler'] ?? null;
            if (!is_callable($handler)) {
                continue;
            }

            $method = strtoupper((string) ($route['method'] ?? 'GET'));
            $path = (string) ($route['path'] ?? '/');
            $middlewares = is_array($route['middleware'] ?? null) ? $route['middleware'] : [];

            $this->add($group, $method, $path, $handler, $middlewares);
        }
    }

    public function dispatch(Request $request, string $group): Response
    {
        $method = $request->method();
        $path = $this->normalizePath($request->path());

        $route = $this->routes[$group][$method][$path] ?? null;
        if (!is_array($route)) {
            return Response::text('Not Found', 404);
        }

        $stack = new MiddlewareStack();
        $result = $stack->handle(
            $request->withAttribute('route_group', $group),
            $route['middlewares'] ?? [],
            fn (Request $request) => ($route['handler'])($request)
        );

        return $this->toResponse($result);
    }

    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');
        return $normalized === '//' ? '/' : $normalized;
    }
}
