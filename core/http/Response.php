<?php

declare(strict_types=1);

namespace Core\http;

final class Response
{
    public function __construct(
        private readonly string $content,
        private readonly int $status = 200,
        private readonly array $headers = []
    ) {}

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, $headers);
    }

    public static function json(array $payload, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        return new self((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, $headers);
    }

    public static function text(string $content, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        return new self($content, $status, $headers);
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;
        return new self($this->content, $this->status, $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }
}
