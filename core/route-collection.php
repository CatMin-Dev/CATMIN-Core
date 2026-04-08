<?php

declare(strict_types=1);

final class RouteCollection
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $routesByMethod = [];
    /** @var array<string, array<string, mixed>> */
    private array $namedRoutes = [];
    /** @var array<int, array<string, mixed>> */
    private array $groups = [];
    /** @var array<string, mixed>|null */
    private ?array $pendingAttributes = null;
    private mixed $fallback = null;

    public function get(string $path, mixed $handler, array $options = []): void
    {
        $this->match(['GET'], $path, $handler, $options);
    }

    public function post(string $path, mixed $handler, array $options = []): void
    {
        $this->match(['POST'], $path, $handler, $options);
    }

    /** @param array<int, string> $methods */
    public function match(array $methods, string $path, mixed $handler, array $options = []): void
    {
        $group = $this->currentGroup();
        $normalizedPath = PatternMatcher::normalize(($group['prefix'] ?? '') . '/' . trim($path, '/'));
        $zone = (string) ($options['zone'] ?? $group['zone'] ?? 'front');
        $name = $this->finalName((string) ($options['name'] ?? ''), (string) ($group['name_prefix'] ?? ''));

        $middlewares = [];
        foreach ([$group['middleware'] ?? [], $options['middleware'] ?? [], $this->pendingAttributes['middleware'] ?? []] as $source) {
            $middlewares = array_merge($middlewares, is_array($source) ? $source : [$source]);
        }

        $where = $options['where'] ?? $options['constraints'] ?? [];
        if (!is_array($where)) {
            $where = [];
        }

        [$regex, $params] = PatternMatcher::compile($normalizedPath, array_map('strval', $where));

        $definition = [
            'methods' => array_values(array_unique(array_map(static fn (string $m): string => strtoupper($m), $methods))),
            'path' => $normalizedPath,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
            'middleware' => array_values(array_filter($middlewares, static fn (mixed $m): bool => $m !== null && $m !== '')),
            'name' => $name !== '' ? $name : null,
            'module' => isset($options['module']) ? (string) $options['module'] : ($group['module'] ?? null),
            'zone' => $zone,
            'where' => array_map('strval', $where),
        ];

        foreach ($definition['methods'] as $method) {
            $this->assertNoPathConflict($method, $normalizedPath, $zone);
            $this->routesByMethod[$method][] = $definition;
        }

        if (is_string($definition['name']) && $definition['name'] !== '') {
            if (isset($this->namedRoutes[$definition['name']])) {
                throw new InvalidRouteDefinitionException('Duplicate route name: ' . $definition['name']);
            }
            $this->namedRoutes[$definition['name']] = $definition;
        }

        $this->pendingAttributes = null;
    }

    public function group(array $attributes, callable $callback): void
    {
        $group = [
            'prefix' => PatternMatcher::normalize((string) ($attributes['prefix'] ?? '')),
            'middleware' => $attributes['middleware'] ?? [],
            'zone' => (string) ($attributes['zone'] ?? 'front'),
            'name_prefix' => (string) ($attributes['name_prefix'] ?? ''),
            'module' => isset($attributes['module']) ? (string) $attributes['module'] : null,
        ];

        $parent = $this->currentGroup();
        $composed = [
            'prefix' => PatternMatcher::normalize(($parent['prefix'] ?? '') . '/' . trim((string) ($group['prefix'] ?? ''), '/')),
            'middleware' => array_merge(is_array($parent['middleware'] ?? null) ? $parent['middleware'] : [], is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']]),
            'zone' => $group['zone'] !== '' ? $group['zone'] : (string) ($parent['zone'] ?? 'front'),
            'name_prefix' => (string) ($parent['name_prefix'] ?? '') . $group['name_prefix'],
            'module' => $group['module'] ?? ($parent['module'] ?? null),
        ];

        $this->groups[] = $composed;
        $callback($this);
        array_pop($this->groups);
    }

    public function name(string $name): self
    {
        $this->pendingAttributes ??= [];
        $this->pendingAttributes['name'] = $name;

        return $this;
    }

    public function middleware(array|string $middlewares): self
    {
        $this->pendingAttributes ??= [];
        $existing = $this->pendingAttributes['middleware'] ?? [];
        if (!is_array($existing)) {
            $existing = [$existing];
        }
        $incoming = is_array($middlewares) ? $middlewares : [$middlewares];
        $this->pendingAttributes['middleware'] = array_merge($existing, $incoming);

        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->pendingAttributes ??= [];
        $this->pendingAttributes['prefix'] = $prefix;

        return $this;
    }

    public function fallback(mixed $handler): void
    {
        $this->fallback = $handler;
    }

    /** @return array<int, array<string,mixed>> */
    public function routesForMethod(string $method): array
    {
        return $this->routesByMethod[strtoupper($method)] ?? [];
    }

    /** @return array<string, array<string,mixed>> */
    public function namedRoutes(): array
    {
        return $this->namedRoutes;
    }

    public function fallbackHandler(): mixed
    {
        return $this->fallback;
    }

    /** @param array<int, array{method?:string,path?:string,handler?:mixed,middleware?:array|string,name?:string,zone?:string,module?:string,where?:array,constraints?:array}> $definitions */
    public function addFromDefinitions(array $definitions, string $defaultZone, ?string $module = null): void
    {
        foreach ($definitions as $definition) {
            $method = strtoupper((string) ($definition['method'] ?? 'GET'));
            $path = (string) ($definition['path'] ?? '/');
            $handler = $definition['handler'] ?? null;
            if ($handler === null) {
                continue;
            }

            $options = [
                'name' => $definition['name'] ?? null,
                'middleware' => $definition['middleware'] ?? [],
                'zone' => $definition['zone'] ?? $defaultZone,
                'module' => $definition['module'] ?? $module,
                'where' => $definition['where'] ?? $definition['constraints'] ?? [],
            ];

            $this->match([$method], $path, $handler, $options);
        }
    }

    private function assertNoPathConflict(string $method, string $path, string $zone): void
    {
        foreach ($this->routesByMethod[$method] ?? [] as $registered) {
            if ((string) ($registered['path'] ?? '') === $path && (string) ($registered['zone'] ?? 'front') === $zone) {
                throw new InvalidRouteDefinitionException('Route collision for [' . $method . '] ' . $path);
            }
        }
    }

    /** @return array<string, mixed> */
    private function currentGroup(): array
    {
        return $this->groups !== [] ? $this->groups[array_key_last($this->groups)] : [
            'prefix' => '',
            'middleware' => [],
            'zone' => 'front',
            'name_prefix' => '',
            'module' => null,
        ];
    }

    private function finalName(string $routeName, string $groupNamePrefix): string
    {
        $name = $routeName;
        if (isset($this->pendingAttributes['name']) && is_string($this->pendingAttributes['name']) && $this->pendingAttributes['name'] !== '') {
            $name = (string) $this->pendingAttributes['name'];
        }

        if (isset($this->pendingAttributes['prefix']) && is_string($this->pendingAttributes['prefix']) && $this->pendingAttributes['prefix'] !== '') {
            $groupNamePrefix .= trim((string) $this->pendingAttributes['prefix'], '/') . '.';
        }

        if ($name === '') {
            return '';
        }

        return $groupNamePrefix . ltrim($name, '.');
    }
}
