<?php

declare(strict_types=1);

use Core\http\Response;

final class CoreNoindexManager
{
    public function apply(Response $response): Response
    {
        return $response->withHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}

