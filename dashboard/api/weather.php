<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function getClientIp(): string
{
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwarded !== '') {
        $parts = explode(',', $forwarded);
        $candidate = trim($parts[0]);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function fetchJson(string $url): ?array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'CATMIN Weather Proxy/1.0',
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response) || $status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 12,
            'ignore_errors' => true,
            'header' => "User-Agent: CATMIN Weather Proxy/1.0\r\n",
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if (!is_string($response)) {
        return null;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function toFloatOrNull(mixed $value): ?float
{
    if (is_numeric($value)) {
        return (float)$value;
    }

    return null;
}

function detectLocationByIp(string $ip): array
{
    $default = [
        'lat' => 46.2276,
        'lon' => 2.2137,
        'label' => 'France',
        'source' => 'fallback',
    ];

    $endpoint = 'https://ipapi.co/' . rawurlencode($ip) . '/json/';
    $ipData = fetchJson($endpoint);

    if (!is_array($ipData)) {
        return $default;
    }

    $lat = toFloatOrNull($ipData['latitude'] ?? null);
    $lon = toFloatOrNull($ipData['longitude'] ?? null);

    if ($lat === null || $lon === null) {
        return $default;
    }

    $city = trim((string)($ipData['city'] ?? ''));
    $country = trim((string)($ipData['country_name'] ?? ''));
    $labelParts = array_values(array_filter([$city, $country], static fn($part) => $part !== ''));

    return [
        'lat' => $lat,
        'lon' => $lon,
        'label' => $labelParts ? implode(', ', $labelParts) : 'Localisation approximative (IP)',
        'source' => 'ip',
    ];
}

$lat = toFloatOrNull($_GET['lat'] ?? null);
$lon = toFloatOrNull($_GET['lon'] ?? null);
$locationLabel = 'Votre localisation';
$source = 'geolocation';

if ($lat === null || $lon === null) {
    $detected = detectLocationByIp(getClientIp());
    $lat = $detected['lat'];
    $lon = $detected['lon'];
    $locationLabel = $detected['label'];
    $source = $detected['source'];
}

$forecastUrl = sprintf(
    'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&timezone=auto&current=temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,weather_code&daily=temperature_2m_max,temperature_2m_min,weather_code',
    rawurlencode((string)$lat),
    rawurlencode((string)$lon)
);

$weatherPayload = fetchJson($forecastUrl);
if (!is_array($weatherPayload)) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Weather service unavailable',
        'locationLabel' => $locationLabel,
        'source' => $source,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($source === 'geolocation') {
    $reverseUrl = sprintf(
        'https://geocoding-api.open-meteo.com/v1/reverse?latitude=%s&longitude=%s&language=fr&count=1',
        rawurlencode((string)$lat),
        rawurlencode((string)$lon)
    );
    $reversePayload = fetchJson($reverseUrl);
    $first = $reversePayload['results'][0] ?? null;
    if (is_array($first)) {
        $city = trim((string)($first['city'] ?? $first['name'] ?? ''));
        $country = trim((string)($first['country'] ?? ''));
        $parts = array_values(array_filter([$city, $country], static fn($part) => $part !== ''));
        if ($parts) {
            $locationLabel = implode(', ', $parts);
        }
    }
}

echo json_encode([
    'locationLabel' => $locationLabel,
    'source' => $source,
    'weather' => $weatherPayload,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
