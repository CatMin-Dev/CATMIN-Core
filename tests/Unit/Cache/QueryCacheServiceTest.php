<?php

namespace Tests\Unit\Cache;

use Illuminate\Support\Facades\Cache;
use Modules\Cache\Services\QueryCacheService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueryCacheServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        QueryCacheService::flushAll();
    }

    #[Test]
    public function it_tracks_hits_misses_and_module_registry(): void
    {
        $resolverCalls = 0;

        $first = QueryCacheService::remember('pages', 'listing.1', 120, function () use (&$resolverCalls): array {
            $resolverCalls++;
            return ['value' => 'cached-result'];
        });

        $second = QueryCacheService::remember('pages', 'listing.1', 120, function () use (&$resolverCalls): array {
            $resolverCalls++;
            return ['value' => 'fresh-result'];
        });

        $stats = QueryCacheService::stats();

        $this->assertSame(1, $resolverCalls);
        $this->assertSame(['value' => 'cached-result'], $first);
        $this->assertSame(['value' => 'cached-result'], $second);
        $this->assertSame(1, $stats['hits']);
        $this->assertSame(1, $stats['misses']);
        $this->assertSame(2, $stats['requests']);
        $this->assertSame(50.0, $stats['hit_ratio']);
        $this->assertSame(1, $stats['modules']);
        $this->assertSame(1, $stats['keys']);
    }

    #[Test]
    public function it_invalidates_one_module_without_touching_others(): void
    {
        QueryCacheService::remember('pages', 'listing.1', 120, fn (): string => 'pages-v1');
        QueryCacheService::remember('users', 'listing.1', 120, fn (): string => 'users-v1');

        $deleted = QueryCacheService::invalidateModule('pages');

        $this->assertSame(1, $deleted);

        $pageResolverCalls = 0;
        $userResolverCalls = 0;

        $pageValue = QueryCacheService::remember('pages', 'listing.1', 120, function () use (&$pageResolverCalls): string {
            $pageResolverCalls++;
            return 'pages-v2';
        });

        $userValue = QueryCacheService::remember('users', 'listing.1', 120, function () use (&$userResolverCalls): string {
            $userResolverCalls++;
            return 'users-v2';
        });

        $stats = QueryCacheService::stats();

        $this->assertSame('pages-v2', $pageValue);
        $this->assertSame('users-v1', $userValue);
        $this->assertSame(1, $pageResolverCalls);
        $this->assertSame(0, $userResolverCalls);
        $this->assertGreaterThanOrEqual(1, $stats['invalidations']);
    }

    #[Test]
    public function it_flushes_all_registered_modules(): void
    {
        QueryCacheService::remember('pages', 'listing.1', 120, fn (): string => 'pages-v1');
        QueryCacheService::remember('users', 'listing.1', 120, fn (): string => 'users-v1');

        $deleted = QueryCacheService::flushAll();

        $this->assertSame(2, $deleted);

        $pageResolverCalls = 0;
        $userResolverCalls = 0;

        $pageValue = QueryCacheService::remember('pages', 'listing.1', 120, function () use (&$pageResolverCalls): string {
            $pageResolverCalls++;
            return 'pages-v2';
        });

        $userValue = QueryCacheService::remember('users', 'listing.1', 120, function () use (&$userResolverCalls): string {
            $userResolverCalls++;
            return 'users-v2';
        });

        $this->assertSame('pages-v2', $pageValue);
        $this->assertSame('users-v2', $userValue);
        $this->assertSame(1, $pageResolverCalls);
        $this->assertSame(1, $userResolverCalls);
    }
}
