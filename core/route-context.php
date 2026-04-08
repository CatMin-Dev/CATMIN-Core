<?php

declare(strict_types=1);

final class RouteContext
{
    /** @param array<string, string> $parameters */
    /** @param array<int, mixed> $middlewares */
    /** @param array<string, string> $where */
    public function __construct(
        private readonly string $zone,
        private readonly string $path,
        private readonly ?string $name,
        private readonly array $parameters,
        private readonly array $middlewares,
        private readonly ?string $module,
        private readonly mixed $handler,
        private readonly string $method,
        private readonly array $where = []
    ) {}

    public function zone(): string
    {
        return $this->zone;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    /** @return array<string, string> */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /** @return array<int, mixed> */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function module(): ?string
    {
        return $this->module;
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    public function method(): string
    {
        return $this->method;
    }

    /** @return array<string, string> */
    public function where(): array
    {
        return $this->where;
    }
}
