<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Exception\ValidationException;
use DataForge\Service\SqlGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SqlGuardTest extends TestCase
{
    private SqlGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new SqlGuard();
    }

    public static function consultasValidasProvider(): \Generator
    {
        yield 'select simple' => ['SELECT * FROM clientes', 'SELECT * FROM clientes LIMIT 500'];
        yield 'select con limite' => ['SELECT id FROM clientes LIMIT 10', 'SELECT id FROM clientes LIMIT 10'];
        yield 'select minusculas' => ['select id from t', 'select id from t LIMIT 500'];
        yield 'show tables' => ['SHOW TABLES', 'SHOW TABLES LIMIT 500'];
        yield 'describe' => ['DESCRIBE clientes', 'DESCRIBE clientes LIMIT 500'];
        yield 'explain' => ['EXPLAIN SELECT 1', 'EXPLAIN SELECT 1 LIMIT 500'];
        yield 'with cte' => ['WITH t AS (SELECT 1) SELECT * FROM t', 'WITH t AS (SELECT 1) SELECT * FROM t LIMIT 500'];
        yield 'con punto y coma final' => ['SELECT 1;', 'SELECT 1 LIMIT 500'];
        yield 'con comentario de linea' => ["SELECT 1 -- comentario\n", 'SELECT 1 LIMIT 500'];
        yield 'literal con punto y coma' => ["SELECT ';' FROM t", "SELECT ';' FROM t LIMIT 500"];
        yield 'select con espacios' => ['   SELECT 1   ', 'SELECT 1 LIMIT 500'];
    }

    public static function consultasRechazadasProvider(): \Generator
    {
        yield 'insert' => ['INSERT INTO t VALUES (1)'];
        yield 'update' => ['UPDATE t SET a = 1'];
        yield 'delete' => ['DELETE FROM t'];
        yield 'drop' => ['DROP TABLE t'];
        yield 'create' => ['CREATE TABLE t (id INT)'];
        yield 'alter' => ['ALTER TABLE t ADD COLUMN a INT'];
        yield 'grant' => ['GRANT ALL ON *.* TO user'];
        yield 'vacío' => [''];
        yield 'dos enunciados' => ['SELECT 1; SELECT 2'];
        yield 'escritura tras literal' => ["SELECT ';' ; DROP TABLE t"];
        yield 'demasiado larga' => [str_repeat('A', 20001)];
    }

    #[DataProvider('consultasValidasProvider')]
    public function testAceptaConsultasDeLectura(string $sql, string $expected): void
    {
        self::assertSame($expected, $this->guard->inspect($sql, 500));
    }

    #[DataProvider('consultasRechazadasProvider')]
    public function testRechazaConsultasQueNoSonDeLectura(string $sql): void
    {
        $this->expectException(ValidationException::class);
        $this->guard->inspect($sql, 500);
    }

    public function testAplicaElLimiteDeFilasIndicado(): void
    {
        self::assertSame('SELECT 1 LIMIT 25', $this->guard->inspect('SELECT 1', 25));
    }

    public function testRespetaUnLimiteYaPresente(): void
    {
        self::assertSame('SELECT 1 LIMIT 3', $this->guard->inspect('SELECT 1 LIMIT 3', 25));
    }

    public function testElMensajeDeRechazoExplicaElMotivo(): void
    {
        try {
            $this->guard->inspect('DELETE FROM t', 500);
            self::fail('Debio lanzar ValidationException');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('solo admite consultas de lectura', $exception->firstError());
        }
    }
}
