<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Pages\Models\Page;
use Tests\TestCase;

class ApiV2ExternalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available on this environment.');
        }

        parent::setUp();

        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('content')->nullable();
                $table->string('status', 32)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_version_endpoint_uses_normalized_response_shape(): void
    {
        $response = $this->getJson('/api/v2/version');

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonStructure([
                'success',
                'data' => ['catmin_version', 'laravel_version', 'php_version'],
                'error',
                'meta' => ['api_version', 'timestamp'],
            ]);
    }

    public function test_protected_status_endpoint_requires_api_key(): void
    {
        $response = $this->getJson('/api/v2/system/status');

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_protected_status_endpoint_accepts_valid_api_key(): void
    {
        $rawKey = 'catmin_test_external_key_123';

        $apiKey = ApiKey::query()->create([
            'name' => 'test-client',
            'key_hash' => hash('sha256', $rawKey),
            'scopes' => ['external.read'],
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $rawKey)
            ->getJson('/api/v2/system/status');

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.api_key_id', $apiKey->id)
            ->assertJsonPath('data.api_key_name', 'test-client');

        $apiKey->refresh();
        $this->assertSame(1, (int) $apiKey->usage_count);
    }

    public function test_protected_status_endpoint_rejects_missing_scope(): void
    {
        $rawKey = 'catmin_test_external_readonly';

        ApiKey::query()->create([
            'name' => 'pages-reader',
            'key_hash' => hash('sha256', $rawKey),
            'scopes' => ['pages.read'],
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $rawKey)
            ->getJson('/api/v2/system/status');

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('error.details.required_scope', 'external.read');
    }

    public function test_revoked_api_key_is_rejected(): void
    {
        $rawKey = 'catmin_test_external_revoked';

        ApiKey::query()->create([
            'name' => 'revoked-client',
            'key_hash' => hash('sha256', $rawKey),
            'scopes' => ['external.read'],
            'is_active' => true,
            'revoked_at' => now(),
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $rawKey)
            ->getJson('/api/v2/system/status');

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_public_endpoint_returns_coherent_rate_limit_error(): void
    {
        config()->set('catmin.api.external.rate_limits.public-read.limit', 1);
        config()->set('catmin.api.external.rate_limits.public-read.decay_seconds', 60);

        RateLimiter::clear('catmin-api:public-read:ip:127.0.0.1');

        $this->getJson('/api/v2/version')->assertStatus(200);
        $response = $this->getJson('/api/v2/version');

        $response
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'rate_limited');

        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_public_pages_endpoint_is_paginated_and_caps_per_page(): void
    {
        Page::query()->create([
            'title' => 'Home',
            'slug' => 'home',
            'content' => 'Welcome',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => 'About us',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/v2/pages/published?per_page=500');

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.resource', 'pages')
            ->assertJsonPath('meta.pagination.per_page', 100)
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonCount(2, 'data');
    }
}
