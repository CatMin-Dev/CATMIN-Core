<?php

declare(strict_types=1);

final class RouteResolver
{
    public function resolve(Request $request, RouteCollection $collection, string $zone, string $adminPath): RouteContext
    {
        $path = $this->relativePath($request->path(), $zone, $adminPath);
        $method = strtoupper($request->method());

        $allowedMethods = [];

        foreach ($collection->routesForMethod($method) as $route) {
            if ((string) ($route['zone'] ?? 'front') !== $zone) {
                continue;
            }

            if (preg_match((string) $route['regex'], $path, $matches) !== 1) {
                continue;
            }

            $parameters = [];
            foreach ((array) ($route['params'] ?? []) as $name) {
                if (isset($matches[$name])) {
                    $parameters[$name] = (string) $matches[$name];
                }
            }

            return new RouteContext(
                $zone,
                $path,
                is_string($route['name'] ?? null) ? $route['name'] : null,
                $parameters,
                is_array($route['middleware'] ?? null) ? $route['middleware'] : [],
                is_string($route['module'] ?? null) ? $route['module'] : null,
                $route['handler'] ?? null,
                $method,
                is_array($route['where'] ?? null) ? $route['where'] : []
            );
        }

        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $probe) {
            if ($probe === $method) {
                continue;
            }

            foreach ($collection->routesForMethod($probe) as $route) {
                if ((string) ($route['zone'] ?? 'front') !== $zone) {
                    continue;
                }

                if (preg_match((string) $route['regex'], $path) === 1) {
                    $allowedMethods[] = $probe;
                }
            }
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowedMethods)));
        }

        throw new RouteNotFoundException('Route not found: ' . $method . ' ' . $path);
    }

    private function relativePath(string $path, string $zone, string $adminPath): string
    {
        $normalized = PatternMatcher::normalize($path);
        if ($this->isForbiddenCorePath($normalized)) {
            throw new ForbiddenRouteException('Forbidden core path.');
        }

        if ($zone === 'admin') {
            $base = PatternMatcher::normalize('/' . trim($adminPath, '/'));
            if ($base === '/') {
                throw new ForbiddenRouteException('Invalid admin path.');
            }

            if ($normalized === $base) {
                return '/';
            }

            if (str_starts_with($normalized, $base . '/')) {
                $relative = substr($normalized, strlen($base));
                return PatternMatcher::normalize((string) $relative);
            }

            throw new RouteNotFoundException('Admin route not found.');
        }

        if ($zone === 'install') {
            $base = '/install';
            if ($normalized === $base) {
                return '/';
            }

            if (str_starts_with($normalized, $base . '/')) {
                $relative = substr($normalized, strlen($base));
                return PatternMatcher::normalize((string) $relative);
            }

            return $normalized;
        }

        return $normalized;
    }

    private function isForbiddenCorePath(string $normalized): bool
    {
        foreach (['/core', '/modules', '/storage', '/config'] as $blocked) {
            if ($normalized === $blocked || str_starts_with($normalized, $blocked . '/')) {
                return true;
            }
        }

        return false;
    }
}
