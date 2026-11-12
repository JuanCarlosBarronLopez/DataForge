<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Security\Csrf;
use DataForge\Security\SessionManager;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    private Csrf $csrf;

    protected function setUp(): void
    {
        $_SESSION   = [];
        $this->csrf = new Csrf(new SessionManager());
        $this->csrf->token('accion-prueba');
    }

    public function testGeneraUnTokenDeCientoCuatroCaracteres(): void
    {
        $token = $this->csrf->token('otra-accion');

        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testDevuelveElMismoTokenMientrasNoSeConsuma(): void
    {
        $first  = $this->csrf->token('accion-prueba');
        $second = $this->csrf->token('accion-prueba');

        self::assertSame($first, $second);
    }

    public function testTokensDistintosPorAccion(): void
    {
        $login  = $this->csrf->token('login');
        $delete = $this->csrf->token('delete');

        self::assertNotSame($login, $delete);
    }

    public function testValidaElTokenCorrecto(): void
    {
        $token = $this->csrf->token('accion-prueba');

        self::assertTrue($this->csrf->validate('accion-prueba', $token));
    }

    public function testRechazaUnTokenDistinto(): void
    {
        $this->csrf->token('accion-prueba');

        self::assertFalse($this->csrf->validate('accion-prueba', str_repeat('0', 64)));
    }

    public function testRechazaUnTokenVacio(): void
    {
        $this->csrf->token('accion-prueba');

        self::assertFalse($this->csrf->validate('accion-prueba', ''));
    }

    public function testElTokenNoSirveParaOtraAccion(): void
    {
        $token = $this->csrf->token('login');

        self::assertFalse($this->csrf->validate('delete', $token));
    }

    public function testElTokenSeConsumeTrasValidarlo(): void
    {
        $token = $this->csrf->token('accion-prueba');
        $this->csrf->validate('accion-prueba', $token);

        self::assertFalse($this->csrf->validate('accion-prueba', $token));
    }

    public function testElTokenSeConsumeTambienCuandoLaValidacionFalla(): void
    {
        $token = $this->csrf->token('accion-prueba');
        $this->csrf->validate('accion-prueba', 'token-incorrecto');

        self::assertFalse($this->csrf->validate('accion-prueba', $token));
    }

    public function testElCampoOcultoEscapaElToken(): void
    {
        $field = $this->csrf->field('accion-prueba');

        self::assertStringContainsString('name="csrf_token"', $field);
        self::assertDoesNotMatchRegularExpression('/[<>"](?![^<>]*>)\s*value/', $field);
    }

    public function testLimitaElNumeroDeTokensGuardados(): void
    {
        for ($index = 0; $index < 40; ++$index) {
            $this->csrf->token('accion-'.$index);
        }

        self::assertCount(20, $_SESSION['_csrf_tokens']);
    }
}
