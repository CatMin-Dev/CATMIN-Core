<?php

declare(strict_types=1);

final class Response
{
    private int $status = 200;
    /** @var array<string, string> */
    private array $headers = [];
    private string $content = '';

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function html(string $content): self
    {
        $this->header('Content-Type', 'text/html; charset=UTF-8');
        $this->content = $content;

        return $this;
    }

    public function json(array $payload, int $status = 200): self
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->setStatus($status)->header('Content-Type', 'application/json; charset=UTF-8');
        $this->content = is_string($encoded) ? $encoded : '{}';

        return $this;
    }

    public function redirect(string $url, int $status = 302): self
    {
        return $this->setStatus($status)->header('Location', $url);
    }

    public function send(): void
    {
        $this->toCoreResponse()->send();
    }

    public function toCoreResponse(): Core\http\Response
    {
        return Core\http\Response::html($this->content, $this->status, $this->headers);
    }

    public static function fromCoreResponse(Core\http\Response $response): self
    {
        $instance = new self();
        $instance->status = $response->status();
        $instance->headers = $response->headers();
        $instance->content = $response->content();

        return $instance;
    }
}
