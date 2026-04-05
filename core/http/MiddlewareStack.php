<?php

declare(strict_types=1);

namespace Core\http;

use InvalidArgumentException;

final class MiddlewareStack
{
    /**
     * @param array<int, callable|string|object> $middlewares
     */
    public function handle(Request $request, array $middlewares, callable $destination): mixed
    {
        $runner = array_reduce(
            array_reverse($middlewares),
            function (callable $next, callable|string|object $middleware): callable {
                return function (Request $request) use ($middleware, $next): mixed {
                    return $this->invoke($middleware, $request, $next);
                };
            },
            $destination
        );

        return $runner($request);
    }

    private function invoke(callable|string|object $middleware, Request $request, callable $next): mixed
    {
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new InvalidArgumentException('Middleware class not found: ' . $middleware);
            }
            $middleware = new $middleware();
        }

        if (is_callable($middleware)) {
            return $middleware($request, $next);
        }

        if (is_object($middleware) && method_exists($middleware, 'handle')) {
            return $middleware->handle($request, $next);
        }

        throw new InvalidArgumentException('Invalid middleware definition.');
    }
}
