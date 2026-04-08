<?php

declare(strict_types=1);

final class MiddlewarePipeline
{
    /** @param callable(string):callable|null $resolver */
    public function __construct(private $resolver) {}

    /** @param array<int, mixed> $middlewares */
    public function handle(Core\http\Request $request, array $middlewares, callable $destination): Core\http\Response
    {
        $next = array_reduce(
            array_reverse($middlewares),
            function (callable $next, mixed $middleware): callable {
                return function (Core\http\Request $request) use ($next, $middleware): Core\http\Response {
                    $callable = $this->resolve($middleware);
                    $result = $callable($request, $next);

                    return $this->normalizeResponse($result);
                };
            },
            function (Core\http\Request $request) use ($destination): Core\http\Response {
                $result = $destination($request);

                return $this->normalizeResponse($result);
            }
        );

        return $next($request);
    }

    private function resolve(mixed $middleware): callable
    {
        if (is_string($middleware)) {
            $resolved = ($this->resolver)($middleware);
            if (is_callable($resolved)) {
                return $resolved;
            }

            throw new InvalidRouteDefinitionException('Unknown middleware alias: ' . $middleware);
        }

        if (is_callable($middleware)) {
            return $middleware;
        }

        throw new InvalidRouteDefinitionException('Invalid middleware type.');
    }

    private function normalizeResponse(mixed $result): Core\http\Response
    {
        if ($result instanceof Core\http\Response) {
            return $result;
        }

        if ($result instanceof Response) {
            return $result->toCoreResponse();
        }

        if (is_array($result)) {
            return Core\http\Response::json($result);
        }

        return Core\http\Response::html((string) $result);
    }
}
