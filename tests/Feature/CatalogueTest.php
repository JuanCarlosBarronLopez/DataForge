<?php

declare(strict_types=1);

namespace DataForge\Tests\Feature;

use DataForge\Database\ColumnDefinition;
use DataForge\Database\ColumnType;
use DataForge\Database\Migrator;
use DataForge\Exception\NotFoundException;
use DataForge\Exception\ValidationException;
use DataForge\Repository\ActivityRepository;
use DataForge\Repository\ManagedDatabaseRepository;
use DataForge\Service\DatabaseService;
use DataForge\Service\ExportService;
use DataForge\Service\RecordService;
use DataForge\Service\TableService;
use DataForge\Support\Logger;
use DataForge\Tests\Support\DatabaseTestCase;

final class CatalogueTest extends DatabaseTestCase
{
    private const SYSTEM_DB = 'dataforge_catalogue_test';

    private const USER_DB = 'catalogue_user_db';

    private static int $ownerId = 0;

    private DatabaseService $databases;

    private TableService $tables;

    private RecordService $records;

    private ExportService $exports;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!self::isAvailable()) {
            return;
        }

        $server = self::adminConnections()->server();
        $server->exec('DROP DATABASE IF EXISTS `'.self::SYSTEM_DB.'`');
        $server->exec('DROP DATABASE IF EXISTS `'.self::USER_DB.'`');
        $server->exec('CREATE DATABASE `'.self::SYSTEM_DB.'` CHARACTER SET utf8mb4');

        $connections = self::connectionsFor(self::SYSTEM_DB);
        (new Migrator($connections, dirname(__DIR__, 2).'/database/migrations', $connections->system()))->migrate();

        self::$ownerId = 0;
        $connection    = $connections->system();
        $insert        = $connection->query(
            "INSERT INTO `users` (`username`, `email`, `password_hash`) VALUES ('catalogo', 'catalogo@example.com', 'x')",
        );
        if ($insert !== false) {
            self::$ownerId = (int) $connection->lastInsertId();
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::isAvailable()) {
            $server = self::adminConnections()->server();
            $server->exec('DROP DATABASE IF EXISTS `'.self::SYSTEM_DB.'`');
            $server->exec('DROP DATABASE IF EXISTS `'.self::USER_DB.'`');
        }

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connections = self::connectionsFor(self::SYSTEM_DB);
        $logger      = new Logger(sys_get_temp_dir().'/dataforge-test-logs');

        $managed  = new ManagedDatabaseRepository($connections->system());
        $activity = new ActivityRepository($connections->system());

        $this->databases = new DatabaseService(self::adminConnections(), $managed, $activity, $logger);
        $this->tables    = new TableService(self::adminConnections());
        $this->records   = new RecordService(self::adminConnections(), $this->tables);
        $this->exports   = new ExportService(self::adminConnections(), $this->tables);

        // El usuario empieza sin bases registradas y la base de prueba se
        // recrea en cada caso para partir de un estado conocido.
        foreach ($managed->namesFor(self::$ownerId) as $name) {
            $managed->unregister(self::$ownerId, $name);
        }

        self::adminConnections()->server()->exec('DROP DATABASE IF EXISTS `'.self::USER_DB.'`');
    }

    public function testRegistraYListaBasesDelUsuario(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);

        self::assertSame([self::USER_DB], $this->databases->listFor(self::$ownerId));
    }

    public function testRechazaUnNombreDeBaseInvalido(): void
    {
        $this->expectException(ValidationException::class);
        $this->databases->create(self::$ownerId, 'mi base');
    }

    public function testRechazaUnNombreDuplicado(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);

        $this->expectException(ValidationException::class);
        $this->databases->create(self::$ownerId, self::USER_DB);
    }

    public function testUnUsuarioSinPropiedadNoPuedeAbrirLaBase(): void
    {
        $this->expectException(NotFoundException::class);
        $this->databases->assertOwnership(self::$ownerId + 999, self::USER_DB);
    }

    public function testCreaYDescribeUnaTabla(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);

        $this->tables->create(self::USER_DB, 'clientes', [
            new ColumnDefinition('nombre', ColumnType::Varchar, false, 80),
            new ColumnDefinition('saldo', ColumnType::Decimal, true),
            new ColumnDefinition('activo', ColumnType::Boolean, false),
        ]);

        self::assertSame(['clientes'], $this->tables->list(self::USER_DB));

        $columns = $this->tables->describe(self::USER_DB, 'clientes');
        self::assertCount(4, $columns);
        self::assertSame('id', $columns[0]['Field']);
        self::assertSame('nombre', $columns[1]['Field']);
        self::assertSame('saldo', $columns[2]['Field']);
        self::assertSame('YES', $columns[2]['Null']);
    }

    public function testRechazaUnaTablaConNombreInvalido(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);

        $this->expectException(ValidationException::class);
        $this->tables->create(self::USER_DB, 'mi tabla', [
            new ColumnDefinition('a', ColumnType::Int),
        ]);
    }

    public function testRechazaUnaTablaSinColumnas(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);

        $this->expectException(ValidationException::class);
        $this->tables->create(self::USER_DB, 'vacia', []);
    }

    public function testInsertaLeeActualizaYBorra(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);
        $this->tables->create(self::USER_DB, 'productos', [
            new ColumnDefinition('nombre', ColumnType::Varchar, false, 60),
            new ColumnDefinition('precio', ColumnType::Decimal, true),
        ]);

        $id = $this->records->create(self::USER_DB, 'productos', ['nombre' => 'Teclado', 'precio' => '199.90']);

        self::assertGreaterThan(0, $id);

        $found = $this->records->find(self::USER_DB, 'productos', $id);
        self::assertNotNull($found);
        self::assertSame('Teclado', $found['nombre']);

        $this->records->update(self::USER_DB, 'productos', $id, ['nombre' => 'Teclado mecanico']);

        $updated = $this->records->find(self::USER_DB, 'productos', $id);
        self::assertNotNull($updated);
        self::assertSame('Teclado mecanico', $updated['nombre']);

        $this->records->delete(self::USER_DB, 'productos', $id);
        self::assertNull($this->records->find(self::USER_DB, 'productos', $id));
    }

    public function testIgnoraColumnasQueNoExisten(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);
        $this->tables->create(self::USER_DB, 'segura', [
            new ColumnDefinition('nombre', ColumnType::Varchar, false, 60),
        ]);

        $this->records->create(self::USER_DB, 'segura', [
            'nombre'              => 'valor',
            'columna_inexistente' => 'inyeccion',
        ]);

        $columns = $this->tables->columnNames(self::USER_DB, 'segura');

        self::assertSame(['id', 'nombre'], $columns);
    }

    public function testPaginaConBusqueda(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);
        $this->tables->create(self::USER_DB, 'pagina', [
            new ColumnDefinition('titulo', ColumnType::Varchar, false, 60),
        ]);

        for ($index = 1; $index <= 7; ++$index) {
            $this->records->create(self::USER_DB, 'pagina', [
                'titulo' => $index % 2 === 0 ? 'even '.$index : 'odd '.$index,
            ]);
        }

        $result = $this->records->paginate(self::USER_DB, 'pagina', 3, 0, '', 'asc', '');

        self::assertSame(7, $result['total']);
        self::assertCount(3, $result['rows']);

        $filtered = $this->records->paginate(self::USER_DB, 'pagina', 25, 0, '', 'asc', 'even');

        self::assertSame(3, $filtered['total']);

        $sorted = $this->records->paginate(self::USER_DB, 'pagina', 25, 0, 'titulo', 'desc', '');

        self::assertSame('odd 7', $sorted['rows'][0]['titulo']);
    }

    public function testExportaLaTablaEnCsv(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);
        $this->tables->create(self::USER_DB, 'csv', [
            new ColumnDefinition('nombre', ColumnType::Varchar, false, 60),
        ]);
        $this->records->create(self::USER_DB, 'csv', ['nombre' => 'uno']);

        $csv = $this->exports->tableToCsv(self::USER_DB, 'csv');

        self::assertStringContainsString('nombre', $csv);
        self::assertStringContainsString('uno', $csv);
    }

    public function testExportaLaBaseEnSql(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);
        $this->tables->create(self::USER_DB, 'dump', [
            new ColumnDefinition('nombre', ColumnType::Varchar, false, 60),
        ]);
        $this->records->create(self::USER_DB, 'dump', ['nombre' => 'registro']);

        $dump = $this->exports->databaseToSql(self::USER_DB);

        self::assertStringContainsString('CREATE TABLE `dump`', $dump);
        self::assertStringContainsString('INSERT INTO `dump`', $dump);
        self::assertStringContainsString('registro', $dump);
    }

    public function testEliminaLaBaseYQuitaElRegistro(): void
    {
        $this->databases->create(self::$ownerId, self::USER_DB);
        $this->databases->drop(self::$ownerId, self::USER_DB);

        self::assertSame([], $this->databases->listFor(self::$ownerId));
    }
}
