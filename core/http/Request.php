<?php

declare(strict_types=1);

namespace Core\http;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query,
        private readonly array $post
    ) {}

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        return new self($method, $uri, $_GET, $_POST);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return (string) parse_url($this->uri, PHP_URL_PATH) ?: '/';
    }

    public function query(): array
    {
        return $this->query;
    }

    public function post(): array
    {
        return $this->post;
    }
}
