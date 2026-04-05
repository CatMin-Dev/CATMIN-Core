<?php

declare(strict_types=1);

namespace Core\http;

final class Request
{
    private array $attributes = [];

    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query,
        private readonly array $post,
        private readonly array $server
    ) {}

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        return new self($method, $uri, $_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        $path = (string) parse_url($this->uri, PHP_URL_PATH);
        $normalized = '/' . trim($path, '/');
        return $normalized === '//' ? '/' : $normalized;
    }

    public function query(): array
    {
        return $this->query;
    }

    public function post(): array
    {
        return $this->post;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        return $this->query[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$normalized] ?? null;

        return is_string($value) ? $value : $default;
    }

    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
