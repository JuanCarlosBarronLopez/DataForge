<?php

declare(strict_types=1);

use DataForge\Support\Env;

$host     = Env::get('DB_HOST', '127.0.0.1');
$port     = Env::int('DB_PORT', 3306);
$username = Env::get('DB_USER', 'root');
$password = Env::get('DB_PASS', '');
$system   = Env::get('SYSTEM_DB', 'dataforge_system');

// Los proveedores gestionados ofrecen una URL unica que concentra los datos
// de conexion (TiDB Cloud, PlanetScale, Railway).
$url = Env::get('MYSQL_URL');
if ($url !== null && $url !== '') {
    $parsed = parse_url($url);

    if (is_array($parsed)) {
        $host     = $parsed['host'] ?? $host;
        $port     = isset($parsed['port']) ? (int) $parsed['port'] : $port;
        $username = isset($parsed['user']) ? rawurldecode((string) $parsed['user']) : $username;
        $password = isset($parsed['pass']) ? rawurldecode((string) $parsed['pass']) : $password;

        if (isset($parsed['path']) && trim((string) $parsed['path'], '/') !== '') {
            $system = trim((string) $parsed['path'], '/');
        }
    }
}

return [
    'host'            => $host,
    'port'            => $port,
    'username'        => $username,
    'password'        => $password,
    'charset'         => Env::get('DB_CHARSET', 'utf8mb4'),
    'system_database' => $system,
    'ssl_ca'          => Env::get('DB_SSL_CA'),
    'ssl_verify'      => Env::bool('DB_SSL_VERIFY', true),
];
