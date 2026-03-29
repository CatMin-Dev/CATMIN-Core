<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pages\Models\Page;
use Tests\TestCase;

class ApiV1ExternalCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available on this environment.');
        }

        parent::setUp();
    }

    public function test_pages_index_requires_api_credential(): void
    {
        $response = $this->getJson('/api/v1/pages');

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'unauthorized')
            ->assertJsonPath('meta.api_version', 'v1');
    }

    public function test_pages_index_with_read_scope_returns_paginated_shape(): void
    {
        $rawKey = 'catmin_test_v1_read_key_123';

        ApiKey::query()->create([
            'name' => 'v1-reader',
            'key_hash' => hash('sha256', $rawKey),
            'scopes' => ['pages.read'],
            'is_active' => true,
        ]);

        Page::query()->create([
            'title' => 'Home',
            'slug' => 'home',
            'content' => 'Welcome',
            'status' => 'published',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $rawKey)
            ->getJson('/api/v1/pages?per_page=10&status=published');

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonPath('meta.resource', 'pages')
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_pages_write_requires_write_scope(): void
    {
        $rawKey = 'catmin_test_v1_read_only_key';

        ApiKey::query()->create([
            'name' => 'v1-read-only',
            'key_hash' => hash('sha256', $rawKey),
            'scopes' => ['pages.read'],
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $rawKey)
            ->postJson('/api/v1/pages', [
                'title' => 'Forbidden create',
                'slug' => 'forbidden-create',
            ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_internal_token_allows_write_operations_without_api_key(): void
    {
        config()->set('catmin.api.internal_token', 'internal-v1-token');

        $response = $this
            ->withHeader('X-Catmin-Token', 'internal-v1-token')
            ->postJson('/api/v1/pages', [
                'title' => 'Internal page',
                'slug' => 'internal-page',
                'status' => 'draft',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonPath('meta.action', 'created');

        $this->assertDatabaseHas('pages', [
            'slug' => 'internal-page',
        ]);
    }
}
