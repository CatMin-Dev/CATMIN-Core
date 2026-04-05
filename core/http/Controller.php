<?php

declare(strict_types=1);

namespace Core\http;

final class Controller
{
    protected function view(string $template, array $data = [], int $status = 200, array $headers = []): Response
    {
        return View::make($template, $data, CATMIN_AREA, $status, $headers);
    }

    protected function json(array $payload, int $status = 200): Response
    {
        return Response::json($payload, $status);
    }
}
