<?php

declare(strict_types=1);

final class RouteDispatcher
{
    public function __construct(
        private readonly RouteGuard $guard,
        private readonly Core\security\SecurityManager $security,
        private readonly string $zone,
        private readonly string $adminPath
    ) {}

    public function dispatch(RouteContext $context, Core\http\Request $request): Core\http\Response
    {
        $this->guard->validate($context, $this->zone);

        $middlewares = array_merge($this->globalMiddlewares(), $this->zoneMiddlewares($this->zone), $context->middlewares());
        $pipeline = new MiddlewarePipeline(fn (string $name): ?callable => $this->resolveMiddlewareAlias($name));

        return $pipeline->handle($request, $middlewares, function (Core\http\Request $request) use ($context): Core\http\Response {
            $result = $this->invokeHandler($context->handler(), $request, $context);

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
        });
    }

    /** @return array<int, string> */
    private function globalMiddlewares(): array
    {
        return ['ip.whitelist', 'maintenance.check', 'security.headers'];
    }

    /** @return array<int, string> */
    private function zoneMiddlewares(string $zone): array
    {
        if ($zone === 'front') {
            return ['noindex.front'];
        }

        if ($zone === 'install') {
            return ['install.available'];
        }

        return [];
    }

    private function resolveMiddlewareAlias(string $name): ?callable
    {
        return match ($name) {
            'security.headers' => $this->security->securityHeadersMiddleware(),
            'auth.admin' => $this->security->adminAuthRequiredMiddleware(),
            'admin.guest' => function (Core\http\Request $request, callable $next): Core\http\Response {
                $auth = new Admin\controllers\AuthController();
                if ($auth->requiresAuth()) {
                    return Core\http\Response::html('', 302, ['Location' => $auth->adminBasePath()]);
                }

                $result = $next($request);
                return $result instanceof Core\http\Response ? $result : Core\http\Response::html((string) $result);
            },
            'install.available' => $this->security->installAvailabilityMiddleware(),
            'csrf.verify' => $this->security->csrfCheckMiddleware(),
            'maintenance.check' => $this->security->maintenanceModeMiddleware(),
            'reauth.required' => $this->security->recentPasswordRequiredMiddleware(),
            'ip.whitelist' => function (Core\http\Request $request, callable $next): Core\http\Response {
                $denied = $this->security->enforceIpWhitelist();
                if ($denied instanceof Core\http\Response) {
                    return $denied;
                }

                $result = $next($request);
                return $result instanceof Core\http\Response ? $result : Core\http\Response::html((string) $result);
            },
            'noindex.front' => $this->security->noindexFrontMiddleware(),
            'session.admin' => static fn (Core\http\Request $request, callable $next): Core\http\Response => $next($request),
            default => null,
        };
    }

    private function invokeHandler(mixed $handler, Core\http\Request $request, RouteContext $context): mixed
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            $class = $handler[0];
            $method = $handler[1];

            if (!class_exists($class)) {
                throw new InvalidRouteDefinitionException('Controller class not found: ' . $class);
            }

            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new InvalidRouteDefinitionException('Controller method not found: ' . $class . '@' . $method);
            }

            return $this->invokeCallable([$controller, $method], $request, $context->parameters());
        }

        if (is_callable($handler)) {
            return $this->invokeCallable($handler, $request, $context->parameters());
        }

        throw new InvalidRouteDefinitionException('Invalid route handler target.');
    }

    /** @param array<string, string> $routeParams */
    private function invokeCallable(callable $callable, Core\http\Request $request, array $routeParams): mixed
    {
        $ref = is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction(Closure::fromCallable($callable));

        $args = [];
        foreach ($ref->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $type->getName() === Core\http\Request::class) {
                $args[] = $request;
                continue;
            }

            $name = $parameter->getName();
            if (array_key_exists($name, $routeParams)) {
                $args[] = $routeParams[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            $args[] = null;
        }

        return $callable(...$args);
    }
}
