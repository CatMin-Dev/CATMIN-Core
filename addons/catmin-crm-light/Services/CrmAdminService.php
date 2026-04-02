<?php

namespace Addons\CatminCrmLight\Services;

use Addons\CatminCrmLight\Models\CrmCompany;
use Addons\CatminCrmLight\Models\CrmContact;
use Addons\CatminCrmLight\Models\CrmNote;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CrmAdminService
{
    /** @param array<string,mixed> $filters */
    public function contacts(array $filters = []): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return CrmContact::query()
            ->with('company:id,name')
            ->when($q !== '', function ($builder) use ($q): void {
                $builder->where(function ($sub) use ($q): void {
                    $sub->where('first_name', 'like', '%' . $q . '%')
                        ->orWhere('last_name', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%')
                        ->orWhere('phone', 'like', '%' . $q . '%')
                        ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', '%' . $q . '%'));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    /** @param array<string,mixed> $payload */
    public function createContact(array $payload): CrmContact
    {
        return CrmContact::query()->create([
            'crm_company_id' => $payload['crm_company_id'] ?? null,
            'first_name' => (string) $payload['first_name'],
            'last_name' => $payload['last_name'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'position' => $payload['position'] ?? null,
            'status' => (string) ($payload['status'] ?? 'lead'),
            'tags' => $payload['tags'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'metadata' => ['source' => 'admin'],
        ]);
    }

    /** @param array<string,mixed> $payload */
    public function updateContact(CrmContact $contact, array $payload): CrmContact
    {
        $contact->update([
            'crm_company_id' => $payload['crm_company_id'] ?? null,
            'first_name' => (string) $payload['first_name'],
            'last_name' => $payload['last_name'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'position' => $payload['position'] ?? null,
            'status' => (string) ($payload['status'] ?? $contact->status),
            'tags' => $payload['tags'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        return $contact->fresh() ?? $contact;
    }

    public function deleteContact(CrmContact $contact): void
    {
        $contact->delete();
    }

    /** @param array<string,mixed> $filters */
    public function companies(array $filters = []): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return CrmCompany::query()
            ->withCount('contacts')
            ->when($q !== '', fn ($builder) => $builder->where('name', 'like', '%' . $q . '%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();
    }

    /** @param array<string,mixed> $payload */
    public function createCompany(array $payload): CrmCompany
    {
        return CrmCompany::query()->create([
            'name' => (string) $payload['name'],
            'website' => $payload['website'] ?? null,
            'industry' => $payload['industry'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);
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

        return $company->fresh() ?? $company;
    }

    public function deleteCompany(CrmCompany $company): void
    {
        $company->delete();
    }

    public function addNote(CrmContact $contact, string $content, string $type = 'note', ?string $module = null, ?string $linkedType = null, ?int $linkedId = null): CrmNote
    {
        return CrmNote::query()->create([
            'crm_contact_id' => (int) $contact->id,
            'type' => $type,
            'content' => $content,
            'module' => $module,
            'linked_type' => $linkedType,
            'linked_id' => $linkedId,
            'created_by_id' => auth()->id(),
        ]);
    }

    /** @return array<int, array<string,mixed>> */
    public function contactTimeline(CrmContact $contact): array
    {
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
}
