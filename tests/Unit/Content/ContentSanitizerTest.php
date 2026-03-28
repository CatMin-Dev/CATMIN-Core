<?php

namespace Tests\Unit\Content;

use App\Services\ContentSanitizerService;
use PHPUnit\Framework\TestCase;

class ContentSanitizerTest extends TestCase
{
    private ContentSanitizerService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ContentSanitizerService();
    }

    // -----------------------------------------------------------------------
    // Allowed HTML is preserved
    // -----------------------------------------------------------------------

    public function test_allowed_tags_are_kept(): void
    {
        $input = '<p>Hello <strong>world</strong></p>';
        $this->assertStringContainsString('<strong>world</strong>', $this->svc->sanitize($input));
    }

    public function test_headings_are_kept(): void
    {
        $input = '<h2>Section</h2><p>Text</p>';
        $output = $this->svc->sanitize($input);
        $this->assertStringContainsString('<h2>Section</h2>', $output);
        $this->assertStringContainsString('<p>Text</p>', $output);
    }

    public function test_links_with_safe_href_are_kept(): void
    {
        $input = '<a href="https://example.com" title="Ex">Link</a>';
        $output = $this->svc->sanitize($input);
        $this->assertStringContainsString('href="https://example.com"', $output);
        $this->assertStringContainsString('>Link</a>', $output);
    }

    public function test_lists_are_kept(): void
    {
        $input = '<ul><li>A</li><li>B</li></ul>';
        $output = $this->svc->sanitize($input);
        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<li>A</li>', $output);
    }

    public function test_table_structure_is_kept(): void
    {
        $input = '<table><thead><tr><th>H</th></tr></thead><tbody><tr><td colspan="2">Cell</td></tr></tbody></table>';
        $output = $this->svc->sanitize($input);
        $this->assertStringContainsString('<table>', $output);
        $this->assertStringContainsString('colspan="2"', $output);
    }

    // -----------------------------------------------------------------------
    // XSS vectors are removed
    // -----------------------------------------------------------------------

    public function test_script_tag_is_removed_with_content(): void
    {
        $input = '<p>Safe</p><script>alert("xss")</script><p>Also safe</p>';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringNotContainsString('alert', $output);
        $this->assertStringContainsString('Safe', $output);
    }

    public function test_javascript_href_is_stripped(): void
    {
        $input = '<a href="javascript:alert(1)">Click</a>';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('javascript:', $output);
        // The link text is preserved but without the href
        $this->assertStringContainsString('Click', $output);
    }

    public function test_event_handlers_are_stripped(): void
    {
        $input = '<p onclick="alert(1)">Text</p>';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('onclick', $output);
        $this->assertStringContainsString('Text', $output);
    }

    public function test_onerror_on_img_is_stripped(): void
    {
        $input = '<img src="valid.jpg" onerror="alert(1)" alt="img">';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('onerror', $output);
        $this->assertStringContainsString('valid.jpg', $output);
    }

    public function test_data_uri_img_src_is_stripped(): void
    {
        $input = '<img src="data:image/png;base64,abc" alt="evil">';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('data:', $output);
    }

    public function test_style_attribute_is_stripped(): void
    {
        $input = '<p style="color:red">Styled</p>';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('style=', $output);
        $this->assertStringContainsString('Styled', $output);
    }

    public function test_iframe_is_removed_completely(): void
    {
        $input = '<p>Before</p><iframe src="evil.html">iframe content</iframe><p>After</p>';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringNotContainsString('iframe content', $output);
        $this->assertStringContainsString('Before', $output);
        $this->assertStringContainsString('After', $output);
    }

    public function test_disallowed_tag_is_unwrapped_keeping_text(): void
    {
        $input = '<article><p>Content</p></article>';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('<article', $output);
        $this->assertStringContainsString('Content', $output);
    }

    // -----------------------------------------------------------------------
    // Security extras
    // -----------------------------------------------------------------------

    public function test_blank_target_gets_noopener_rel(): void
    {
        $input = '<a href="https://example.com" target="_blank">Open</a>';
        $output = $this->svc->sanitize($input);
        $this->assertStringContainsString('rel="noopener noreferrer"', $output);
    }

    public function test_empty_string_returns_empty(): void
    {
        $this->assertSame('', $this->svc->sanitize(''));
        $this->assertSame('', $this->svc->sanitize('   '));
    }

    public function test_plain_text_is_returned_unchanged(): void
    {
        $input = 'Hello World, no HTML here.';
        $output = $this->svc->sanitize($input);
        $this->assertStringContainsString('Hello World', $output);
    }

    public function test_obfuscated_javascript_href_is_stripped(): void
    {
        // Whitespace-padded JS URL
        $input = '<a href="  javascript:void(0)">Click</a>';
        $output = $this->svc->sanitize($input);
        $this->assertStringNotContainsString('javascript:', $output);
    }
}
