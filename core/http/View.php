<?php

declare(strict_types=1);

namespace Core\http;

use Core\support\PathManager;
use RuntimeException;

final class View
{
    public static function make(string $template, array $data = [], string $area = 'front', int $status = 200, array $headers = []): Response
    {
        $pathManager = new PathManager();
        $viewPath = $pathManager->viewPath($area, $template);

        if (!is_file($viewPath)) {
            throw new RuntimeException('View not found: ' . $viewPath);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = (string) ob_get_clean();

        return Response::html($content, $status, $headers);
    }
}
