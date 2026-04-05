<?php

declare(strict_types=1);

namespace Core\auth;

use Core\config\Config;

final class ReAuthManager
{
    public function __construct(private readonly SessionManager $sessions) {}

    public function isRecent(): bool
    {
        $ttl = (int) Config::get('security.reauth_ttl_seconds', 900);
        $last = $this->sessions->lastReauthAt();

        return is_int($last) && (time() - $last) <= $ttl;
    }

    public function markValidated(): void
    {
        $this->sessions->markReauthenticated();
    }
}
