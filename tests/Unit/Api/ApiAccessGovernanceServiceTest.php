<?php

namespace Tests\Unit\Api;

use App\Services\Api\ApiAccessGovernanceService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiAccessGovernanceServiceTest extends TestCase
{
    #[Test]
    public function it_expands_scope_profiles_and_supports_wildcards(): void
    {
        config()->set('catmin.api.external.scope_profiles', [
            'readonly' => ['external.read', 'pages.read', 'articles.read'],
            'content-manager' => ['pages.*', 'articles.*', 'media.read'],
        ]);

        $service = app(ApiAccessGovernanceService::class);

        $this->assertTrue($service->hasScope(['readonly'], 'pages.read'));
        $this->assertTrue($service->hasScope(['content-manager'], 'pages.write'));
        $this->assertFalse($service->hasScope(['readonly'], 'pages.write'));
    }

    #[Test]
    public function it_temporarily_blocks_abusive_invalid_credentials(): void
    {
        config()->set('catmin.api.external.abuse.invalid_credentials_threshold', 2);
        config()->set('catmin.api.external.abuse.block_seconds', 120);

        $request = Request::create('/api/v2/system/status', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.9']);
        $service = app(ApiAccessGovernanceService::class);

        $service->recordInvalidCredential($request);
        $this->assertNull($service->isBlocked($request));

        $service->recordInvalidCredential($request);
        $this->assertNotNull($service->isBlocked($request));
    }
}
