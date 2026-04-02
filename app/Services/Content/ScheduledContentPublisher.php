<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Services\CatminEventBus;
use Modules\Articles\Models\Article;
use Modules\Logger\Services\SystemLogService;
use Modules\Pages\Models\Page;

final class ScheduledContentPublisher
{
    /**
     * @return array{pages:int, articles:int, total:int}
     */
    public function publishDue(): array
    {
        $pagesCount = 0;
        $articlesCount = 0;

        $now = now();

        $pages = Page::query()
            ->where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->get();

        foreach ($pages as $page) {
            $page->status = 'published';
            $page->save();
            $pagesCount++;

            CatminEventBus::dispatch(CatminEventBus::PAGE_PUBLISHED, [
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'published_at' => optional($page->published_at)->toIso8601String(),
                    'source' => 'scheduler',
                ],
            ]);

            $this->log('content.page.auto_published', 'Page publiee automatiquement', [
                'id' => $page->id,
                'slug' => $page->slug,
                'published_at' => optional($page->published_at)->toIso8601String(),
            ]);
        }

        $articles = Article::query()
            ->where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->get();

        foreach ($articles as $article) {
            $article->status = 'published';
            $article->save();
            $articlesCount++;

            CatminEventBus::dispatch(CatminEventBus::ARTICLE_PUBLISHED, [
                'article' => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'published_at' => optional($article->published_at)->toIso8601String(),
                    'source' => 'scheduler',
                ],
            ]);

            $this->log('content.article.auto_published', 'Article publie automatiquement', [
                'id' => $article->id,
                'slug' => $article->slug,
                'published_at' => optional($article->published_at)->toIso8601String(),
            ]);
        }

        return [
            'pages' => $pagesCount,
            'articles' => $articlesCount,
            'total' => $pagesCount + $articlesCount,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $event, string $message, array $context): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                $event,
                $message,
                $context,
                'info',
                'scheduler'
            );
        } catch (\Throwable) {
            // Publishing must remain resilient even if logging fails.
        }
    }
}
