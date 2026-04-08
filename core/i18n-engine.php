<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/i18n-loader.php';
require_once CATMIN_CORE . '/i18n-fallback.php';
require_once CATMIN_CORE . '/i18n-user-locale.php';

final class CoreI18nEngine
{
    /** @var array<string, array<string,string>> */
    private static array $cache = [];

    public function __construct(
        private readonly CoreI18nLoader $loader = new CoreI18nLoader(),
        private readonly CoreI18nFallback $fallback = new CoreI18nFallback(),
        private readonly CoreI18nUserLocale $localeStore = new CoreI18nUserLocale()
    ) {}

    public function locale(): string
    {
        return $this->fallback->normalize($this->localeStore->resolve());
    }

    public function setLocale(string $locale): string
    {
        $normalized = $this->fallback->normalize($locale);
        $this->localeStore->persist($normalized);
        return $normalized;
    }

    public function t(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $this->fallback->normalize($locale ?? $this->locale());
        $master = $this->fallback->masterLocale();

        $dict = $this->dictionary($locale);
        $value = (string) ($dict[$key] ?? '');
        if ($value === '') {
            $value = (string) ($this->dictionary($master)[$key] ?? $key);
        }

        if ($replace !== []) {
            foreach ($replace as $token => $tokenValue) {
                $value = str_replace('{' . $token . '}', (string) $tokenValue, $value);
            }
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    private function dictionary(string $locale): array
    {
        if (!isset(self::$cache[$locale])) {
            $loaded = $this->loader->loadLocale($locale);
            self::$cache[$locale] = array_map('strval', $loaded);
        }

        return self::$cache[$locale];
    }
}

