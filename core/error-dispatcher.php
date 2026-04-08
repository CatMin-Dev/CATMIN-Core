<?php

declare(strict_types=1);

use Core\http\Response;

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
            return $this->failsafeHtml($context);
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
        $title = htmlspecialchars((string) ($context['title'] ?? 'Erreur'), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars((string) ($context['message'] ?? 'Une erreur est survenue.'), ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>CATMIN Error</title></head><body style="font-family:system-ui,Segoe UI,Arial,sans-serif;background:#fafaf9;color:#1c1917;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;"><main style="max-width:640px;padding:24px;border:1px solid #e7e5e4;border-radius:12px;background:#fff;"><h1 style="margin:0 0 8px 0;font-size:1.5rem;">' . $status . ' - ' . $title . '</h1><p style="margin:0 0 16px 0;">' . $message . '</p><a href="/" style="color:#c2234d;text-decoration:none;">Retour accueil</a></main></body></html>';
    }
}

