<?php

declare(strict_types=1);

namespace Core\security;

use Core\http\Response;

final class HeaderManager
{
    public function apply(Response $response, string $csp, bool $noindex): Response
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => $csp,
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        if ($noindex) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
