<?php

declare(strict_types=1);

final class RouteGuard
{
    /** @var array<int, string> */
    private array $allowedHandlerNamespaces = ['Admin\\', 'Front\\', 'Install\\', 'Core\\'];

    public function validate(RouteContext $context, string $expectedZone): void
    {
        if ($context->zone() !== $expectedZone) {
            throw new ForbiddenRouteException('Zone mismatch.');
        }

        $this->validateHandler($context->handler());
    }

    public function validateHandler(mixed $handler): void
    {
        if ($handler instanceof Closure) {
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = array_values($handler);
            if (!is_string($class) || !is_string($method) || $class === '' || $method === '') {
                throw new InvalidRouteDefinitionException('Invalid handler array.');
            }

            if (str_contains($class, '..') || str_contains($method, '..')) {
                throw new ForbiddenRouteException('Unsafe handler target.');
            }

            $allowed = false;
            foreach ($this->allowedHandlerNamespaces as $prefix) {
                if (str_starts_with($class, $prefix)) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                throw new ForbiddenRouteException('Handler namespace forbidden.');
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $method)) {
                throw new InvalidRouteDefinitionException('Invalid handler method.');
            }

            return;
        }

        if (is_callable($handler)) {
            return;
        }

        throw new InvalidRouteDefinitionException('Unsupported route handler.');
    }
}
