<?php

declare(strict_types=1);

namespace DataForge\Tests\Feature;

use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Repository\ActivityRepository;
use DataForge\Service\LiveFeed;
use DataForge\Tests\Support\DatabaseTestCase;

/**
 * Canal de eventos en vivo: snapshot inicial y entradas nuevas.
 */
final class LiveFeedTest extends DatabaseTestCase
{
    private const SYSTEM_DB = 'dataforge_live_test';

    private static int $userId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!self::isAvailable()) {
            return;
        }

        $server = self::adminConnections()->server();
        $server->exec('DROP DATABASE IF EXISTS `'.self::SYSTEM_DB.'`');
        $server->exec('CREATE DATABASE `'.self::SYSTEM_DB.'` CHARACTER SET utf8mb4');

        $connections = self::connectionsFor(self::SYSTEM_DB);
        (new \DataForge\Database\Migrator(
            $connections,
            dirname(__DIR__, 2).'/database/migrations',
            $connections->system(),
        ))->migrate();

        $connection = $connections->system();
        $connection->exec(
            "INSERT INTO `users` (`username`, `email`, `password_hash`) VALUES ('vivo', 'vivo@example.com', 'x')",
        );
        self::$userId = (int) $connection->lastInsertId();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::isAvailable()) {
            self::adminConnections()->server()->exec('DROP DATABASE IF EXISTS `'.self::SYSTEM_DB.'`');
        }

        parent::tearDownAfterClass();
    }

    private function feed(): LiveFeed
    {
        $connections = self::connectionsFor(self::SYSTEM_DB);

        return new LiveFeed(
            new ActivityRepository($connections->system()),
            new \DataForge\Service\DatabaseService(
                self::adminConnections(),
                new \DataForge\Repository\ManagedDatabaseRepository($connections->system()),
                new ActivityRepository($connections->system()),
                new \DataForge\Support\Logger(sys_get_temp_dir().'/dataforge-test-logs'),
            ),
        );
    }

    public function testElSnapshotIncluyeMetricasYBitacora(): void
    {
        $snapshot = $this->feed()->snapshot(self::$userId);

        self::assertArrayHasKey('metrics', $snapshot);
        self::assertArrayHasKey('entries', $snapshot);
        self::assertArrayHasKey('databases', $snapshot['metrics']);
        self::assertArrayHasKey('tables', $snapshot['metrics']);
        self::assertArrayHasKey('rows', $snapshot['metrics']);
        self::assertSame(0, $snapshot['metrics']['databases']);
    }

    public function testDetectaLasEntradasNuevas(): void
    {
        $connections = self::connectionsFor(self::SYSTEM_DB);
        $activity    = new ActivityRepository($connections->system());

        $before = $this->feed()->snapshot(self::$userId);

        $activity->record(self::$userId, 'create', 'tabla', 'ventas', 'tienda');

        $update = $this->feed()->since(self::$userId, (int) $before['lastId']);

        self::assertCount(1, $update['entries']);
        self::assertSame('ventas', $update['entries'][0]['target_name']);
        self::assertGreaterThan((int) $before['lastId'], (int) $update['lastId']);
    }

    public function testNoRepiteEntradasYaEnviadas(): void
    {
        $connections = self::connectionsFor(self::SYSTEM_DB);
        $activity    = new ActivityRepository($connections->system());

        $activity->record(self::$userId, 'create', 'tabla', 'clientes', 'tienda');

        $snapshot = $this->feed()->snapshot(self::$userId);
        $update   = $this->feed()->since(self::$userId, (int) $snapshot['lastId']);

        self::assertSame([], $update['entries']);
    }

    public function testLaRutaDeEventosExigeSesion(): void
    {
        $application = new \DataForge\Core\Application(dirname(__DIR__, 2), self::config());

        $response = $application->handle(Request::create('GET', '/events'));

        self::assertSame(302, $response->status());
        self::assertStringContainsString('/login', $response->headers()['Location']);
    }

    public function testLaRespuestaDeEventosEsUnFlujo(): void
    {
        $response = Response::stream(static function (): void {
            echo 'event: ping';
        });

        self::assertSame(200, $response->status());
        self::assertStringContainsString('text/event-stream', $response->headers()['Content-Type']);
        self::assertSame('no-cache, no-store, no-transform', $response->headers()['Cache-Control']);
    }

    /**
     * @return \DataForge\Core\Config
     */
    private static function config(): \DataForge\Core\Config
    {
        $root = dirname(__DIR__, 2);

        return new \DataForge\Core\Config([
            'app' => [
                'name'    => 'DataForge Test',
                'env'     => 'testing',
                'debug'   => true,
                'version' => '4.0.0',
                'url'     => 'http://localhost',
                'paths'   => [
                    'root'       => $root,
                    'templates'  => $root.'/templates',
                    'storage'    => $root.'/storage',
                    'logs'       => sys_get_temp_dir().'/dataforge-live-logs',
                    'cache'      => sys_get_temp_dir().'/dataforge-live-cache',
                    'framework'  => $root.'/storage/framework',
                    'uploads'    => sys_get_temp_dir().'/dataforge-live-uploads',
                    'migrations' => $root.'/database/migrations',
                ],
            ],
            'database' => [
                'host'            => self::setting('DB_HOST', '127.0.0.1'),
                'port'            => (int) self::setting('DB_PORT', '3307'),
                'username'        => self::setting('DB_ADMIN_USER', 'root'),
                'password'        => self::setting('DB_ADMIN_PASS', 'root_secret'),
                'charset'         => 'utf8mb4',
                'system_database' => self::SYSTEM_DB,
                'ssl_ca'          => null,
                'ssl_verify'      => true,
            ],
            'security' => [
                'session' => [
                    'idle_timeout'     => 1800,
                    'absolute_timeout' => 28800,
                ],
                'rate_limit' => [
                    'storage' => 'file',
                    'global'  => ['max' => 1000, 'window' => 60],
                    'login'   => ['max' => 50, 'window' => 60],
                ],
                'redis'           => ['host' => '127.0.0.1', 'port' => 6379, 'password' => null],
                'trusted_proxies' => [],
                'query'           => ['max_rows' => 100, 'timeout' => 5],
                'avatar'          => ['max_bytes' => 2_097_152],
            ],
        ]);
    }
}
