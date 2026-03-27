<?php

namespace Modules\Mailer\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Logger\Services\SystemLogService;
use Modules\Mailer\Jobs\SendTemplatedMailJob;
use Modules\Mailer\Mail\TemplatedMail;
use Modules\Mailer\Models\MailerConfig;
use Modules\Mailer\Models\MailerHistory;
use Modules\Mailer\Models\MailerTemplate;

class MailerAdminService
{
    public function __construct(private readonly SystemLogService $systemLogService)
    {
    }

    public function getOrCreateConfig(): MailerConfig
    {
        /** @var MailerConfig $config */
        $config = MailerConfig::query()->firstOrCreate([], [
            'driver' => 'log',
            'is_enabled' => false,
        ]);

        return $config;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateConfig(array $payload): MailerConfig
    {
        $config = $this->getOrCreateConfig();
        $config->fill([
            'driver' => (string) ($payload['driver'] ?? 'log'),
            'from_email' => $payload['from_email'] ?: null,
            'from_name' => $payload['from_name'] ?: null,
            'reply_to_email' => $payload['reply_to_email'] ?: null,
            'is_enabled' => (bool) ($payload['is_enabled'] ?? false),
        ]);
        $config->save();

        return $config;
    }

    /**
     * @return Collection<int, MailerTemplate>
     */
    public function templateListing(): Collection
    {
        $this->ensureDefaultTemplates();

        return MailerTemplate::query()->orderBy('name')->get();
    }

    public function findTemplateByCode(string $code): ?MailerTemplate
    {
        $this->ensureDefaultTemplates();

        return MailerTemplate::query()->where('code', $code)->first();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createTemplate(array $payload): MailerTemplate
    {
        $code = $this->uniqueTemplateCode((string) ($payload['code'] ?? ''), (string) $payload['name']);

        /** @var MailerTemplate $template */
        $template = MailerTemplate::query()->create([
            'code' => $code,
            'name' => (string) $payload['name'],
            'description' => (string) ($payload['description'] ?? ''),
            'subject' => (string) $payload['subject'],
            'body_html' => (string) ($payload['body_html'] ?? ''),
            'body_text' => (string) ($payload['body_text'] ?? ''),
            'available_variables' => $this->normalizeVariableList($payload['available_variables'] ?? []),
            'sample_payload' => $this->normalizePayload($payload['sample_payload'] ?? []),
            'is_enabled' => (bool) ($payload['is_enabled'] ?? true),
        ]);

        $this->logAudit('mailer.template.created', 'Template mail cree', ['template_id' => $template->id, 'code' => $template->code]);

        return $template;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateTemplate(MailerTemplate $template, array $payload): MailerTemplate
    {
        $code = $this->uniqueTemplateCode((string) ($payload['code'] ?? ''), (string) $payload['name'], $template->id);

        $template->fill([
            'code' => $code,
            'name' => (string) $payload['name'],
            'description' => (string) ($payload['description'] ?? ''),
            'subject' => (string) ($payload['subject'] ?? ''),
            'body_html' => (string) ($payload['body_html'] ?? ''),
            'body_text' => (string) ($payload['body_text'] ?? ''),
            'available_variables' => $this->normalizeVariableList($payload['available_variables'] ?? []),
            'sample_payload' => $this->normalizePayload($payload['sample_payload'] ?? []),
            'is_enabled' => (bool) ($payload['is_enabled'] ?? true),
        ]);
        $template->save();

        $this->logAudit('mailer.template.updated', 'Template mail modifie', ['template_id' => $template->id, 'code' => $template->code]);

        return $template;
    }

    public function historyListing(): LengthAwarePaginator
    {
        return MailerHistory::query()
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param MailerTemplate|string $template
     * @param array<string, mixed> $variables
     * @return array{subject:string, body_html:string, body_text:string, variables:array<string, mixed>}
     */
    public function previewTemplate(MailerTemplate|string $template, array $variables = []): array
    {
        $templateModel = $template instanceof MailerTemplate ? $template : $this->findTemplateByCode($template);
        if (!$templateModel) {
            return [
                'subject' => '',
                'body_html' => '',
                'body_text' => '',
                'variables' => [],
            ];
        }

        $mergedVariables = array_replace_recursive($this->defaultPreviewVariables(), $templateModel->sample_payload ?? [], $variables);

        return [
            'subject' => $this->renderString((string) $templateModel->subject, $mergedVariables),
            'body_html' => $this->renderString((string) ($templateModel->body_html ?? ''), $mergedVariables),
            'body_text' => $this->renderString((string) ($templateModel->body_text ?? ''), $mergedVariables),
            'variables' => $mergedVariables,
        ];
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $options
     */
    public function dispatchTemplate(MailerTemplate|string $template, string $recipient, ?string $recipientName = null, array $variables = [], array $options = []): MailerHistory
    {
        $templateModel = $template instanceof MailerTemplate ? $template : $this->findTemplateByCode($template);
        if (!$templateModel || !$templateModel->is_enabled) {
            throw new \RuntimeException('Template mail introuvable ou inactif.');
        }

        $config = $this->getOrCreateConfig();
        $preview = $this->previewTemplate($templateModel, $variables);
        $queue = (bool) ($options['queue'] ?? true);

        /** @var MailerHistory $history */
        $history = MailerHistory::query()->create([
            'recipient' => $recipient,
            'recipient_name' => $recipientName,
            'subject' => $preview['subject'],
            'template_code' => $templateModel->code,
            'driver' => (string) $config->driver,
            'status' => $queue ? 'queued' : 'pending',
            'variables_json' => $preview['variables'],
            'body_html' => $preview['body_html'],
            'body_text' => $preview['body_text'],
            'queued_at' => $queue ? now() : null,
            'attempts' => 0,
            'is_test' => (bool) ($options['is_test'] ?? false),
            'trigger_source' => (string) ($options['trigger_source'] ?? 'system'),
        ]);

        if ($queue) {
            SendTemplatedMailJob::dispatch($history->id)->onQueue('mail');
        } else {
            $history = $this->deliverHistory($history);
        }

        return $history;
    }

    public function deliverHistory(MailerHistory|int $history): MailerHistory
    {
        $historyModel = $history instanceof MailerHistory ? $history : MailerHistory::query()->findOrFail($history);
        $config = $this->getOrCreateConfig();

        if (!$config->is_enabled && $config->driver !== 'log') {
            return $this->markHistoryFailed($historyModel, 'Mailer desactive.');
        }

        try {
            $historyModel->attempts = (int) $historyModel->attempts + 1;
            $historyModel->status = 'sending';
            $historyModel->save();

            $mailable = new TemplatedMail(
                mailSubject: (string) $historyModel->subject,
                html: (string) ($historyModel->body_html ?? ''),
                text: (string) ($historyModel->body_text ?? ''),
                fromEmail: $config->from_email ?: config('mail.from.address'),
                fromName: $config->from_name ?: config('mail.from.name'),
                replyToEmail: $config->reply_to_email,
            );

            Mail::mailer($config->driver)->to($historyModel->recipient, $historyModel->recipient_name)->send($mailable);

            $historyModel->status = 'sent';
            $historyModel->sent_at = now();
            $historyModel->error_message = null;
            $historyModel->save();

            $this->logAudit('mailer.sent', 'Email envoye', [
                'history_id' => $historyModel->id,
                'recipient' => $historyModel->recipient,
                'template_code' => $historyModel->template_code,
                'status' => $historyModel->status,
            ]);
        } catch (\Throwable $throwable) {
            return $this->markHistoryFailed($historyModel, $throwable->getMessage());
        }

        return $historyModel;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function sendTestTemplate(MailerTemplate $template, string $recipient, array $variables = [], bool $queue = false): MailerHistory
    {
        return $this->dispatchTemplate($template, $recipient, null, $variables, [
            'queue' => $queue,
            'is_test' => true,
            'trigger_source' => 'admin.test',
        ]);
    }

    private function markHistoryFailed(MailerHistory $history, string $message): MailerHistory
    {
        $history->status = 'failed';
        $history->failed_at = now();
        $history->error_message = Str::limit($message, 65535, '');
        $history->save();

        $this->logAudit('mailer.failed', 'Echec envoi email', [
            'history_id' => $history->id,
            'recipient' => $history->recipient,
            'template_code' => $history->template_code,
            'error' => $message,
        ], 'warning');

        return $history;
    }

    /**
     * @param mixed $payload
     * @return array<string, mixed>
     */
    public function normalizePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (!is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param mixed $variables
     * @return array<int, string>
     */
    public function normalizeVariableList(mixed $variables): array
    {
        if (is_array($variables)) {
            return array_values(array_filter(array_map(fn ($value) => trim((string) $value), $variables)));
        }

        if (!is_string($variables) || trim($variables) === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n|,/', $variables) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    private function renderString(string $content, array $variables): string
    {
        $flatVariables = Arr::dot($variables);

        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function (array $matches) use ($flatVariables) {
            $key = (string) ($matches[1] ?? '');
            $value = $flatVariables[$key] ?? null;

            if (is_scalar($value) || $value === null) {
                return (string) ($value ?? '');
            }

            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }, $content) ?? $content;
    }

    private function ensureDefaultTemplates(): void
    {
        foreach ($this->defaultTemplates() as $template) {
            MailerTemplate::query()->firstOrCreate(
                ['code' => $template['code']],
                $template
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultTemplates(): array
    {
        return [
            [
                'code' => 'system_test',
                'name' => 'Test systeme',
                'description' => 'Template de verification rapide du mailer CATMIN.',
                'subject' => 'Test CATMIN {{ app.name }}',
                'body_html' => '<h1>Test CATMIN</h1><p>Bonjour {{ user.name }}, le mailer fonctionne.</p><p>Environnement: {{ app.env }}</p>',
                'body_text' => "Test CATMIN\nBonjour {{ user.name }}, le mailer fonctionne.\nEnvironnement: {{ app.env }}",
                'available_variables' => ['app.name', 'app.env', 'user.name'],
                'sample_payload' => $this->defaultPreviewVariables(),
                'is_enabled' => true,
            ],
            [
                'code' => 'shop_order_created',
                'name' => 'Shop commande creee',
                'description' => 'Confirmation de creation d une commande shop.',
                'subject' => 'Commande {{ order.number }} recue',
                'body_html' => '<h1>Commande {{ order.number }}</h1><p>Bonjour {{ customer.name }}, votre commande est en statut {{ order.status }}.</p><p>Total: {{ order.total }} {{ order.currency }}</p>',
                'body_text' => "Commande {{ order.number }}\nBonjour {{ customer.name }}, votre commande est en statut {{ order.status }}.\nTotal: {{ order.total }} {{ order.currency }}",
                'available_variables' => ['customer.name', 'order.number', 'order.status', 'order.total', 'order.currency'],
                'sample_payload' => $this->defaultShopPreviewVariables(),
                'is_enabled' => true,
            ],
            [
                'code' => 'shop_order_status',
                'name' => 'Shop changement statut commande',
                'description' => 'Notification de changement de statut pour une commande shop.',
                'subject' => 'Commande {{ order.number }} mise a jour',
                'body_html' => '<h1>Mise a jour commande {{ order.number }}</h1><p>Bonjour {{ customer.name }}, le nouveau statut est {{ order.status }}.</p>',
                'body_text' => "Commande {{ order.number }}\nBonjour {{ customer.name }}, le nouveau statut est {{ order.status }}.",
                'available_variables' => ['customer.name', 'order.number', 'order.status'],
                'sample_payload' => $this->defaultShopPreviewVariables(),
                'is_enabled' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPreviewVariables(): array
    {
        return [
            'app' => [
                'name' => config('app.name', 'CATMIN'),
                'env' => app()->environment(),
            ],
            'user' => [
                'name' => 'Admin CATMIN',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultShopPreviewVariables(): array
    {
        return array_replace_recursive($this->defaultPreviewVariables(), [
            'customer' => [
                'name' => 'Client CATMIN',
            ],
            'order' => [
                'number' => 'CMD-20260327-0001',
                'status' => 'pending',
                'total' => '149.90',
                'currency' => 'EUR',
            ],
        ]);
    }

    private function logAudit(string $event, string $message, array $context = [], string $level = 'info'): void
    {
        try {
            $this->systemLogService->logAudit($event, $message, $context, $level, (string) session('catmin_admin_username', ''));
        } catch (\Throwable) {
        }
    }

    private function uniqueTemplateCode(string $candidateCode, string $name, ?int $ignoreId = null): string
    {
        $baseCode = Str::slug($candidateCode !== '' ? $candidateCode : $name, '_');
        $baseCode = $baseCode !== '' ? $baseCode : 'template';

        $code = $baseCode;
        $suffix = 1;

        while (MailerTemplate::query()->where('code', $code)->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $suffix++;
            $code = $baseCode . '_' . $suffix;
        }

        return $code;
    }
}
