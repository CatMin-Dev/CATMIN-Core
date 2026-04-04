<?php

declare(strict_types=1);

namespace Tests\Unit\Editor;

use App\Services\Editor\BlockRegistry;
use App\Services\Editor\EditorFieldResolver;
use App\Services\Editor\FieldEditorIntegrationService;
use App\Services\Editor\SnippetRegistry;
use Tests\TestCase;

class EditorIntegrationResolverTest extends TestCase
{
    public function test_page_content_receives_structured_mode(): void
    {
        $integration = app(FieldEditorIntegrationService::class)
            ->resolve('pages.create', 'content');

        $this->assertSame('structured', $integration['mode']);
        $this->assertTrue($integration['panel_enabled']);
    }

    public function test_article_excerpt_receives_rich_mode(): void
    {
        $integration = app(FieldEditorIntegrationService::class)
            ->resolve('articles.edit', 'excerpt');

        $this->assertSame('rich', $integration['mode']);
        $this->assertFalse($integration['panel_enabled']);
    }

    public function test_notes_field_falls_back_to_simple_mode(): void
    {
        $integration = app(FieldEditorIntegrationService::class)
            ->resolve('users.create', 'internal_notes');

        $this->assertSame('simple', $integration['mode']);
        $this->assertFalse($integration['enabled']);
    }

    public function test_registry_respects_missing_addon_requirements(): void
    {
        $snippetRegistry = app(SnippetRegistry::class);

        SnippetRegistry::registerProvider(static function (): array {
            return [[
                'id' => 'addon-only-snippet',
                'label' => 'Addon only',
                'html' => '<p>Addon</p>',
                'scopes' => ['test.scope.field'],
                'requires_addons' => ['addon-does-not-exist'],
            ]];
        });

        $items = $snippetRegistry->forContext([
            'scope' => 'test.scope',
            'field' => 'field',
        ]);

        $this->assertFalse(collect($items)->contains(fn (array $item): bool => ($item['id'] ?? '') === 'addon-only-snippet'));
    }

    public function test_structured_context_exposes_snippets_and_blocks(): void
    {
        $integration = app(FieldEditorIntegrationService::class)
            ->resolve('pages.edit', 'content');

        $this->assertNotEmpty($integration['snippets']);
        $this->assertNotEmpty($integration['blocks']);
    }

    public function test_non_structured_context_has_no_blocks(): void
    {
        $integration = app(FieldEditorIntegrationService::class)
            ->resolve('shop.create', 'description');

        $this->assertSame('rich+assets', $integration['mode']);
        $this->assertEmpty($integration['blocks']);
    }

    public function test_block_registry_filters_by_scope(): void
    {
        $blocks = app(BlockRegistry::class)->forContext([
            'scope' => 'pages.create',
            'field' => 'content',
        ]);

        $this->assertTrue(collect($blocks)->contains(fn (array $block): bool => ($block['id'] ?? '') === 'hero'));
    }

    public function test_unknown_scope_uses_resolver_fallback(): void
    {
        $resolver = app(EditorFieldResolver::class);
        $definition = $resolver->resolve('unknown.scope', 'description');

        $this->assertSame('rich+assets', $definition->mode);
    }
}
