<?php

declare(strict_types=1);

return [
    'official' => [
        [
            'key_id' => 'catmin-official-anchor-001',
            'publisher' => 'catmin-dev',
            'algorithm' => 'rsa-sha256',
            'scope' => 'official',
            'source' => 'embedded',
            'public_key' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwMMWGvyFYw+0es/81k7z\nRqLPZyaFcSJgfXXu0gxSu9aYX6ucShWnqTnhaUC7L/do1nnJwoEy4KkV0DqpGYce\nqaAE2HslHOFwKnNn49EHJvta6cUgCI0m4UXRTtooaXyqZNitkri7aWngH4FuDD5U\nAoMMwfRotOzqNDujZnYmHzVjQzSi2jp/EfP1v4TCjE69Ab3OuihmbuqJFGeBHUKb\nO1ni3prD3XMDOPGbqzCmitA5Rd1+09am3s4YZJJM9ejHOYrIAkgT9FsI+GC0wu6j\nHg1VDbCG3TGKZIXg96s0hzc3nHKE+oY+wi5VU4tTURgBulNfDyqAtV5RQiyBFydL\ngQIDAQAB\n-----END PUBLIC KEY-----",
        ],
    ],
    'trusted' => [],
    'community' => [],
    'trusted_publishers' => [
        [
            'publisher' => 'catmin-dev',
            'trust_scope' => 'official',
            'source' => 'embedded',
        ],
    ],
    'revoked' => [],
    'remote' => [
        'enabled' => false,
        'keyring_url' => 'https://keyring.catmin.dev/keyring.json',
        'registry_url' => 'https://keyring.catmin.dev/trust-registry.json',
        'timeout' => 5,
    ],
];
