<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }
}
