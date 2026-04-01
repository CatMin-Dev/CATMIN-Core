<?php

namespace Tests\Unit\Editor;

use App\Services\Editor\WysiwygSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WysiwygSanitizerTest extends TestCase
{
    #[Test]
    public function it_removes_scripts_and_event_handlers(): void
    {
        $sanitizer = app(WysiwygSanitizer::class);

        $html = '<p onclick="alert(1)">Hello<script>alert(1)</script></p><a href="javascript:alert(2)">bad</a>';
        $clean = $sanitizer->sanitize($html);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick=', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('<p>Hello</p>', $clean);
    }

    #[Test]
    public function it_keeps_allowed_editor_formatting(): void
    {
        $sanitizer = app(WysiwygSanitizer::class);

        $html = '<p class="text-center unknown" style="text-align:center;color:#111;position:absolute">Body</p>';
        $clean = $sanitizer->sanitize($html);

        $this->assertStringContainsString('class="text-center"', $clean);
        $this->assertStringContainsString('style="text-align: center; color: #111"', $clean);
        $this->assertStringNotContainsString('position:absolute', $clean);
    }
}
