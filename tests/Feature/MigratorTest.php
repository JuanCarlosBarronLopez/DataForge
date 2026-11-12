<?php

declare(strict_types=1);

namespace DataForge\Tests\Feature;

use DataForge\Database\Migrator;
use DataForge\Tests\Support\DatabaseTestCase;

final class MigratorTest extends DatabaseTestCase
{
    private const SYSTEM_DB = 'dataforge_migrate_test';

    private ?\PDO $server = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = self::adminConnections()->server();
        $this->server->exec('DROP DATABASE IF EXISTS `'.self::SYSTEM_DB.'`');
        $this->server->exec('CREATE DATABASE `'.self::SYSTEM_DB.'` CHARACTER SET utf8mb4');
    }
    protected function tearDown(): void
    {
        $this->server?->exec('DROP DATABASE IF EXISTS `'.self::SYSTEM_DB.'`');

        parent::tearDown();
    }

    private function migrator(): Migrator
    {
        $connections = self::connectionsFor(self::SYSTEM_DB);

        return new Migrator(
            $connections,
            dirname(__DIR__, 2).'/database/migrations',
            $connections->system(),
        );
    }

    public function testAplicaTodasLasMigraciones(): void
    {
        $applied = $this->migrator()->migrate();

        self::assertCount(5, $applied);

        $tables = $this->systemTables();

        foreach (['users', 'managed_databases', 'activity_log', 'login_attempts', 'query_history', 'schema_migrations'] as $expected) {
            self::assertContains($expected, $tables, "Falta la tabla {$expected}");
        }
    }

    public function testNoAplicaDosVecesLaMismaMigracion(): void
    {
        $migrator = $this->migrator();

        $first  = $migrator->migrate();
        $second = $migrator->migrate();

        self::assertCount(5, $first);
        self::assertSame([], $second);
    }

    public function testReportaLasPendientes(): void
    {
        $migrator = $this->migrator();

        self::assertCount(5, $migrator->pending());
        $migrator->migrate();
        self::assertSame([], $migrator->pending());
    }

    public function testLaClaveForaneaDePropiedadExiste(): void
    {
        $this->migrator()->migrate();

        $statement = $this->server()->query(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS'
            ." WHERE CONSTRAINT_TYPE = 'FOREIGN KEY'"
            ." AND TABLE_SCHEMA = '".self::SYSTEM_DB."'"
            ." AND TABLE_NAME = 'managed_databases'",
        );

        self::assertNotFalse($statement);
        self::assertSame(1, (int) $statement->fetchColumn());
    }

    /**
     * Conexion sin base de datos seleccionada, para consultar el servidor.
     */
    private function server(): \PDO
    {
        return self::adminConnections()->server();
    }

    /**
     * @return list<string>
     */
    private function systemTables(): array
    {
        $statement = $this->server()->query('SHOW TABLES FROM `'.self::SYSTEM_DB.'`');
        self::assertNotFalse($statement);

        /** @var list<string> $tables */
        $tables = $statement->fetchAll(\PDO::FETCH_COLUMN);

        return $tables;
    }
}
