<?php

namespace Addons\CatminCrmLight\Services;

use Addons\CatminCrmLight\Models\CrmContact;
use Addons\CatminCrmLight\Models\CrmInteraction;
use Addons\CatminCrmLight\Models\CrmTask;

class CrmWorkflowService
{
    /**
     * @param array<string,mixed> $payload
     */
    public function addInteraction(CrmContact $contact, array $payload): CrmInteraction
    {
        $interaction = CrmInteraction::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_company_id' => $contact->crm_company_id,
            'type' => (string) ($payload['type'] ?? 'note'),
            'subject' => $payload['subject'] ?? null,
            'content' => (string) $payload['content'],
            'source' => (string) ($payload['source'] ?? 'crm'),
            'happened_at' => $payload['happened_at'] ?? now(),
            'created_by_id' => auth()->id(),
        ]);

        $contact->update([
            'last_interaction_at' => now(),
        ]);

        return $interaction;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function createTask(CrmContact $contact, array $payload): CrmTask
    {
        return CrmTask::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_company_id' => $contact->crm_company_id,
            'title' => (string) $payload['title'],
            'details' => $payload['details'] ?? null,
            'status' => 'open',
            'due_at' => $payload['due_at'] ?? null,
            'assigned_to_id' => $payload['assigned_to_id'] ?? null,
            'created_by_id' => auth()->id(),
        ]);
    }

    public function completeTask(CrmTask $task): CrmTask
    {
        $task->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        return $task->fresh() ?? $task;
    }
}
