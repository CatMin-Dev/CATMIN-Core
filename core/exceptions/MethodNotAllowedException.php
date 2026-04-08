<?php

declare(strict_types=1);

final class MethodNotAllowedException extends RuntimeException
{
    /** @param array<int, string> $allowed */
    public function __construct(private readonly array $allowed, string $message = 'Method Not Allowed')
    {
        parent::__construct($message);
    }

    /** @return array<int, string> */
    public function allowedMethods(): array
    {
        return $this->allowed;
    }
}
