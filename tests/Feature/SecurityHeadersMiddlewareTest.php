<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    #[Test]
    public function it_applies_security_headers_on_admin_login_page(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');

        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', '0');
    }

    #[Test]
    public function it_can_disable_security_headers_via_config(): void
    {
        config()->set('catmin.security.headers.enabled', false);

        $response = $this->get('/admin-error/404');

        $response->assertOk();
        $response->assertHeaderMissing('Content-Security-Policy');
        $response->assertHeaderMissing('X-Frame-Options');
    }
}
