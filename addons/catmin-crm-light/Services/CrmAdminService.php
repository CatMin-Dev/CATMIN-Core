<?php

namespace Addons\CatminCrmLight\Services;

use Addons\CatminCrmLight\Models\CrmCompany;
use Addons\CatminCrmLight\Models\CrmContact;
use Addons\CatminCrmLight\Models\CrmInteraction;
use Addons\CatminCrmLight\Models\CrmNote;
use Addons\CatminCrmLight\Services\CrmPipelineService;
use Addons\CatminCrmLight\Services\CrmRelationService;
use Addons\CatminCrmLight\Services\CrmWorkflowService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Cache\Services\QueryCacheService;

class CrmAdminService
{
    public function __construct(
        private readonly CrmRelationService $relationService,
        private readonly CrmPipelineService $pipelineService,
        private readonly CrmWorkflowService $workflowService,
    ) {
    }

    /** @param array<string,mixed> $filters */
    public function contacts(array $filters = []): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $pipelineStage = trim((string) ($filters['pipeline_stage'] ?? ''));
        $source = trim((string) ($filters['source'] ?? ''));
        $interactionFrom = trim((string) ($filters['interaction_from'] ?? ''));
        $interactionTo = trim((string) ($filters['interaction_to'] ?? ''));
        $page = max(1, (int) request()->query('page', 1));
        $key = 'contacts.' . md5(json_encode([$q, $pipelineStage, $source, $interactionFrom, $interactionTo, $page]));

        return QueryCacheService::remember('crm', $key, 90, function () use ($q, $pipelineStage, $source, $interactionFrom, $interactionTo): LengthAwarePaginator {
            return CrmContact::query()
                ->with('company:id,name')
                ->when($q !== '', function ($builder) use ($q): void {
                    $builder->where(function ($sub) use ($q): void {
                        $sub->where('first_name', 'like', '%' . $q . '%')
                            ->orWhere('last_name', 'like', '%' . $q . '%')
                            ->orWhere('email', 'like', '%' . $q . '%')
                            ->orWhere('phone', 'like', '%' . $q . '%')
                            ->orWhere('pipeline_stage', 'like', '%' . $q . '%')
                            ->orWhere('source', 'like', '%' . $q . '%')
                            ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', '%' . $q . '%'));
                    });
                })
                    ->when($pipelineStage !== '', fn ($builder) => $builder->where('pipeline_stage', $pipelineStage))
                    ->when($source !== '', fn ($builder) => $builder->where('source', $source))
                    ->when($interactionFrom !== '', fn ($builder) => $builder->whereDate('last_interaction_at', '>=', $interactionFrom))
                    ->when($interactionTo !== '', fn ($builder) => $builder->whereDate('last_interaction_at', '<=', $interactionTo))
                ->orderByDesc('created_at')
                ->paginate(25)
                ->withQueryString();
        });
    }

    /** @param array<string,mixed> $payload */
    public function createContact(array $payload): CrmContact
    {
        $created = CrmContact::query()->create([
            'crm_company_id' => $payload['crm_company_id'] ?? null,
            'first_name' => (string) $payload['first_name'],
            'last_name' => $payload['last_name'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'position' => $payload['position'] ?? null,
            'status' => (string) ($payload['status'] ?? 'lead'),
            'pipeline_stage' => (string) ($payload['pipeline_stage'] ?? 'new'),
            'source' => (string) ($payload['source'] ?? 'admin'),
            'tags' => $payload['tags'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'metadata' => ['source' => 'admin'],
        ]);

        $this->invalidateCache();

        return $created;
    }

    /** @param array<string,mixed> $payload */
    public function updateContact(CrmContact $contact, array $payload): CrmContact
    {
        $contact->update([
            'first_name' => (string) $payload['first_name'],
            'last_name' => $payload['last_name'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'position' => $payload['position'] ?? null,
            'status' => (string) ($payload['status'] ?? $contact->status),
            'pipeline_stage' => (string) ($payload['pipeline_stage'] ?? $contact->pipeline_stage ?? 'new'),
            'source' => (string) ($payload['source'] ?? $contact->source ?? 'admin'),
            'tags' => $payload['tags'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->relationService->attachContactToCompany($contact, isset($payload['crm_company_id']) ? (int) $payload['crm_company_id'] : null);

        $this->invalidateCache();

        return $contact->fresh() ?? $contact;
    }

    public function deleteContact(CrmContact $contact): void
    {
        $contact->delete();
        $this->invalidateCache();
    }

    /** @param array<string,mixed> $filters */
    public function companies(array $filters = []): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $page = max(1, (int) request()->query('page', 1));
        $key = 'companies.' . md5(json_encode([$q, $page]));

        return QueryCacheService::remember('crm', $key, 90, function () use ($q): LengthAwarePaginator {
            return CrmCompany::query()
                ->withCount('contacts')
                ->when($q !== '', fn ($builder) => $builder->where('name', 'like', '%' . $q . '%'))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString();
        });
    }

    /** @param array<string,mixed> $payload */
    public function createCompany(array $payload): CrmCompany
    {
        $created = CrmCompany::query()->create([
            'name' => (string) $payload['name'],
            'website' => $payload['website'] ?? null,
            'industry' => $payload['industry'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->invalidateCache();

        return $created;
    }

    /** @param array<string,mixed> $payload */
    public function updateCompany(CrmCompany $company, array $payload): CrmCompany
    {
        $company->update([
            'name' => (string) $payload['name'],
            'website' => $payload['website'] ?? null,
            'industry' => $payload['industry'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->invalidateCache();

        return $company->fresh() ?? $company;
    }

    public function deleteCompany(CrmCompany $company): void
    {
        $company->delete();
        $this->invalidateCache();
    }

    public function addNote(CrmContact $contact, string $content, string $type = 'note', ?string $module = null, ?string $linkedType = null, ?int $linkedId = null): CrmNote
    {
        $note = CrmNote::query()->create([
            'crm_contact_id' => (int) $contact->id,
            'type' => $type,
            'content' => $content,
            'module' => $module,
            'linked_type' => $linkedType,
            'linked_id' => $linkedId,
            'created_by_id' => auth()->id(),
        ]);

        $this->invalidateCache();

        return $note;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function addInteraction(CrmContact $contact, array $payload): CrmInteraction
    {
        $interaction = $this->workflowService->addInteraction($contact, $payload);
        $this->addNote($contact, (string) $payload['content'], (string) ($payload['type'] ?? 'note'), 'crm.workflow');
        $this->invalidateCache();

        return $interaction;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function createTask(CrmContact $contact, array $payload): \Addons\CatminCrmLight\Models\CrmTask
    {
        $task = $this->workflowService->createTask($contact, $payload);
        $this->addNote($contact, 'Tache creee: ' . $task->title, 'task', 'crm.workflow');
        $this->invalidateCache();

        return $task;
    }

    public function completeTask(\Addons\CatminCrmLight\Models\CrmTask $task): \Addons\CatminCrmLight\Models\CrmTask
    {
        $task = $this->workflowService->completeTask($task);
        $contact = $task->contact;

        if ($contact) {
            $this->addNote($contact, 'Tache completee: ' . $task->title, 'task_done', 'crm.workflow');
        }

        $this->invalidateCache();

        return $task;
    }

    public function movePipeline(CrmContact $contact, string $toStage): CrmContact
    {
        $updated = $this->pipelineService->move($contact, $toStage);
        $this->addNote($updated, 'Pipeline -> ' . $toStage, 'pipeline', 'crm.pipeline');
        $this->invalidateCache();

        return $updated;
    }

    /**
     * @return array<string,int>
     */
    public function pipelineMetrics(): array
    {
        return $this->pipelineService->metrics();
    }

    /**
     * @return array<int,string>
     */
    public function pipelineStages(): array
    {
        return $this->pipelineService->stages();
    }

    /** @return array<int, array<string,mixed>> */
    public function contactTimeline(CrmContact $contact): array
    {
        $key = 'timeline.' . (int) $contact->id . '.' . md5((string) ($contact->email ?? ''));

        return QueryCacheService::remember('crm', $key, 60, function () use ($contact): array {
        $items = [];

        foreach ($contact->crmNotes()->get() as $note) {
            $items[] = [
                'source' => 'crm',
                'type' => (string) $note->type,
                'title' => 'Note CRM',
                'content' => (string) $note->content,
                'date' => optional($note->created_at)?->toIso8601String(),
            ];
        }

        if (DB::getSchemaBuilder()->hasTable('crm_interactions')) {
            $interactionRows = DB::table('crm_interactions')
                ->where('crm_contact_id', $contact->id)
                ->orderByDesc('happened_at')
                ->limit(20)
                ->get(['type', 'subject', 'content', 'happened_at']);

            foreach ($interactionRows as $row) {
                $items[] = [
                    'source' => 'crm',
                    'type' => (string) ($row->type ?? 'interaction'),
                    'title' => (string) ($row->subject ?: 'Interaction CRM'),
                    'content' => (string) ($row->content ?? ''),
                    'date' => optional($row->happened_at)?->toIso8601String(),
                ];
            }
        }

        if (DB::getSchemaBuilder()->hasTable('crm_tasks')) {
            $taskRows = DB::table('crm_tasks')
                ->where('crm_contact_id', $contact->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['title', 'status', 'due_at', 'created_at']);

            foreach ($taskRows as $row) {
                $items[] = [
                    'source' => 'crm',
                    'type' => 'task',
                    'title' => 'Tache: ' . (string) ($row->title ?? 'N/A'),
                    'content' => 'Statut: ' . (string) ($row->status ?? 'open'),
                    'date' => optional($row->due_at ?? $row->created_at)?->toIso8601String(),
                ];
            }
        }

        $email = trim((string) ($contact->email ?? ''));

        if ($email !== '' && DB::getSchemaBuilder()->hasTable('bookings')) {
            $bookingRows = DB::table('bookings')
                ->where('customer_email', $email)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'status', 'confirmation_code', 'created_at']);

            foreach ($bookingRows as $row) {
                $items[] = [
                    'source' => 'booking',
                    'type' => 'booking',
                    'title' => 'Réservation ' . ($row->confirmation_code ?? ('#' . $row->id)),
                    'content' => 'Statut: ' . (string) ($row->status ?? 'unknown'),
                    'date' => optional($row->created_at)?->toIso8601String(),
                ];
            }
        }

        if ($email !== '' && DB::getSchemaBuilder()->hasTable('event_participants')) {
            $eventRows = DB::table('event_participants')
                ->where('email', $email)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['id', 'status', 'created_at']);

            foreach ($eventRows as $row) {
                $items[] = [
                    'source' => 'event',
                    'type' => 'event',
                    'title' => 'Inscription événement #' . (int) $row->id,
                    'content' => 'Statut: ' . (string) ($row->status ?? 'unknown'),
                    'date' => optional($row->created_at)?->toIso8601String(),
                ];
            }
        }

            usort($items, static function (array $a, array $b): int {
                return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
            });

            return $items;
        });
    }

    public function sendContactMail(CrmContact $contact, string $subject, string $message): bool
    {
        if (trim((string) $contact->email) === '') {
            return false;
        }

        try {
            Mail::raw($message, function ($mail) use ($contact, $subject): void {
                $mail->to((string) $contact->email, $contact->fullName())
                    ->subject($subject);
            });

            $this->addNote($contact, 'Email envoyé: ' . $subject, 'mail', 'mailer');
            return true;
        } catch (\Throwable) {
            $this->addNote($contact, 'Échec envoi email: ' . $subject, 'mail_failed', 'mailer');
            return false;
        }
    }

    private function invalidateCache(): void
    {
        QueryCacheService::invalidateModules(['crm', 'dashboard', 'performance']);
    }
}
