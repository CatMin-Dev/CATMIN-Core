<?php

declare(strict_types=1);

final class CoreModulePublicKeyring
{
    public function all(): array
    {
        $keys = config('module-trust.keyring', []);
        return is_array($keys) ? $keys : [];
    }

    public function get(string $keyId): ?string
    {
        $keyId = trim($keyId);
        if ($keyId === '') {
            return null;
        }
        $keys = $this->all();
        $value = $keys[$keyId] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }
}

