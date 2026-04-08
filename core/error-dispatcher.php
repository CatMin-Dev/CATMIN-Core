<?php

declare(strict_types=1);

use Core\http\Response;
use Core\failsafe\SafeViewRenderer;

final class CoreErrorDispatcher
{
    /** @var array<int, string> */
    private const CODE_TO_TEMPLATE = [
        403 => '403',
        404 => '404',
        405 => '405',
        419 => '419',
        429 => '429',
        500 => '500',
    ];

    public function response(int $status, array $context = [], array $headers = []): Response
    {
        $template = self::CODE_TO_TEMPLATE[$status] ?? '500';
        return $this->named($template, $status, $context, $headers);
    }

    public function maintenance(array $context = [], array $headers = []): Response
    {
        return $this->named('maintenance', 503, $context, $headers);
    }

    public function installLocked(array $context = [], array $headers = []): Response
    {
        return $this->named('install_locked', 423, $context, $headers);
    }

    public function adminAccessDenied(array $context = [], array $headers = []): Response
    {
        return $this->named('access_denied_admin', 403, $context, $headers);
    }

    public function outputForFatal(int $status, array $context = [], array $headers = []): void
    {
        $response = $this->response($status, $context, $headers);
        http_response_code($response->status());
        foreach ($response->headers() as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $response->content();
    }

    private function named(string $name, int $status, array $context = [], array $headers = []): Response
    {
        $context = array_merge([
            'status' => $status,
            'title' => 'Erreur',
            'message' => 'Une erreur est survenue.',
            'admin_login' => '/' . trim((string) config('security.admin_path', 'admin'), '/') . '/login',
            'home_url' => '/',
        ], $context);

        $content = $this->renderTemplate($name, $context);
        $headers = array_merge([
            'Content-Type' => 'text/html; charset=UTF-8',
        ], $headers);

        return Response::html($content, $status, $headers);
    }

    private function renderTemplate(string $name, array $context): string
    {
        $file = CATMIN_CORE . '/views/errors/' . $name . '.php';
        if (!is_file($file)) {
            $file = CATMIN_CORE . '/views/errors/generic-failsafe.php';
            if (!is_file($file)) {
                return $this->failsafeHtml($context);
            }
        }

        try {
            extract($context, EXTR_SKIP);
            ob_start();
            require $file;
            return (string) ob_get_clean();
        } catch (Throwable) {
            return $this->failsafeHtml($context);
        }
    }

    private function failsafeHtml(array $context): string
    {
        $status = (int) ($context['status'] ?? 500);
        $title = (string) ($context['title'] ?? 'Erreur');
        $message = (string) ($context['message'] ?? 'Une erreur est survenue.');
        return (new SafeViewRenderer())->renderMinimal($status, $title, $message);
    }
}
