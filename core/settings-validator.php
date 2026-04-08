<?php

declare(strict_types=1);

final class CoreSettingsValidator
{
    public function validate(string $key, mixed $value, array $schema): array
    {
        $type = strtolower(trim((string) ($schema['type'] ?? 'string')));

        return match ($type) {
            'string' => $this->validateString($value),
            'int' => $this->validateInt($value),
            'bool' => $this->validateBool($value),
            'json' => $this->validateJson($value),
            'array' => $this->validateArray($value),
            'email' => $this->validateEmail($value),
            'url' => $this->validateUrl($value),
            'path' => $this->validatePath($value),
            'enum' => $this->validateEnum($value, $schema),
            default => ['valid' => false, 'value' => null, 'error' => 'Type de setting non supporte: ' . $type],
        };
    }

    private function validateString(mixed $value): array
    {
        return ['valid' => true, 'value' => trim((string) $value), 'error' => null];
    }

    private function validateInt(mixed $value): array
    {
        if (is_int($value)) {
            return ['valid' => true, 'value' => $value, 'error' => null];
        }
        if (is_numeric($value)) {
            return ['valid' => true, 'value' => (int) $value, 'error' => null];
        }

        return ['valid' => false, 'value' => null, 'error' => 'Valeur int invalide'];
    }

    private function validateBool(mixed $value): array
    {
        if (is_bool($value)) {
            return ['valid' => true, 'value' => $value, 'error' => null];
        }
        if (is_int($value) || is_string($value)) {
            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized !== null) {
                return ['valid' => true, 'value' => $normalized, 'error' => null];
            }
        }

        return ['valid' => false, 'value' => null, 'error' => 'Valeur bool invalide'];
    }

    private function validateJson(mixed $value): array
    {
        if (is_array($value)) {
            return ['valid' => true, 'value' => $value, 'error' => null];
        }

        if (!is_string($value)) {
            return ['valid' => false, 'value' => null, 'error' => 'Valeur json invalide'];
        }

        $raw = trim($value);
        if ($raw === '') {
            return ['valid' => true, 'value' => null, 'error' => null];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['valid' => false, 'value' => null, 'error' => 'JSON invalide'];
        }

        return ['valid' => true, 'value' => $decoded, 'error' => null];
    }

    private function validateArray(mixed $value): array
    {
        if (is_array($value)) {
            return ['valid' => true, 'value' => $value, 'error' => null];
        }
        return ['valid' => false, 'value' => null, 'error' => 'Valeur array invalide'];
    }

    private function validateEmail(mixed $value): array
    {
        $str = trim((string) $value);
        if ($str === '' || filter_var($str, FILTER_VALIDATE_EMAIL) !== false) {
            return ['valid' => true, 'value' => $str, 'error' => null];
        }
        return ['valid' => false, 'value' => null, 'error' => 'Email invalide'];
    }

    private function validateUrl(mixed $value): array
    {
        $str = trim((string) $value);
        if ($str === '' || filter_var($str, FILTER_VALIDATE_URL) !== false) {
            return ['valid' => true, 'value' => $str, 'error' => null];
        }
        return ['valid' => false, 'value' => null, 'error' => 'URL invalide'];
    }

    private function validatePath(mixed $value): array
    {
        $str = trim((string) $value, " \t\n\r\0\x0B/");
        if ($str === '' || str_contains($str, '..') || preg_match('/[^a-zA-Z0-9\-_\/]/', $str) === 1) {
            return ['valid' => false, 'value' => null, 'error' => 'Path invalide'];
        }
        return ['valid' => true, 'value' => $str, 'error' => null];
    }

    private function validateEnum(mixed $value, array $schema): array
    {
        $allowed = isset($schema['enum']) && is_array($schema['enum']) ? $schema['enum'] : [];
        $normalized = trim((string) $value);
        if (in_array($normalized, $allowed, true)) {
            return ['valid' => true, 'value' => $normalized, 'error' => null];
        }
        return ['valid' => false, 'value' => null, 'error' => 'Valeur enum invalide'];
    }
}

