<?php

declare(strict_types=1);

final class MiddlewareAbortException extends RuntimeException
{
    public function __construct(private readonly int $status = 403, string $message = 'Forbidden')
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }
}
