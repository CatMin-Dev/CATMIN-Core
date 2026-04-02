<?php

namespace Modules\SEO\Services;

use App\Services\SettingService;
use Illuminate\Support\Facades\Cache;
use Modules\Articles\Models\Article;
use Modules\Pages\Models\Page;

class SitemapService
{
    private const CACHE_KEY = 'seo.sitemap.xml';

    public function getXml(): string
    {
        $ttl = $this->cacheTtlMinutes();

        return Cache::remember(self::CACHE_KEY, now()->addMinutes($ttl), function (): string {
            return $this->buildXml();
        });
    }

    public function refresh(): string
    {
        Cache::forget(self::CACHE_KEY);

        return $this->getXml();
    }

    private function buildXml(): string
    {
        $entries = [];

        $baseUrl = rtrim((string) SettingService::get('site.url', config('app.url')), '/');

        $entries[] = [
            'loc' => $baseUrl . '/',
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        $pages = Page::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        foreach ($pages as $page) {
            $entries[] = [
                'loc' => $baseUrl . '/page/' . ltrim((string) $page->slug, '/'),
                'lastmod' => optional($page->updated_at)->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $articles = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        foreach ($articles as $article) {
            $entries[] = [
                'loc' => $baseUrl . '/article/' . ltrim((string) $article->slug, '/'),
                'lastmod' => optional($article->updated_at)->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($entries as $entry) {
            $xml->startElement('url');
            $xml->writeElement('loc', $entry['loc']);
            $xml->writeElement('lastmod', $entry['lastmod']);
            $xml->writeElement('changefreq', $entry['changefreq']);
            $xml->writeElement('priority', $entry['priority']);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function cacheTtlMinutes(): int
    {
        $ttl = (int) SettingService::get('seo.sitemap_cache_minutes', 60);

        return max(5, min(1440, $ttl));
    }
}
