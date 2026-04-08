<?php

declare(strict_types=1);

final class CoreAppsValidator
{
    public function validate(array $payload): array
    {
        $label = trim((string) ($payload['label'] ?? ''));
        $url = trim((string) ($payload['url'] ?? ''));
        $type = strtolower(trim((string) ($payload['type'] ?? 'external')));
        $target = strtolower(trim((string) ($payload['target'] ?? '_blank')));
        $icon = trim((string) ($payload['icon'] ?? ''));

        $errors = [];
        if ($label === '') {
            $errors[] = 'Label requis.';
        }

        if ($url === '') {
            $errors[] = 'URL requise.';
        } elseif (!$this->isUrlValid($url, $type)) {
            $errors[] = 'URL invalide pour ce type.';
        }

        if (!in_array($type, ['internal', 'external'], true)) {
            $errors[] = 'Type invalide.';
        }

        if (!in_array($target, ['_self', '_blank'], true)) {
            $errors[] = 'Target invalide.';
        }

        if (mb_strlen($label) > 120) {
            $errors[] = 'Label trop long (120 max).';
        }

        if ($icon !== '' && mb_strlen($icon) > 255) {
            $errors[] = 'Icône trop longue (255 max).';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'label' => mb_substr($label, 0, 120),
                'icon' => $icon !== '' ? mb_substr($icon, 0, 255) : null,
                'url' => mb_substr($url, 0, 255),
                'type' => $type,
                'target' => $target,
                'is_enabled' => ((string) ($payload['is_enabled'] ?? '0')) === '1',
                'sort_order' => max(1, (int) ($payload['sort_order'] ?? 100)),
            ],
        ];
    }

    private function isUrlValid(string $url, string $type): bool
    {
        if ($type === 'internal') {
            return str_starts_with($url, '/');
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
