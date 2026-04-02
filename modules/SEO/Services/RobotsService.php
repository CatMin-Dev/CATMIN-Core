<?php

namespace Modules\SEO\Services;

use App\Services\SettingService;
use Illuminate\Validation\ValidationException;

class RobotsService
{
    private const DEFAULT_ROBOTS = "User-agent: *\nAllow: /\n\nSitemap: /sitemap.xml\n";

    public function getContent(): string
    {
        $raw = (string) SettingService::get('seo.robots_txt', '');

        if (trim($raw) === '') {
            return self::DEFAULT_ROBOTS;
        }

        return $this->normalize($raw);
    }

    public function validate(string $content): void
    {
        $normalized = $this->normalize($content);

        if (mb_strlen($normalized) > 20000) {
            throw ValidationException::withMessages([
                'seo_robots_txt' => 'Le robots.txt depasse la taille maximale (20000 caracteres).',
            ]);
        }

        $lines = preg_split('/\r\n|\r|\n/', $normalized) ?: [];
        $allowed = [
            'user-agent',
            'allow',
            'disallow',
            'sitemap',
            'crawl-delay',
            'host',
            '#',
        ];

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode(':', $trimmed, 2);
            if (count($parts) < 2) {
                throw ValidationException::withMessages([
                    'seo_robots_txt' => 'Directive invalide a la ligne ' . ($index + 1) . '.',
                ]);
            }

            $directive = strtolower(trim($parts[0]));
            if (!in_array($directive, $allowed, true)) {
                throw ValidationException::withMessages([
                    'seo_robots_txt' => 'Directive robots non autorisee a la ligne ' . ($index + 1) . ': ' . $directive,
                ]);
            }
        }
    }

    private function normalize(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", trim($content));

        return $content . "\n";
    }
}
