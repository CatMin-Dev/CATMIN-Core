<?php

declare(strict_types=1);

final class UrlGenerator
{
    public function __construct(private readonly RouteCollection $collection, private readonly string $adminPath) {}

    /** @param array<string, scalar> $params */
    public function route(string $name, array $params = []): string
    {
        $routes = $this->collection->namedRoutes();
        if (!isset($routes[$name])) {
            throw new InvalidArgumentException('Unknown route name: ' . $name);
        }

        $path = (string) ($routes[$name]['path'] ?? '/');
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }

        if (preg_match('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', $path) === 1) {
            throw new InvalidArgumentException('Missing route parameters for: ' . $name);
        }

        $zone = (string) ($routes[$name]['zone'] ?? 'front');
        if ($zone === 'admin') {
            return PatternMatcher::normalize('/' . trim($this->adminPath, '/') . '/' . ltrim($path, '/'));
        }

        if ($zone === 'install') {
            return PatternMatcher::normalize('/install/' . ltrim($path, '/'));
        }

        return PatternMatcher::normalize($path);
    }

    public function admin(string $path = ''): string
    {
        return PatternMatcher::normalize('/' . trim($this->adminPath, '/') . '/' . ltrim($path, '/'));
    }

    public function asset(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function base(): string
    {
        $https = ((string) ($_SERVER['HTTPS'] ?? '') === 'on') || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host;
    }
}
