<?php

declare(strict_types=1);

namespace App\Services\Editor;

use App\Support\Editor\EditorFieldDefinition;

class EditorFieldResolver
{
    public function __construct(private readonly EditorFieldRegistry $registry)
    {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function resolve(string $scope, string $field, array $context = []): EditorFieldDefinition
    {
        $resolved = $this->registry->resolve($scope, $field, $context);
        if ($resolved !== null) {
            return $resolved;
        }

        return $this->fallback($scope, $field);
    }

    private function fallback(string $scope, string $field): EditorFieldDefinition
    {
        $fieldName = strtolower($field);
        $key = strtolower($scope . '.' . $field);

        if (in_array($key, ['pages.create.content', 'pages.edit.content', 'articles.create.content', 'articles.edit.content'], true)) {
            return new EditorFieldDefinition($scope, $field, 'structured', true, ['*'], ['*'], source: 'fallback');
        }

        if (in_array($fieldName, ['content', 'description', 'body'], true)) {
            return new EditorFieldDefinition($scope, $field, 'rich+assets', true, ['*'], [], source: 'fallback');
        }

        if (in_array($fieldName, ['excerpt', 'summary', 'meta_description'], true)) {
            return new EditorFieldDefinition($scope, $field, 'rich', false, [], [], source: 'fallback');
        }

        if (str_contains($fieldName, 'note')) {
            return new EditorFieldDefinition($scope, $field, 'simple', false, [], [], source: 'fallback');
        }

        return new EditorFieldDefinition($scope, $field, 'simple', false, [], [], source: 'fallback');
    }
}
