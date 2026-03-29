<?php

namespace Modules\Mailer\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Logger\Services\AlertingService;
use Modules\Logger\Services\SystemLogService;
use Modules\Mailer\Jobs\SendTemplatedMailJob;
use Modules\Mailer\Mail\TemplatedMail;
use Modules\Mailer\Models\MailerConfig;
use Modules\Mailer\Models\MailerHistory;
use Modules\Mailer\Models\MailerTemplate;

class MailerAdminService
{
    public function __construct(
        private readonly SystemLogService $systemLogService,
        private readonly AlertingService $alertingService,
    )
    {
    }

    public function getOrCreateConfig(): MailerConfig
    {
        /** @var MailerConfig $config */
        $config = MailerConfig::query()->firstOrCreate([], [
            'driver' => 'log',
            'brand_primary_color' => '#0d6efd',
            'retry_max_attempts' => (int) config('catmin.mailer.retry.max_attempts', 3),
            'retry_backoff_seconds' => (int) config('catmin.mailer.retry.backoff_seconds', 60),
            'failure_alert_threshold' => (int) config('catmin.mailer.failure_alert_threshold', 5),
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
            'brand_name' => $payload['brand_name'] ?: null,
            'brand_logo_url' => $payload['brand_logo_url'] ?: null,
            'brand_primary_color' => $payload['brand_primary_color'] ?: '#0d6efd',
            'brand_footer_text' => $payload['brand_footer_text'] ?: null,
            'sandbox_mode' => (bool) ($payload['sandbox_mode'] ?? false),
            'sandbox_recipient' => $payload['sandbox_recipient'] ?: null,
            'retry_max_attempts' => max(1, (int) ($payload['retry_max_attempts'] ?? config('catmin.mailer.retry.max_attempts', 3))),
            'retry_backoff_seconds' => max(5, (int) ($payload['retry_backoff_seconds'] ?? config('catmin.mailer.retry.backoff_seconds', 60))),
            'fallback_driver' => $payload['fallback_driver'] ?: null,
            'failure_alert_threshold' => max(1, (int) ($payload['failure_alert_threshold'] ?? config('catmin.mailer.failure_alert_threshold', 5))),
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

    /**
     * @param array<string, mixed> $filters
     */
    public function historyListing(array $filters = []): LengthAwarePaginator
    {
        return MailerHistory::query()
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', (string) $filters['status']))
            ->when(($filters['template_code'] ?? '') !== '', fn ($query) => $query->where('template_code', (string) $filters['template_code']))
            ->when(($filters['trigger_source'] ?? '') !== '', fn ($query) => $query->where('trigger_source', (string) $filters['trigger_source']))
            ->when(array_key_exists('is_test', $filters) && $filters['is_test'] !== '', fn ($query) => $query->where('is_test', (bool) $filters['is_test']))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function historySummary(): array
    {
        $base = MailerHistory::query();

        return [
            'pending' => (clone $base)->whereIn('status', ['pending', 'queued', 'sending', 'retrying'])->count(),
            'sent_24h' => (clone $base)->where('status', 'sent')->where('sent_at', '>=', now()->subDay())->count(),
            'failed_24h' => (clone $base)->where('status', 'failed')->where('failed_at', '>=', now()->subDay())->count(),
            'tests_24h' => (clone $base)->where('is_test', true)->where('created_at', '>=', now()->subDay())->count(),
        ];
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
        $subject = trim(strip_tags($this->renderTemplateString((string) $templateModel->subject, $mergedVariables)));
        $bodyHtml = $this->renderTemplateString((string) ($templateModel->body_html ?? ''), $mergedVariables);
        $bodyText = trim($this->renderTemplateString((string) ($templateModel->body_text ?? ''), $mergedVariables));

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
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
        $recipientForDelivery = $recipient;
        $recipientNameForDelivery = $recipientName;
        $originalRecipient = null;

        if ($config->sandbox_mode && $config->sandbox_recipient) {
            $originalRecipient = $recipient;
            $recipientForDelivery = (string) $config->sandbox_recipient;
            $recipientNameForDelivery = 'Sandbox';
            $preview['variables']['mail'] = [
                'sandbox' => true,
                'original_recipient' => $recipient,
                'sandbox_recipient' => $recipientForDelivery,
            ];
        }

        $preview['body_html'] = $this->applyBrandingTemplate($preview['body_html'], $config, $preview['variables']);
        if ($preview['body_text'] === '') {
            $preview['body_text'] = trim(strip_tags($preview['body_html']));
        }

        /** @var MailerHistory $history */
        $history = MailerHistory::query()->create([
            'recipient' => $recipientForDelivery,
            'recipient_name' => $recipientNameForDelivery,
            'subject' => $preview['subject'],
            'template_code' => $templateModel->code,
            'driver' => (string) $config->driver,
            'status' => $queue ? 'queued' : 'pending',
            'variables_json' => $preview['variables'],
            'body_html' => $preview['body_html'],
            'body_text' => $preview['body_text'],
            'queued_at' => $queue ? now() : null,
            'attempts' => 0,
            'next_retry_at' => null,
            'is_test' => (bool) ($options['is_test'] ?? false),
            'trigger_source' => (string) ($options['trigger_source'] ?? 'system'),
            'original_recipient' => $originalRecipient,
        ]);

        if (($preview['variables']['mail']['sandbox'] ?? false) === true) {
            $this->logAudit('mailer.sandbox_redirect', 'Email redirige vers sandbox', [
                'history_id' => $history->id,
                'original_recipient' => $recipient,
                'sandbox_recipient' => $recipientForDelivery,
            ]);
        }

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
        $provider = (string) ($historyModel->driver ?: $config->driver);

        if (!$config->is_enabled && $provider !== 'log') {
            return $this->markHistoryFailed($historyModel, 'Mailer desactive.');
        }

        try {
            $historyModel->attempts = (int) $historyModel->attempts + 1;
            $historyModel->status = 'sending';
            $historyModel->driver = $provider;
            $historyModel->next_retry_at = null;
            $historyModel->failure_class = null;
            $historyModel->save();

            $mailable = new TemplatedMail(
                mailSubject: (string) $historyModel->subject,
                htmlContent: (string) ($historyModel->body_html ?? ''),
                textContent: (string) ($historyModel->body_text ?? ''),
                fromEmail: $config->from_email ?: config('mail.from.address'),
                fromName: $config->from_name ?: config('mail.from.name'),
                replyToEmail: $config->reply_to_email,
            );

            Mail::mailer($provider)
                ->to($historyModel->recipient, $historyModel->recipient_name)
                ->send($mailable);

            $historyModel->status = 'sent';
            $historyModel->sent_at = now();
            $historyModel->error_message = null;
            $historyModel->failed_at = null;
            $historyModel->next_retry_at = null;
            $historyModel->failure_class = null;
            $historyModel->save();

            $this->logAudit('mailer.sent', 'Email envoye', [
                'history_id' => $historyModel->id,
                'recipient' => $historyModel->recipient,
                'template_code' => $historyModel->template_code,
                'status' => $historyModel->status,
                'driver' => $provider,
            ]);
        } catch (\Throwable $throwable) {
            return $this->handleDeliveryFailure($historyModel, $config, $throwable);
        }

        return $historyModel;
    }

    public function retryHistory(MailerHistory|int $history, bool $queue = true): MailerHistory
    {
        $historyModel = $history instanceof MailerHistory ? $history : MailerHistory::query()->findOrFail($history);

        $historyModel->status = $queue ? 'queued' : 'pending';
        $historyModel->queued_at = now();
        $historyModel->next_retry_at = null;
        $historyModel->failed_at = null;
        $historyModel->error_message = null;
        $historyModel->failure_class = null;
        $historyModel->save();

        $this->logAudit('mailer.retry.requested', 'Relance email demandee', [
            'history_id' => $historyModel->id,
            'recipient' => $historyModel->recipient,
            'template_code' => $historyModel->template_code,
        ]);

        if ($queue) {
            SendTemplatedMailJob::dispatch($historyModel->id)->onQueue('mail');

            return $historyModel->fresh();
        }

        return $this->deliverHistory($historyModel);
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

    private function markHistoryFailed(MailerHistory $history, string $message, ?string $failureClass = null): MailerHistory
    {
        $history->status = 'failed';
        $history->failed_at = now();
        $history->next_retry_at = null;
        $history->error_message = Str::limit($message, 65535, '');
        $history->failure_class = $failureClass;
        $history->save();

        $this->logAudit('mailer.failed', 'Echec envoi email', [
            'history_id' => $history->id,
            'recipient' => $history->recipient,
            'template_code' => $history->template_code,
            'error' => $message,
            'failure_class' => $failureClass,
        ], 'warning');

        $this->triggerFailureAlert($history, $message, terminal: true);

        return $history;
    }

    private function handleDeliveryFailure(MailerHistory $history, MailerConfig $config, \Throwable $throwable): MailerHistory
    {
        $message = $throwable->getMessage() !== '' ? $throwable->getMessage() : get_class($throwable);
        $failureClass = get_class($throwable);

        if ($this->shouldRetry($history, $config, $message)) {
            $delaySeconds = $this->retryDelaySeconds($history, $config);

            $history->status = 'retrying';
            $history->failed_at = now();
            $history->next_retry_at = now()->addSeconds($delaySeconds);
            $history->error_message = Str::limit($message, 65535, '');
            $history->failure_class = $failureClass;

            if ($config->fallback_driver && $history->driver !== $config->fallback_driver) {
                $history->driver = $config->fallback_driver;
            }

            $history->save();

            SendTemplatedMailJob::dispatch($history->id)
                ->delay(now()->addSeconds($delaySeconds))
                ->onQueue('mail');

            $this->logAudit('mailer.retrying', 'Email programme pour retry', [
                'history_id' => $history->id,
                'recipient' => $history->recipient,
                'template_code' => $history->template_code,
                'attempts' => $history->attempts,
                'next_retry_at' => optional($history->next_retry_at)->toIso8601String(),
                'driver' => $history->driver,
                'error' => $message,
            ], 'warning');

            $this->triggerFailureAlert($history, $message, terminal: false);

            return $history;
        }

        return $this->markHistoryFailed($history, $message, $failureClass);
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

    private function renderTemplateString(string $content, array $variables): string
    {
        $normalized = $this->normalizeDotNotationVariables($content);
        $renderContext = array_merge($variables, ['__catmin' => $variables]);

        try {
            return (string) Blade::render($normalized, $renderContext, deleteCachedView: true);
        } catch (\Throwable) {
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
    }

    private function normalizeDotNotationVariables(string $content): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)+)\s*\}\}/', function (array $matches): string {
            $key = (string) ($matches[1] ?? '');

            return "{{ data_get(\$__catmin, '" . addslashes($key) . "') }}";
        }, $content);
    }

    private function applyBrandingTemplate(string $bodyHtml, MailerConfig $config, array $variables): string
    {
        $content = trim($bodyHtml) !== '' ? $bodyHtml : '<p>' . e((string) data_get($variables, 'app.name', 'CATMIN')) . '</p>';
        $brandPrimary = (string) ($config->brand_primary_color ?: '#0d6efd');
        $brandName = (string) ($config->brand_name ?: $config->from_name ?: config('app.name', 'CATMIN'));

        $wrapper = <<<'BLADE'
<div style="font-family: Arial, Helvetica, sans-serif; background:#f6f8fb; padding:24px; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #e5e7eb;">
        <tr>
            <td style="padding:18px 24px; border-bottom:3px solid {{ $brandPrimary }};">
                @if($brandLogoUrl)
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" style="max-height:42px; display:block; margin-bottom:8px;">
                @endif
                <p style="margin:0; font-size:18px; font-weight:700; color:#111827;">{{ $brandName }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">{!! $content !!}</td>
        </tr>
        @if($sandbox)
            <tr>
                <td style="padding:0 24px 16px;">
                    <p style="margin:0; font-size:12px; color:#b45309;">Mode sandbox actif: destinataire original {{ $sandboxOriginal }} redirige vers {{ $sandboxRecipient }}.</p>
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding:14px 24px; background:#f9fafb; border-top:1px solid #e5e7eb; font-size:12px; color:#6b7280;">
                {{ $brandFooterText ?: 'Email genere automatiquement par CATMIN.' }}
            </td>
        </tr>
    </table>
</div>
BLADE;

        return (string) Blade::render($wrapper, [
            'content' => $content,
            'brandPrimary' => $brandPrimary,
            'brandName' => $brandName,
            'brandLogoUrl' => $config->brand_logo_url,
            'brandFooterText' => $config->brand_footer_text,
            'sandbox' => (bool) data_get($variables, 'mail.sandbox', false),
            'sandboxOriginal' => (string) data_get($variables, 'mail.original_recipient', ''),
            'sandboxRecipient' => (string) data_get($variables, 'mail.sandbox_recipient', ''),
        ], deleteCachedView: true);
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

    private function shouldRetry(MailerHistory $history, MailerConfig $config, string $message): bool
    {
        $maxAttempts = max(1, (int) ($config->retry_max_attempts ?? config('catmin.mailer.retry.max_attempts', 3)));

        if ((int) $history->attempts >= $maxAttempts) {
            return false;
        }

        return !$this->isTerminalFailure($message);
    }

    private function retryDelaySeconds(MailerHistory $history, MailerConfig $config): int
    {
        $base = max(5, (int) ($config->retry_backoff_seconds ?? config('catmin.mailer.retry.backoff_seconds', 60)));
        $attempt = max(1, (int) $history->attempts);

        return $base * (2 ** max(0, $attempt - 1));
    }

    private function isTerminalFailure(string $message): bool
    {
        $normalized = Str::lower($message);

        foreach (['invalid address', 'invalid recipient', 'unknown user', 'recipient address rejected', '550 ', 'mailbox unavailable'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function triggerFailureAlert(MailerHistory $history, string $message, bool $terminal): void
    {
        $threshold = max(1, (int) ($this->getOrCreateConfig()->failure_alert_threshold ?? config('catmin.mailer.failure_alert_threshold', 5)));
        $failedLastHour = MailerHistory::query()
            ->whereIn('status', ['failed', 'retrying'])
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        if ($failedLastHour < $threshold) {
            return;
        }

        $cacheKey = 'mailer:failure-alert:' . now()->format('YmdH');
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(15));

        $severity = $terminal ? 'critical' : 'warning';
        $this->alertingService->createAlert(
            'mailer_failure',
            'Mailer failures threshold reached',
            sprintf('Mailer failures/retries reached %d in the last hour. Latest template=%s recipient=%s', $failedLastHour, (string) ($history->template_code ?: 'manual'), (string) $history->recipient),
            [
                'history_id' => $history->id,
                'template_code' => $history->template_code,
                'recipient' => $history->recipient,
                'failed_last_hour' => $failedLastHour,
                'error' => Str::limit($message, 500, ''),
                'status' => $history->status,
            ],
            $severity
        );
    }
}
