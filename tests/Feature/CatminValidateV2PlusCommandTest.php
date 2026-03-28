<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CatminValidateV2PlusCommandTest extends TestCase
{
    public function test_validation_v2_plus_json_report_has_expected_shape(): void
    {
        $exitCode = Artisan::call('catmin:validate:v2-plus', [
            '--json' => true,
            '--skip-tests' => true,
        ]);

        $report = json_decode(Artisan::output(), true);

        $this->assertContains($exitCode, [0, 1]);
        $this->assertIsArray($report);
        $this->assertArrayHasKey('ok', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('checks', $report);
        $this->assertArrayHasKey('context', $report);
        $this->assertArrayHasKey('tests', $report);
        $this->assertArrayHasKey('total', $report['summary']);
        $this->assertArrayHasKey('ok', $report['summary']);
        $this->assertArrayHasKey('nok', $report['summary']);
        $this->assertIsArray($report['checks']);
        $this->assertIsArray($report['context']);
        $this->assertFalse((bool) ($report['tests']['executed'] ?? true));
    }
}
