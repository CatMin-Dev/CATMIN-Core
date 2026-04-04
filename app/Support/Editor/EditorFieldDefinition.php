<?php

declare(strict_types=1);

namespace App\Support\Editor;

final class EditorFieldDefinition
{
    public function __construct(
        public readonly string $scope,
        public readonly string $field,
        public readonly string $mode = 'simple',
        public readonly bool $mediaAllowed = false,
        /** @var array<int,string> */
        public readonly array $snippetIds = [],
        /** @var array<int,string> */
        public readonly array $blockIds = [],
        /** @var array<int,string> */
        public readonly array $permissions = [],
        /** @var array<int,string> */
        public readonly array $requiresModules = [],
        /** @var array<int,string> */
        public readonly array $requiresAddons = [],
        public readonly string $source = 'core'
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            scope: (string) ($data['scope'] ?? ''),
            field: (string) ($data['field'] ?? ''),
            mode: (string) ($data['mode'] ?? 'simple'),
            mediaAllowed: (bool) ($data['media_allowed'] ?? false),
            snippetIds: self::normalizeStringList($data['snippets'] ?? []),
            blockIds: self::normalizeStringList($data['blocks'] ?? []),
            permissions: self::normalizeStringList($data['permissions'] ?? []),
            requiresModules: self::normalizeStringList($data['requires_modules'] ?? []),
            requiresAddons: self::normalizeStringList($data['requires_addons'] ?? []),
            source: (string) ($data['source'] ?? 'core')
        );
    }

    public function key(): string
    {
        return $this->scope . '.' . $this->field;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'scope' => $this->scope,
            'field' => $this->field,
            'mode' => $this->mode,
            'media_allowed' => $this->mediaAllowed,
            'snippets' => $this->snippetIds,
            'blocks' => $this->blockIds,
            'permissions' => $this->permissions,
            'requires_modules' => $this->requiresModules,
            'requires_addons' => $this->requiresAddons,
            'source' => $this->source,
        ];
    }

    /** @param mixed $value @return array<int,string> */
    private static function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $value
        ), static fn (string $item): bool => $item !== ''));
    }
}
