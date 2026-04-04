<?php

declare(strict_types=1);

namespace App\Services\Editor;

use App\Support\Editor\EditorFieldDefinition;

class FieldEditorIntegrationService
{
    public function __construct(
        private readonly EditorFieldResolver $fieldResolver,
        private readonly SnippetRegistry $snippetRegistry,
        private readonly BlockRegistry $blockRegistry,
    ) {
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function resolve(string $scope, string $field, array $context = []): array
    {
        $definition = $this->fieldResolver->resolve($scope, $field, $context);
        $mode = $this->normalizeMode($definition->mode);

        $resolvedContext = array_merge($context, [
            'scope' => $scope,
            'field' => $field,
            'mode' => $mode,
        ]);

        $snippets = $mode === 'simple'
            ? []
            : $this->snippetRegistry->forContext($resolvedContext, $definition->snippetIds === [] ? ['*'] : $definition->snippetIds);

        $blocks = $mode === 'structured'
            ? $this->blockRegistry->forContext($resolvedContext, $definition->blockIds === [] ? ['*'] : $definition->blockIds)
            : [];

        return [
            'mode' => $mode,
            'enabled' => $mode !== 'simple',
            'media_allowed' => $definition->mediaAllowed || in_array($mode, ['rich+assets', 'structured'], true),
            'panel_enabled' => $mode === 'structured',
            'snippets' => $snippets,
            'blocks' => $blocks,
            'definition' => $definition,
        ];
    }

    /**
     * @param array<string,mixed> $context
     */
    public function fieldDefinition(string $scope, string $field, array $context = []): EditorFieldDefinition
    {
        return $this->fieldResolver->resolve($scope, $field, $context);
    }

    private function normalizeMode(string $mode): string
    {
        $mode = trim(strtolower($mode));

        return match ($mode) {
            'rich', 'rich+assets', 'structured', 'simple' => $mode,
            default => 'simple',
        };
    }
}
