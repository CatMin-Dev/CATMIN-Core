<?php

declare(strict_types=1);

final class CoreI18nLoader
{
    public function loadLocale(string $locale): array
    {
        $locale = strtolower(trim($locale));
        if ($locale === '') {
            $locale = 'fr';
        }

        $dictionary = [];
        $jsonLangPath = CATMIN_ROOT . '/lang/' . $locale . '.json';
        if (is_file($jsonLangPath)) {
            $loadedJson = $this->loadJsonDictionary($jsonLangPath);
            if ($loadedJson !== []) {
                $dictionary = array_merge($dictionary, $loadedJson);
            }
        }

        $coreLangPath = CATMIN_ROOT . '/lang/' . $locale . '/core.php';
        if (is_file($coreLangPath)) {
            $loaded = require $coreLangPath;
            if (is_array($loaded)) {
                $dictionary = array_merge($dictionary, $loaded);
            }
        }

        $moduleLangFiles = array_merge(
            glob(CATMIN_MODULES . '/*/lang/' . $locale . '.php') ?: [],
            glob(CATMIN_MODULES . '/*/*/lang/' . $locale . '.php') ?: []
        );

        foreach ($moduleLangFiles as $moduleLangFile) {
            $loaded = require $moduleLangFile;
            if (!is_array($loaded)) {
                continue;
            }

            $moduleDir = dirname(dirname((string) $moduleLangFile));
            $slug = strtolower(trim((string) basename($moduleDir)));
            if ($slug === '' || $slug === 'lang') {
                continue;
            }

            foreach ($loaded as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }
                $finalKey = str_starts_with($key, 'module.')
                    ? $key
                    : ('module.' . $slug . '.' . $key);
                $dictionary[$finalKey] = (string) $value;
            }
        }

        return $dictionary;
    }

    /** @return array<string, string> */
    private function loadJsonDictionary(string $path): array
    {
        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $source = $decoded;
        if (isset($decoded['core']) && is_array($decoded['core'])) {
            $source = $decoded['core'];
        }

        $dictionary = [];
        foreach ($source as $key => $value) {
            if (!is_string($key) || !is_string($value) || $key === '') {
                continue;
            }
            $dictionary[$key] = $value;
        }

        return $dictionary;
    }
}
