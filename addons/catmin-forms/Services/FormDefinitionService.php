<?php

namespace Addons\CatminForms\Services;

use Addons\CatminForms\Models\FormDefinition;
use Addons\CatminForms\Models\FormField;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class FormDefinitionService
{
    /** @param array<string,mixed> $filters */
    public function listing(array $filters = []): LengthAwarePaginator
    {
        return FormDefinition::query()
            ->withCount('submissions')
            ->when(($filters['q'] ?? '') !== '', fn ($q) => $q->where('name', 'like', '%' . $filters['q'] . '%'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    /** @param array<string,mixed> $payload */
    public function create(array $payload): FormDefinition
    {
        $slug = trim((string) ($payload['slug'] ?? '')) !== ''
            ? Str::slug((string) $payload['slug'])
            : Str::slug((string) $payload['name']);

        return FormDefinition::query()->create([
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'type' => (string) ($payload['type'] ?? 'custom'),
            'status' => (string) ($payload['status'] ?? 'active'),
            'config' => [
                'mapping' => (string) ($payload['mapping'] ?? 'none'),
                'target_id' => ($payload['target_id'] ?? '') !== '' ? (int) $payload['target_id'] : null,
            ],
        ]);
    }

    /** @param array<string,mixed> $payload */
    public function update(FormDefinition $formDefinition, array $payload): FormDefinition
    {
        $formDefinition->update([
            'name' => (string) $payload['name'],
            'type' => (string) ($payload['type'] ?? 'custom'),
            'status' => (string) ($payload['status'] ?? 'active'),
            'config' => [
                'mapping' => (string) ($payload['mapping'] ?? 'none'),
                'target_id' => ($payload['target_id'] ?? '') !== '' ? (int) $payload['target_id'] : null,
            ],
        ]);

        return $formDefinition->fresh() ?? $formDefinition;
    }

    public function delete(FormDefinition $formDefinition): void
    {
        $formDefinition->delete();
    }

    /** @param array<string,mixed> $payload */
    public function addField(FormDefinition $formDefinition, array $payload): FormField
    {
        return FormField::query()->create([
            'form_definition_id' => $formDefinition->id,
            'type' => (string) ($payload['type'] ?? 'text'),
            'label' => (string) $payload['label'],
            'key' => (string) $payload['key'],
            'is_required' => (bool) ($payload['is_required'] ?? false),
            'options' => isset($payload['options']) && trim((string) $payload['options']) !== ''
                ? explode(',', (string) $payload['options'])
                : null,
            'validation_rules' => (string) ($payload['validation_rules'] ?? ''),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
        ]);
    }

    public function removeField(FormField $formField): void
    {
        $formField->delete();
    }
}
