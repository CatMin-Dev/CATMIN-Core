<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CatminEventMapStatusCommandTest extends TestCase
{
    public function test_event_map_status_json_exposes_required_sections(): void
    {
        $exitCode = Artisan::call('catmin:event-map:status', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('counts', $payload);
        $this->assertArrayHasKey('implemented', $payload);
        $this->assertArrayHasKey('wired', $payload);
        $this->assertArrayHasKey('documented_only', $payload);
        $this->assertArrayHasKey('base_listeners', $payload);
        $this->assertArrayHasKey('priorities', $payload);
    }
}
