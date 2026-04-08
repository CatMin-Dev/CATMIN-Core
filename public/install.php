<?php

declare(strict_types=1);

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/install/');
$query = (string) parse_url($uri, PHP_URL_QUERY);
$path = (string) parse_url($uri, PHP_URL_PATH);
$path = '/' . trim($path, '/');
$path = $path === '//' ? '/' : $path;

if ($path === '/install') {
    $relativePath = '/';
} elseif (str_starts_with($path, '/install/')) {
    $relativePath = '/' . ltrim(substr($path, strlen('/install/')), '/');
} else {
    $relativePath = '/';
}

$_SERVER['REQUEST_URI'] = $relativePath . ($query !== '' ? '?' . $query : '');

require dirname(__DIR__) . '/install/index.php';
