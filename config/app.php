<?php

declare(strict_types=1);

use DataForge\Support\Env;

$root = dirname(__DIR__);

return [
    'name'    => Env::get('APP_NAME', 'DataForge'),
    'env'     => Env::get('APP_ENV', 'development'),
    'debug'   => Env::bool('APP_DEBUG', false),
    'version' => Env::get('APP_VERSION', '4.0.0'),
    'url'     => Env::get('APP_URL', 'http://localhost:8080'),

    'paths' => [
        'root'       => $root,
        'templates'  => $root.'/templates',
        'storage'    => $root.'/storage',
        'logs'       => $root.'/storage/logs',
        'cache'      => $root.'/storage/cache',
        'framework'  => $root.'/storage/framework',
        'uploads'    => $root.'/storage/uploads/avatars',
        'migrations' => $root.'/database/migrations',
    ],
];
