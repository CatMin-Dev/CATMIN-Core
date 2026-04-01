<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CatminFreezeV2CommandTest extends TestCase
{
    public function test_freeze_v2_command_returns_structured_json_report(): void
    {
        $exitCode = Artisan::call('catmin:freeze:v2', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertContains($exitCode, [0, 1]);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('scope', $payload);
        $this->assertArrayHasKey('baseline', $payload);
        $this->assertArrayHasKey('release_strategy', $payload);
        $this->assertArrayHasKey('handover', $payload);
        $this->assertArrayHasKey('checks', $payload);
        $this->assertArrayHasKey('critical_blockers', $payload['summary']);
        $this->assertIsArray($payload['checks']);
        $this->assertIsArray($payload['blockers'] ?? []);
    }
}
