<?php

declare(strict_types=1);

namespace Core\front;

final class FrontContextBuilder
{
    public function __construct(
        private readonly FrontAssetResolver $assets = new FrontAssetResolver(),
        private readonly FrontBridgeRegistry $bridges = new FrontBridgeRegistry(),
        private readonly FrontRegionRenderer $regions = new FrontRegionRenderer(),
        private readonly FrontRouteResolver $routes = new FrontRouteResolver(),
    ) {}

    /** @param array<int,array<string,mixed>> $modules */
    public function build(array $modules): array
    {
        $regionDeclarations = $this->regions->declarations($modules);

        return [
            'modules' => array_values(array_map(static function (array $module): array {
                $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
                return [
                    'slug' => strtolower(trim((string) ($manifest['slug'] ?? ''))),
                    'name' => (string) ($manifest['name'] ?? ''),
                    'version' => (string) ($manifest['version'] ?? ''),
                    'type' => strtolower(trim((string) ($manifest['type'] ?? ''))),
                ];
            }, $modules)),
            'assets' => $this->assets->resolve($modules),
            'bridges' => $this->bridges->register($modules),
            'regions' => $regionDeclarations,
            'front_routes' => $this->routes->resolve($modules),
        ];
    }
}
