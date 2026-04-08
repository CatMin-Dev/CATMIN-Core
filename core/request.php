<?php

declare(strict_types=1);

final class Request
{
    private function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $files,
        private readonly array $headers,
        private readonly array $cookies,
        private readonly string $ip,
        private readonly string $userAgent,
        private readonly string $basePath
    ) {}

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = self::normalizePath((string) parse_url($uri, PHP_URL_PATH));

        return new self(
            $method,
            $uri,
            $path,
            $_GET,
            $_POST,
            $_FILES,
            self::captureHeaders(),
            $_COOKIE,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ''
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return $this->path;
    }

    /** @return array<int, string> */
    public function segments(): array
    {
        if ($this->path === '/') {
            return [];
        }

        return array_values(array_filter(explode('/', trim($this->path, '/')), static fn (string $s): bool => $s !== ''));
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post + $this->query;
        }

        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        return $this->query[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtolower($key);

        return $this->headers[$normalized] ?? $default;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function toCoreRequest(): Core\http\Request
    {
        return Core\http\Request::capture();
    }

    private static function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path) ?? '/';
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }

    /** @return array<string, string> */
    private static function captureHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
                continue;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }

        return $headers;
    }
}
