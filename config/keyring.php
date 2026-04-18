<?php

declare(strict_types=1);

return [
    'official' => [
        [
            'key_id' => 'catmin-official-main-2026',
            'publisher' => 'catmin-dev',
            'algorithm' => 'rsa-sha256',
            'scope' => 'official',
            'status' => 'active',
            'source' => 'embedded',
            'created_at' => '2026-04-14T00:00:00Z',
            'deprecated_at' => null,
            'revoked_at' => null,
            'public_key' => "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA4JHlloOoj9rloOrD/K25\neRn8VFbyW480BiI4iR62SsY0/t75KLDh4P2AYyhjk9NKWJ19Bt2cwpa2Mjmmf2Id\nq8T64eZabfmaGV/J3hU/qyJ0sMO0E/SuPgi/df8G4s8tqpBHmv3UG7JVX1UYphmT\ncnORxGraMd66BeP1PCitDPrh+wIJd1H7hNNYRRzCmhPwXSTFrPpfRjogohLLd3o2\nHenstf+iWXVdleYIxQjm/GLnNbjG0Q4NMoiJ2aBGW2T/ogtcn5yeUTxLb+IHdq1R\n4V5Jp5Bfg17uZemvxNdffjqj+Ag99cAw2IdaQsTzY1ndK8C3yE5OCOMp/ku6zsiX\naci3la+Y012r/hj6uMH6Cy2msYR/SpYbJANAAu8/6ojzMy+mJ5nRVbN4he9iXKZw\nUba2l4OjXqhEXyWCYeD5DmKScLQ+jqz6d5iTc68Czw7crIk2xVdILtJMB+eDATJg\nlDFU3a/0a79bob2JFg5GCPCd9KH+G76RwcX8DooaQyR7paCCLksYS3p2tcqXCxKI\nb58MaytpaKpe5fKo9WXcXTzIdy+ZNj2le+M9ZJYZP8LlinZMtCONBTFd2rOmrA12\nzS6R+I/ldUFI92H6WVS4hgNL86I7/nc6xu2tKsdv5hPEkZFiXP/mybxT4bGZjDm6\npOjv/0gvMzhgMfgpwVi7LhcCAwEAAQ==\n-----END PUBLIC KEY-----",
        ],
    ],
    'trusted' => [],
    'community' => [
        [
            'key_id' => 'catmin-local-media-20260415',
            'publisher' => 'catmin-dev',
            'algorithm' => 'rsa-sha256',
            'scope' => 'community',
            'status' => 'active',
            'source' => 'embedded',
            'created_at' => '2026-04-15T00:00:00Z',
            'deprecated_at' => null,
            'revoked_at' => null,
            'public_key' => "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAmn/dukej2vlt74dhLCzV\ncDjma8AtOoy9/qJSW1iJaSMgtHqCt5k2ybWDucHI818C/UgcVHNWBXNk1sG9o9EQ\nHEcm4/mxuXLJ6MjLyRXodCLywPTLnj7kSEFMeLw1+A9X7B7Rv2I8dVw0zMAPb5bD\nMB6taV15ym/d3F1jNJFvw4/IqwFf7CIyfed673xSb7HnhS0ZNjitMUjCZva/oOxT\nXJab89lu/9XXOc5ZteKswksD4El3VUuHTmL6MxAtOMazsRLmdobGAnrQKRfkgIPm\nqN8aPl///RpOmp88tKtlZVXWPkkBg/ic/PN2aYcsFQJ2N9vO+6XnmQHh3xPXa4I4\nnQIAPcYYaX3dbEungjMGeQviRIl71aWExn1AeyQfJVbNwE7vGmfN7yMsyBKtCnk6\ni5wMZkA5EhkveE/587ffsxo1g08cGsXVTPHhBKoH29/OUjPqdU0vzSllhruid/lw\neHYoA0J8MvXGiyzAJu7OXRe0qyNU0yLatX/Qi4qVLAJLoO75TgpeCVLYRIvylOHs\nMjPss+kKCX5v4bWqql/o0CrrM3qSATRZioPKe+FyJmWgDfcFbopRpHEc6p7eCrV1\n2O5mfIXtbMTM13+dYlh1vYt5qSYnTZQ1tbDvEE9ozQigDw41nlRM6ryGprIpHnYm\noQdtPXJZPDeLl4fQ0wR+U3ECAwEAAQ==\n-----END PUBLIC KEY-----",
        ],
    ],
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
        'base_url' => 'https://keyring.catmin.dev',
        'keyring_url' => 'https://keyring.catmin.dev/keyring.json',
        'registry_url' => 'https://keyring.catmin.dev/trust-registry.json',
        'revocations_url' => 'https://keyring.catmin.dev/revocations.json',
        'publishers_url' => 'https://keyring.catmin.dev/publishers.json',
        'metadata_url' => 'https://keyring.catmin.dev/metadata.json',
        'timeout' => 5,
    ],
];
