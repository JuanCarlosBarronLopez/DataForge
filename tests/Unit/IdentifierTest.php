<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Support\Identifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    public static function validNamesProvider(): \Generator
    {
        yield 'letras' => ['clientes'];
        yield 'con guion bajo' => ['user_accounts'];
        yield 'con numeros' => ['tabla2026'];
        yield 'un caracter' => ['a'];
        yield 'mixto' => ['A9z_0'];
        yield 'limite' => [str_repeat('a', 64)];
    }

    public static function invalidNamesProvider(): \Generator
    {
        yield 'vacio' => [''];
        yield 'con espacios' => ['mi tabla'];
        yield 'con guion' => ['mi-tabla'];
        yield 'con punto' => ['mi.tabla'];
        yield 'injection drop' => ['a; DROP TABLE users'];
        yield 'comentario' => ['a--b'];
        yield 'backtick' => ['a`b'];
        yield 'comillas' => ["a'b"];
        yield 'limite excedido' => [str_repeat('a', 65)];
        yield 'null byte' => ["a\0b"];
    }

    #[DataProvider('validNamesProvider')]
    public function testAceptaNombresValidos(string $name): void
    {
        self::assertTrue(Identifier::isValid($name));
        self::assertSame('`'.$name.'`', Identifier::quote($name));
    }

    #[DataProvider('invalidNamesProvider')]
    public function testRechazaNombresInvalidos(string $name): void
    {
        self::assertFalse(Identifier::isValid($name));
    }

    public function testQuoteLanzaExcepcionConNombreInvalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Identifier::quote('mi tabla');
    }
}
