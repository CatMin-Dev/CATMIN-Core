<?php

declare(strict_types=1);

final class CoreUiTranslator
{
    /** @var array<string, array<string,string>> */
    private static array $maps = [];

    public function translate(string $text, ?string $locale = null): string
    {
        $locale = strtolower(trim((string) ($locale ?? (function_exists('catmin_locale') ? catmin_locale() : 'fr'))));
        if ($locale === 'fr') {
            return $text;
        }

        $map = $this->map($locale);
        if ($map === []) {
            return $text;
        }

        return strtr($text, $map);
    }

    /** @return array<string,string> */
    private function map(string $locale): array
    {
        if (isset(self::$maps[$locale])) {
            return self::$maps[$locale];
        }

        $map = [];

        $jsonPath = CATMIN_ROOT . '/lang/' . $locale . '.json';
        if (is_file($jsonPath)) {
            $fromJson = $this->loadJsonUiMap($jsonPath);
            if ($fromJson !== []) {
                $map = array_merge($map, $fromJson);
            }
        }

        $path = CATMIN_ROOT . '/lang/' . $locale . '/ui-map.php';
        if (is_file($path)) {
            $loaded = require $path;
            if (is_array($loaded)) {
                foreach ($loaded as $from => $to) {
                    if (!is_string($from) || !is_string($to) || $from === '') {
                        continue;
                    }
                    $map[$from] = $to;
                }
            }
        }

        uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        self::$maps[$locale] = $map;
        return $map;
    }

    /** @return array<string,string> */
    private function loadJsonUiMap(string $path): array
    {
        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $source = $decoded['ui_map'] ?? null;
        if (!is_array($source)) {
            return [];
        }

        $map = [];
        foreach ($source as $from => $to) {
            if (!is_string($from) || !is_string($to) || $from === '') {
                continue;
            }
            $map[$from] = $to;
        }

        return $map;
    }
}
