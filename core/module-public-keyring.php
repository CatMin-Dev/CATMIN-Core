<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/keyring-manager.php';

final class CoreModulePublicKeyring
{
    public function all(): array
    {
        $map = (new CoreKeyringManager())->allPublicMap();

        return is_array($map) ? $map : [];
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

    public function entry(string $keyId): ?array
    {
        $keyId = trim($keyId);
        if ($keyId === '') {
            return null;
        }

        $entry = (new CoreKeyringManager())->find($keyId);

        return is_array($entry) ? $entry : null;
    }
}
