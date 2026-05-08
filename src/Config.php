<?php
/**
 * ============================================
 * DataForge — Config Class
 * ============================================
 *
 * Object-oriented wrapper for application configuration.
 * Provides static access to all configuration values.
 *
 * @package  DataForge
 * @version  3.2.0
 */

namespace DataForge;

class Config
{
    /** @var array<string, mixed> Loaded environment values */
    private static array $values = [];

    /** @var bool Whether config has been loaded */
    private static bool $loaded = false;

    /**
     * Load environment configuration.
     * Called automatically by config.php — this class wraps the logic
     * for programmatic access from tests and external tools.
     *
     * @param string|null $envPath Path to .env file (auto-detected if null)
     * @return void
     */
    public static function load(?string $envPath = null): void
    {
        if (self::$loaded) {
            return;
        }

        if ($envPath === null) {
            $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
            $envFile = $root . '/.env';
            $envExample = $root . '/.env.example';
            $envPath = file_exists($envFile) ? $envFile : $envExample;
        }

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    self::$values[$key] = $value;
                }
            }
        }

        // Also capture existing $_ENV values
        foreach ($_ENV as $key => $value) {
            if (!isset(self::$values[$key])) {
                self::$values[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Get a configuration value.
     *
     * @param string $key     Config key
     * @param mixed  $default Default value if key not found
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureLoaded();
        return self::$values[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Check if running in production environment.
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'development') === 'production';
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public static function isDebug(): bool
    {
        return filter_var(self::get('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get database configuration as an associative array.
     *
     * @return array{host: string, port: int, user: string, pass: string, charset: string, system_db: string}
     */
    public static function getDatabaseConfig(): array
    {
        return [
            'host'      => self::get('DB_HOST', 'localhost'),
            'port'      => (int) self::get('DB_PORT', 3306),
            'user'      => self::get('DB_USER', 'root'),
            'pass'      => self::get('DB_PASS', ''),
            'charset'   => self::get('DB_CHARSET', 'utf8mb4'),
            'system_db' => self::get('SYSTEM_DB', 'dataforge_system'),
        ];
    }

    /**
     * Get the application version string.
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return self::get('APP_VERSION', '3.2.0');
    }

    /**
     * Get application name.
     *
     * @return string
     */
    public static function getAppName(): string
    {
        return self::get('APP_NAME', 'DataForge CRUD Manager');
    }

    /**
     * Get application URL.
     *
     * @return string
     */
    public static function getAppUrl(): string
    {
        return self::get('APP_URL', 'http://localhost/dataforge');
    }

    /**
     * Ensure config is loaded.
     */
    private static function ensureLoaded(): void
    {
        if (!self::$loaded) {
            self::load();
        }
    }

    /**
     * Reset for testing purposes.
     * @internal
     */
    public static function reset(): void
    {
        self::$values = [];
        self::$loaded = false;
    }
}
