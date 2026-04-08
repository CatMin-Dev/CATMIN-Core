<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/notifications-repository.php';

final class CoreNotificationsDispatcher
{
    public function __construct(private readonly CoreNotificationsRepository $repository = new CoreNotificationsRepository()) {}

    public function push(array $payload): bool
    {
        return $this->repository->push($payload);
    }
}

