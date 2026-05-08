<?php
/**
 * ============================================
 * DataForge CRUD Manager — Global Configuration
 * ============================================
 *
 * Centralized configuration file. Reads settings from `.env` file
 * or falls back to `.env.example` defaults.
 *
 * @package  DataForge
 * @version  3.2.0
 * @author   Raju Technology
 * @license  MIT
 */

// ─── Secure Session Configuration ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly'  => true,
        'samesite'  => 'Strict',
    ]);

    session_start();
}

// ─── Load Environment Variables ────────────────────────────────────────────
$envFile = __DIR__ . '/.env';
$envExampleFile = __DIR__ . '/.env.example';

$envPath = file_exists($envFile) ? $envFile : $envExampleFile;

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            
            // Only set if not already present in environment (e.g. from Render)
            if (getenv($key) === false && !isset($_SERVER[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

// ─── Parse MYSQL_URL for cloud providers (TiDB, PlanetScale, etc.) ─────────
$mysqlUrl = $_ENV['MYSQL_URL'] ?? getenv('MYSQL_URL') ?: '';
if (!empty($mysqlUrl)) {
    $parsed = parse_url($mysqlUrl);
    if ($parsed !== false) {
        $_ENV['DB_HOST'] = $parsed['host'] ?? 'localhost';
        $_ENV['DB_PORT'] = (string) ($parsed['port'] ?? 3306);
        $_ENV['DB_USER'] = $parsed['user'] ?? 'root';
        $_ENV['DB_PASS'] = $parsed['pass'] ?? '';
        if (!empty($parsed['path'])) {
            $_ENV['SYSTEM_DB'] = ltrim($parsed['path'], '/');
        }
    }
}

// ─── Helper function for environment variables ──────────────────────────────
function getEnvVal(string $key, $default = '') {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    return $default;
}

// ─── Database Constants ────────────────────────────────────────────────────
define('DB_HOST', getEnvVal('DB_HOST', 'localhost'));
define('DB_PORT', getEnvVal('DB_PORT', '3306'));
define('DB_USER', getEnvVal('DB_USER', 'root'));
define('DB_PASS', getEnvVal('DB_PASS', ''));
define('DB_CHARSET', getEnvVal('DB_CHARSET', 'utf8mb4'));

// ─── Application Constants ────────────────────────────────────────────────
define('APP_ENV', getEnvVal('APP_ENV', 'development'));
define('APP_DEBUG', filter_var(getEnvVal('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN));
define('APP_NAME', getEnvVal('APP_NAME', 'DataForge CRUD Manager'));
define('APP_VERSION', getEnvVal('APP_VERSION', '3.2.0'));
define('APP_URL', getEnvVal('APP_URL', 'http://localhost/dataforge'));
define('SYSTEM_DB', getEnvVal('SYSTEM_DB', 'dataforge_system'));

// ─── Path Constants ────────────────────────────────────────────────────────
define('ROOT_PATH', __DIR__);
define('INCLUDES_PATH', __DIR__ . '/includes');
define('DATABASE_PATH', __DIR__ . '/database');
define('TABLES_PATH', __DIR__ . '/tables');
define('RECORDS_PATH', __DIR__ . '/records');
define('AUTH_PATH', __DIR__ . '/auth');
define('ACCOUNT_PATH', __DIR__ . '/account');
define('LOGS_PATH', __DIR__ . '/logs');

// ─── Auto-create logs directory ─────────────────────────────────────────────
if (!is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0750, true);
    file_put_contents(LOGS_PATH . '/.htaccess', 'Deny from all');
}

// ─── Load Autoloader for Classes ───────────────────────────────────────────
if (file_exists(__DIR__ . '/src/autoload.php')) {
    require_once __DIR__ . '/src/autoload.php';
}

// ─── Error Reporting ───────────────────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// ─── Security Headers ─────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (APP_ENV !== 'development') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ─── Load Security Middleware (CSP, Rate Limiting) ─────────────────────────
require_once __DIR__ . '/includes/security.php';
applySecurityHeaders();
applyRateLimit();
cleanupRateLimitFiles();
