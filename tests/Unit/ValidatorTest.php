<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Support\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testAceptaUnRegistroValido(): void
    {
        $validator = (new Validator())
            ->username('juan_perez')
            ->email('juan@example.com')
            ->password('Contrasena2026', 'Contrasena2026');

        self::assertTrue($validator->passes());
        self::assertSame([], $validator->errors());
    }

    public function testRechazaUsuarioCorto(): void
    {
        $validator = (new Validator())->username('ab');

        self::assertTrue($validator->fails());
        self::assertStringContainsString('3 y 30', $validator->firstError());
    }

    public function testRechazaUsuarioConCaracteresEspeciales(): void
    {
        self::assertTrue((new Validator())->username('user<script>')->fails());
        self::assertTrue((new Validator())->username('user name')->fails());
        self::assertTrue((new Validator())->username('user-name')->fails());
    }

    public function testRechazaCorreoInvalido(): void
    {
        self::assertTrue((new Validator())->email('no-es-un-correo')->fails());
        self::assertTrue((new Validator())->email('a@b')->fails());
    }

    public function testExigeDoceCaracteresEnLaContrasena(): void
    {
        $validator = (new Validator())->password('abc123');

        self::assertTrue($validator->fails());
        self::assertStringContainsString('12 caracteres', $validator->firstError());
    }

    public function testExigeLetrasYDigitos(): void
    {
        self::assertTrue((new Validator())->password('abcdefghij12')->passes());
        self::assertTrue((new Validator())->password('abcdefghijkl')->fails());
        self::assertTrue((new Validator())->password('123456789012')->fails());
    }

    public function testDetectaContrasenasDistintas(): void
    {
        $validator = (new Validator())->password('Contrasena2026', 'Contrasena2027');

        self::assertTrue($validator->fails());
        self::assertStringContainsString('no coinciden', $validator->firstError());
    }

    public function testAcumulaVariosErrores(): void
    {
        $validator = (new Validator())
            ->username('')
            ->email('malo');

        self::assertCount(2, $validator->errors());
    }
}
