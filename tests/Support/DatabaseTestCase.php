<?php

declare(strict_types=1);

namespace DataForge\Tests\Support;

use DataForge\Database\ConnectionFactory;
use DataForge\Support\Env;
use PHPUnit\Framework\TestCase;

/**
 * Base para las pruebas que necesitan MySQL.
 *
 * Si el servidor no esta disponible la prueba se marca como omitida en lugar
 * de fallar, de modo que la suite siga siendo util sin contenedor.
 *
 * Las pruebas de integracion crean y borran bases de datos propias, asi que
 * necesitan un usuario con permisos de administrador. Se indica con
 * DB_ADMIN_USER y DB_ADMIN_PASS; si no se definen, se usan los normales.
 */
abstract class DatabaseTestCase extends TestCase
{
    private static bool $available = false;

    private static string $host = '127.0.0.1';

    private static int $port = 3307;

    private static string $username = 'dataforge';

    private static string $password = 'dataforge_secret';

    private static string $adminUsername = 'dataforge';

    private static string $adminPassword = 'dataforge_secret';

    private static string $systemDatabase = 'dataforge_test';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$host           = self::setting('DB_HOST', '127.0.0.1');
        self::$port           = (int) self::setting('DB_PORT', '3307');
        self::$username       = self::setting('DB_USER', 'dataforge');
        self::$password       = self::setting('DB_PASS', 'dataforge_secret');
        self::$adminUsername  = self::setting('DB_ADMIN_USER', self::$username);
        self::$adminPassword  = self::setting('DB_ADMIN_PASS', self::$password);
        self::$systemDatabase = self::setting('SYSTEM_DB', 'dataforge_test');

        try {
            self::ensureSystemDatabase();
            self::$available = true;
        } catch (\PDOException) {
            self::$available = false;
        }
    }

    /**
     * Crea la base de datos de sistema de las pruebas si no existe.
     */
    private static function ensureSystemDatabase(): void
    {
        $name = self::$systemDatabase;

        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)) {
            return;
        }

        $server = self::adminConnections()->server();
        $server->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4', $name));
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$available) {
            $this->markTestSkipped('MySQL no esta disponible.');
        }
    }

    public static function isAvailable(): bool
    {
        return self::$available;
    }

    /**
     * Conexion con los permisos de la aplicacion.
     */
    protected static function connections(): ConnectionFactory
    {
        return new ConnectionFactory(
            self::$host,
            self::$port,
            self::$username,
            self::$password,
            'utf8mb4',
            self::$systemDatabase,
        );
    }

    /**
     * Conexion con permisos de administrador, para crear y borrar las bases de
     * datos que usan las pruebas.
     */
    protected static function adminConnections(): ConnectionFactory
    {
        return new ConnectionFactory(
            self::$host,
            self::$port,
            self::$adminUsername,
            self::$adminPassword,
            'utf8mb4',
            self::$systemDatabase,
        );
    }

    /**
     * Fabrica que apunta a otra base de datos de sistema, para que cada
     * prueba trabaje sobre su propio esquema.
     */
    protected static function connectionsFor(string $systemDatabase): ConnectionFactory
    {
        return new ConnectionFactory(
            self::$host,
            self::$port,
            self::$adminUsername,
            self::$adminPassword,
            'utf8mb4',
            $systemDatabase,
        );
    }

    protected static function setting(string $key, string $default): string
    {
        $value = Env::get($key);

        return $value ?? $default;
    }
}
