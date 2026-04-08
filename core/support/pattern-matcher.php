<?php

declare(strict_types=1);

final class PatternMatcher
{
    /** @var array<string, string> */
    private const DEFAULTS = [
        'id' => '[0-9]+',
        'slug' => '[a-zA-Z0-9-]+',
        'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}',
    ];

    /**
     * @param array<string, string> $constraints
     * @return array{0:string,1:array<int,string>}
     */
    public static function compile(string $path, array $constraints = []): array
    {
        $parameterNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m) use ($constraints, &$parameterNames): string {
            $name = (string) $m[1];
            $parameterNames[] = $name;

            $rawPattern = $constraints[$name] ?? self::DEFAULTS[$name] ?? '[^/]+';
            if (!preg_match('/^[a-zA-Z0-9\\[\\]\\(\\)\\+\\*\\?\\-\\\\\|\^\$\.\:]+$/', $rawPattern)) {
                throw new InvalidRouteDefinitionException('Unsafe route constraint: ' . $name);
            }

            return '(?P<' . $name . '>' . $rawPattern . ')';
        }, self::normalize($path));

        if (!is_string($regex)) {
            throw new InvalidRouteDefinitionException('Invalid route pattern.');
        }

        return ['#^' . $regex . '$#', $parameterNames];
    }

    public static function normalize(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path) ?? '/';
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }
}
