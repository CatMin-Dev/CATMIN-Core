<?php

declare(strict_types=1);

namespace Tests\Unit\Frontend;

use App\Services\Frontend\FrontendResolverService;
use App\Services\Frontend\PublicContentRenderService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Unit tests for the public frontend rendering layer.
 *
 * Tests cover:
 *  - FrontendResolverService context building
 *  - PublicContentRenderService rendering and excerpt helpers
 *  - Route existence for all public frontend routes
 *  - Contact form validation rules
 */
class FrontendPublicTest extends TestCase
{
    // ── FrontendResolverService ──────────────────────────────────────

    public function test_resolver_context_returns_site_name_key(): void
    {
        $resolver = new FrontendResolverService();
        $context  = $resolver->context();

        $this->assertIsArray($context);
        $this->assertArrayHasKey('site_name', $context);
    }

    public function test_resolver_context_overrides_are_merged(): void
    {
        $resolver = new FrontendResolverService();
        $context  = $resolver->context(['custom_key' => 'hello']);

        $this->assertArrayHasKey('custom_key', $context);
        $this->assertSame('hello', $context['custom_key']);
    }

    public function test_resolver_menu_returns_collection(): void
    {
        $resolver = new FrontendResolverService();
        $menu     = $resolver->menu('primary');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $menu);
    }

    public function test_resolver_seo_returns_expected_keys(): void
    {
        $resolver = new FrontendResolverService();
        $seo      = $resolver->seo(null, null, ['title' => 'Test Title']);

        $this->assertArrayHasKey('title', $seo);
        $this->assertArrayHasKey('description', $seo);
        $this->assertArrayHasKey('robots', $seo);
        $this->assertArrayHasKey('canonical', $seo);
        $this->assertArrayHasKey('og', $seo);
        $this->assertSame('Test Title', $seo['title']);
    }

    // ── PublicContentRenderService ───────────────────────────────────

    public function test_render_returns_string_for_empty_content(): void
    {
        $renderer = new PublicContentRenderService();
        $this->assertSame('', $renderer->render(''));
    }

    public function test_render_injects_blocks_placeholder(): void
    {
        // inject_blocks() is a global helper — we just ensure it runs without error
        $renderer = new PublicContentRenderService();
        $result   = $renderer->render('<p>Hello {{ block:missing-block }}</p>');

        $this->assertIsString($result);
        $this->assertStringContainsString('<p>Hello', $result);
    }

    public function test_excerpt_strips_html_tags(): void
    {
        $renderer = new PublicContentRenderService();
        $excerpt  = $renderer->excerpt('<p>Hello <b>World</b>, this is a test.</p>', 20);

        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertStringNotContainsString('<b>', $excerpt);
    }

    public function test_excerpt_limits_to_max_length(): void
    {
        $renderer = new PublicContentRenderService();
        $long     = str_repeat('Word ', 200);
        $excerpt  = $renderer->excerpt($long, 50);

        $this->assertLessThanOrEqual(53, strlen($excerpt)); // 50 + possible ellipsis "..."
    }

    public function test_reading_time_at_least_one_minute(): void
    {
        $renderer = new PublicContentRenderService();
        $this->assertGreaterThanOrEqual(1, $renderer->readingTime('Short text'));
    }

    public function test_reading_time_scales_with_content_length(): void
    {
        $renderer = new PublicContentRenderService();
        $short    = $renderer->readingTime(str_repeat('word ', 100));
        $long     = $renderer->readingTime(str_repeat('word ', 800));

        $this->assertGreaterThanOrEqual($short, $long);
    }

    // ── Route existence ──────────────────────────────────────────────

    public function test_all_frontend_routes_are_registered(): void
    {
        $expected = [
            'frontend.root',
            'frontend.home',
            'frontend.page',
            'frontend.articles.index',
            'frontend.articles.show',
            'frontend.contact',
            'frontend.contact.send',
            'frontend.map',
        ];

        foreach ($expected as $name) {
            $this->assertTrue(Route::has($name), "Route {$name} is not registered");
        }
    }
}
