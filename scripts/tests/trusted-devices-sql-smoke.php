<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Core\security\TrustedDeviceManager;
use Core\security\TrustedDeviceRepository;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, "[FAIL] " . $message . PHP_EOL);
        exit(1);
    }

    echo "[OK] " . $message . PHP_EOL;
};

$repository = new TrustedDeviceRepository();

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'catmin-trusted-device-smoke';

$userId = 999001;
$manager = new TrustedDeviceManager($repository);
$token = $manager->issue($userId);
$assert($token !== '', 'issue returns token');

$_COOKIE['catmin_trusted_device'] = $token;
$assert($manager->isTrusted($userId), 'trusted device is resolved from SQL storage');

$active = $repository->listActiveDevicesByUser($userId);
$assert(is_array($active) && count($active) >= 1, 'repository lists active trusted devices');

$assert($manager->revokeByToken($userId, $token), 'trusted device can be revoked by token');
$assert(!$manager->isTrusted($userId), 'revoked trusted device is no longer accepted');

echo "[DONE] trusted devices SQL smoke tests" . PHP_EOL;