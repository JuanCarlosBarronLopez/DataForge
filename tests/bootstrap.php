<?php
/**
 * ============================================
 * DataForge — PHPUnit Bootstrap
 * ============================================
 *
 * Sets up the test environment with mock session,
 * autoloading, and configuration.
 */

// Prevent "headers already sent" errors in tests
if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

// Start output buffering to capture header() calls
ob_start();

// Start session for CSRF and auth tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set test environment variables
$_ENV['APP_ENV']    = 'testing';
$_ENV['APP_DEBUG']  = 'true';
$_ENV['DB_HOST']    = getenv('DB_HOST') ?: '127.0.0.1';
$_ENV['DB_PORT']    = getenv('DB_PORT') ?: '3307';
$_ENV['DB_USER']    = getenv('DB_USER') ?: 'dataforge';
$_ENV['DB_PASS']    = getenv('DB_PASS') ?: 'dataforge_secret';
$_ENV['SYSTEM_DB']  = getenv('SYSTEM_DB') ?: 'dataforge_test';
$_ENV['DB_CHARSET'] = 'utf8mb4';
$_ENV['RATE_LIMIT_STORAGE'] = 'file';

// Define constants if not already defined
foreach ([
    'DB_HOST'    => $_ENV['DB_HOST'],
    'DB_PORT'    => $_ENV['DB_PORT'],
    'DB_USER'    => $_ENV['DB_USER'],
    'DB_PASS'    => $_ENV['DB_PASS'],
    'DB_CHARSET' => $_ENV['DB_CHARSET'],
    'APP_ENV'    => 'testing',
    'APP_DEBUG'  => true,
    'APP_NAME'   => 'DataForge Test',
    'APP_VERSION'=> '3.2.0',
    'APP_URL'    => 'http://localhost:8080',
    'SYSTEM_DB'  => $_ENV['SYSTEM_DB'],
    'ROOT_PATH'  => dirname(__DIR__),
    'LOGS_PATH'  => dirname(__DIR__) . '/logs',
] as $name => $value) {
    if (!defined($name)) {
        define($name, $value);
    }
}

// Load project files needed for tests
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../database/db_functions.php';
require_once __DIR__ . '/../auth/auth_functions.php';
require_once __DIR__ . '/../records/record_functions.php';
require_once __DIR__ . '/../tables/table_functions.php';
