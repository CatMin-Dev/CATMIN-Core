<?php

namespace Addons\CatminForms\Services;

use Addons\CatminForms\Models\FormDefinition;
use Addons\CatminForms\Models\FormSubmission;
use Illuminate\Pagination\LengthAwarePaginator;

class FormSubmissionService
{
    public function __construct(private readonly FormRoutingService $routingService)
    {
    }

    /** @param array<string,mixed> $filters */
    public function listing(array $filters = []): LengthAwarePaginator
    {
        return FormSubmission::query()
            ->with('form:id,name,slug')
            ->when(($filters['status'] ?? '') !== '', fn ($q) => $q->where('status', (string) $filters['status']))
            ->when(!empty($filters['form_definition_id']), fn ($q) => $q->where('form_definition_id', (int) $filters['form_definition_id']))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function submit(FormDefinition $formDefinition, array $payload): FormSubmission
    {
        $submission = FormSubmission::query()->create([
            'form_definition_id' => $formDefinition->id,
            'payload' => $payload,
            'source' => 'public',
            'status' => 'new',
            'ip_hash' => hash('sha256', (string) request()->ip()),
        ]);

        $linkedContactId = $this->routingService->handle($formDefinition, $submission, $payload);

        $submission->update([
            'linked_contact_id' => $linkedContactId,
        ]);

        return $submission->fresh() ?? $submission;
    }

    public function markProcessed(FormSubmission $formSubmission): FormSubmission
    {
        $formSubmission->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        return $formSubmission->fresh() ?? $formSubmission;
    }
}
