<?php

namespace Modules\Mailer\Services;

use Illuminate\Support\Str;
use Modules\Mailer\Models\MailerConfig;
use Modules\Mailer\Models\MailerHistory;
use Modules\Mailer\Models\MailerTemplate;

class MailerAdminService
{
    public function getOrCreateConfig(): MailerConfig
    {
        /** @var MailerConfig $config */
        $config = MailerConfig::query()->firstOrCreate([], [
            'driver' => 'smtp',
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
            'driver' => (string) ($payload['driver'] ?? 'smtp'),
            'from_email' => $payload['from_email'] ?: null,
            'from_name' => $payload['from_name'] ?: null,
            'reply_to_email' => $payload['reply_to_email'] ?: null,
            'is_enabled' => (bool) ($payload['is_enabled'] ?? false),
        ]);
        $config->save();

        return $config;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, MailerTemplate>
     */
    public function templateListing()
    {
        return MailerTemplate::query()->orderBy('name')->get();
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
            'subject' => (string) $payload['subject'],
            'body_html' => (string) ($payload['body_html'] ?? ''),
            'body_text' => (string) ($payload['body_text'] ?? ''),
            'is_enabled' => (bool) ($payload['is_enabled'] ?? true),
        ]);

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
            'subject' => (string) $payload['subject'],
            'body_html' => (string) ($payload['body_html'] ?? ''),
            'body_text' => (string) ($payload['body_text'] ?? ''),
            'is_enabled' => (bool) ($payload['is_enabled'] ?? true),
        ]);
        $template->save();

        return $template;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, MailerHistory>
     */
    public function historyListing()
    {
        return MailerHistory::query()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
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
