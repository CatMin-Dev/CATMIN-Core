<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/notifications-repository.php';
require_once CATMIN_CORE . '/notifications-presenter.php';

final class CoreNotificationsBridge
{
    public function __construct(
        private readonly CoreNotificationsRepository $repository = new CoreNotificationsRepository(),
        private readonly CoreNotificationsPresenter $presenter = new CoreNotificationsPresenter()
    ) {}

    public function topbarPayload(int $limit = 8): array
    {
        $rows = $this->repository->listRecent($limit);
        return [
            'unread' => $this->repository->countUnread(),
            'items' => array_map(function (array $row): array {
                $row['badge_class'] = $this->presenter->badgeClass((string) ($row['type'] ?? 'info'));
                $row['type_label'] = $this->presenter->label((string) ($row['type'] ?? 'info'));
                return $row;
            }, $rows),
        ];
    }
}
