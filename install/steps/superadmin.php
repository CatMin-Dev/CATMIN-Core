<?php

declare(strict_types=1);

return [
    'title' => 'SuperAdmin',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $username = trim((string) ($input['username'] ?? 'superadmin'));
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Valid email required.', 'data' => []];
        }

        if (strlen($password) < 10) {
            return ['ok' => false, 'message' => 'Password must be at least 10 characters.', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]];
    },
];
