<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Security\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new PasswordHasher();
    }

    public function testVerificaLaContrasenaCorrecta(): void
    {
        $hash = $this->hasher->hash('Contrasena2026');

        self::assertTrue($this->hasher->verify('Contrasena2026', $hash));
        self::assertFalse($this->hasher->verify('OtraContrasena', $hash));
    }

    public function testNoProduceElMismoHashDosVeces(): void
    {
        self::assertNotSame(
            $this->hasher->hash('Contrasena2026'),
            $this->hasher->hash('Contrasena2026'),
        );
    }

    public function testUsaArgon2idCuandoEstaDisponible(): void
    {
        $hash = $this->hasher->hash('Contrasena2026');

        if ($this->hasher->supportsArgon2()) {
            self::assertStringStartsWith('$argon2id$', $hash);

            return;
        }

        self::assertStringStartsWith('$2y$12$', $hash, 'El respaldo bcrypt debe usar cost 12');
    }

    public function testElRespaldoBcryptUsaCostDoce(): void
    {
        if ($this->hasher->supportsArgon2()) {
            self::markTestSkipped('La extension Argon2id esta disponible; bcrypt no entra en juego.');
        }

        $hash = $this->hasher->hash('Contrasena2026');

        self::assertStringStartsWith('$2y$12$', $hash);
    }

    public function testRechazaHashVacio(): void
    {
        self::assertFalse($this->hasher->verify('Contrasena2026', ''));
    }

    public function testDetectaUnHashObsoleto(): void
    {
        if (!$this->hasher->supportsArgon2()) {
            self::markTestSkipped('Sin Argon2id no se puede distinguir un hash obsoleto.');
        }

        $old = (string) password_hash('Contrasena2026', PASSWORD_BCRYPT, ['cost' => 10]);

        self::assertTrue($this->hasher->needsRehash($old));

        $fresh = $this->hasher->hash('Contrasena2026');
        self::assertFalse($this->hasher->needsRehash($fresh));
    }
}
