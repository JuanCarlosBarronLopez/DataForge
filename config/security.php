<?php

declare(strict_types=1);

use DataForge\Support\Env;

$trustedProxies = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) (Env::get('TRUSTED_PROXIES', '') ?? '')),
)));

return [
    'session' => [
        'idle_timeout'     => Env::int('SESSION_IDLE_TIMEOUT', 1800),
        'absolute_timeout' => Env::int('SESSION_ABSOLUTE_TIMEOUT', 28800),
    ],

    'rate_limit' => [
        'storage' => Env::get('RATE_LIMIT_STORAGE', 'file') === 'redis' ? 'redis' : 'file',
        'global'  => [
            'max'    => Env::int('RATE_LIMIT_MAX', 120),
            'window' => Env::int('RATE_LIMIT_WINDOW', 60),
        ],
        'login' => [
            'max'    => Env::int('RATE_LIMIT_LOGIN_MAX', 10),
            'window' => Env::int('RATE_LIMIT_LOGIN_WINDOW', 300),
        ],
    ],

    'redis' => [
        'host'     => Env::get('REDIS_HOST', '127.0.0.1'),
        'port'     => Env::int('REDIS_PORT', 6379),
        'password' => Env::get('REDIS_PASSWORD'),
    ],

    'trusted_proxies' => $trustedProxies,

    'query' => [
        'max_rows' => Env::int('QUERY_MAX_ROWS', 500),
        'timeout'  => Env::int('QUERY_TIMEOUT', 10),
    ],

    'avatar' => [
        'max_bytes' => Env::int('AVATAR_MAX_BYTES', 2_097_152),
    ],
];
