<?php

namespace App\Services;

use App\Models\AdminUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

/**
 * LocaleService — resolves and applies the admin locale.
 *
 * Priority order:
 *   1. AdminUser::metadata['locale']  (persisted preference per-user)
 *   2. Session key 'catmin_admin_locale' (temporary override, e.g. guest lang switch)
 *   3. config('app.locale')           (application default)
 *
 * Supported locales are declared in SUPPORTED_LOCALES.
 * Any unrecognised value is silently replaced with the fallback.
 */
class LocaleService
{
    /** Supported admin locales. */
    public const SUPPORTED_LOCALES = ['fr', 'en'];

    /** Default locale when nothing else is resolved. */
    public const DEFAULT_LOCALE = 'fr';

    /** Session key used for temporary locale storage. */
    public const SESSION_KEY = 'catmin_admin_locale';

    /**
     * Resolve the locale from the current request context.
     *
     * @param AdminUser|null $adminUser  Authenticated admin user (may be null before auth)
     * @return string                    Validated locale code
     */
    public static function resolve(?AdminUser $adminUser = null): string
    {
        // 1. Try user metadata
        if ($adminUser !== null) {
            $meta = (array) ($adminUser->metadata ?? []);
            $userLocale = (string) ($meta['locale'] ?? '');
            if (self::isSupported($userLocale)) {
                return $userLocale;
            }
        }

        // 2. Try session
        $sessionLocale = (string) (session(self::SESSION_KEY, ''));
        if (self::isSupported($sessionLocale)) {
            return $sessionLocale;
        }

        // 3. App config / .env
        $configLocale = (string) config('app.locale', self::DEFAULT_LOCALE);
        if (self::isSupported($configLocale)) {
            return $configLocale;
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * Apply the given locale to Laravel + Carbon.
     */
    public static function apply(string $locale): void
    {
        $locale = self::isSupported($locale) ? $locale : self::DEFAULT_LOCALE;

        App::setLocale($locale);

        // Set Carbon locale (e.g. 'fr', 'en') for human-readable date diffs
        try {
            Carbon::setLocale($locale);
        } catch (\Throwable) {
            // Carbon locale not critical — ignore
        }

        // Store in session so subsequent AJAX requests preserve the locale
        try {
            session([self::SESSION_KEY => $locale]);
        } catch (\Throwable) {
            // Session may not be started yet during bootstrapping
        }
    }

    /**
     * Persist the locale preference on the admin user's metadata.
     */
    public static function persistForUser(AdminUser $adminUser, string $locale): void
    {
        $locale = self::isSupported($locale) ? $locale : self::DEFAULT_LOCALE;

        $meta = (array) ($adminUser->metadata ?? []);
        $meta['locale'] = $locale;
        $adminUser->metadata = $meta;
        $adminUser->save();
    }

    /**
     * Check if a locale string is supported.
     */
    public static function isSupported(string $locale): bool
    {
        return $locale !== '' && in_array($locale, self::SUPPORTED_LOCALES, true);
    }

    /**
     * Return the list of supported locales as [code => label] map.
     *
     * @return array<string, string>
     */
    public static function localeOptions(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
        ];
    }
}
