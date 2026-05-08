<?php
/**
 * ============================================
 * DataForge — Database Class
 * ============================================
 *
 * Object-oriented wrapper for database connection
 * and database management operations.
 *
 * @package  DataForge
 * @version  3.2.0
 */

namespace DataForge;

class Database
{
    /** @var array System databases excluded from user operations */
    public const SYSTEM_DATABASES = [
        'information_schema',
        'mysql',
        'performance_schema',
        'sys',
        'phpmyadmin',
        'test',
    ];

    /**
     * Establish a MySQL connection.
     *
     * @param string|null $dbName Database to select
     * @return \mysqli
     * @throws \Exception
     */
    public static function connect(?string $dbName = null): \mysqli
    {
        if (function_exists('getDbConnection')) {
            return getDbConnection($dbName);
        }

        $host = defined('DB_HOST') ? DB_HOST : Config::get('DB_HOST', 'localhost');
        $user = defined('DB_USER') ? DB_USER : Config::get('DB_USER', 'root');
        $pass = defined('DB_PASS') ? DB_PASS : Config::get('DB_PASS', '');
        $port = defined('DB_PORT') ? DB_PORT : Config::get('DB_PORT', 3306);
        $charset = defined('DB_CHARSET') ? DB_CHARSET : Config::get('DB_CHARSET', 'utf8mb4');
        $db = empty($dbName) ? "" : $dbName;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = mysqli_init();
            $flags = 0;
            // Force SSL if connecting to an external cloud database
            if ($host !== 'localhost' && $host !== '127.0.0.1' && $host !== 'db') {
                $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
                $flags = MYSQLI_CLIENT_SSL;
            }

            $conn->real_connect($host, $user, $pass, $db, (int)$port, NULL, $flags);
            $conn->set_charset($charset);
            return $conn;
        } catch (\mysqli_sql_exception $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get a connection to the system database.
     *
     * @return \mysqli
     */
    public static function connectSystem(): \mysqli
    {
        $systemDb = defined('SYSTEM_DB') ? SYSTEM_DB : Config::get('SYSTEM_DB', 'dataforge_system');
        return self::connect($systemDb);
    }

    /**
     * List all user-created databases.
     *
     * @return array<string>
     */
    public static function list(): array
    {
        if (function_exists('getDatabases')) {
            return getDatabases();
        }

        $databases = [];
        try {
            $conn = self::connect();
            $result = $conn->query("SHOW DATABASES");
            while ($row = $result->fetch_assoc()) {
                $name = $row['Database'];
                if (!in_array($name, self::SYSTEM_DATABASES, true)) {
                    $databases[] = $name;
                }
            }
            $conn->close();
        } catch (\Exception $e) {
            error_log("Database::list error: " . $e->getMessage());
        }
        return $databases;
    }

    /**
     * Create a new database.
     *
     * @param string $name Database name
     * @return array{success: bool, message: string}
     */
    public static function create(string $name): array
    {
        if (function_exists('createDatabase')) {
            return createDatabase($name);
        }
        return ['success' => false, 'message' => 'Database functions not loaded.'];
    }

    /**
     * Drop a database.
     *
     * @param string $name Database name
     * @return array{success: bool, message: string}
     */
    public static function drop(string $name): array
    {
        if (function_exists('dropDatabase')) {
            return dropDatabase($name);
        }
        return ['success' => false, 'message' => 'Database functions not loaded.'];
    }

    /**
     * Check if a database name is a protected system database.
     *
     * @param string $name
     * @return bool
     */
    public static function isSystemDatabase(string $name): bool
    {
        return in_array($name, self::SYSTEM_DATABASES, true);
    }
}
