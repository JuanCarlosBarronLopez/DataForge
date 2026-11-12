<?php

declare(strict_types=1);

namespace DataForge\Tests\Feature;

use DataForge\Database\Migrator;
use DataForge\Exception\ValidationException;
use DataForge\Repository\ActivityRepository;
use DataForge\Repository\LoginAttemptRepository;
use DataForge\Security\PasswordHasher;
use DataForge\Security\SessionManager;
use DataForge\Service\AuthService;
use DataForge\Service\AvatarService;
use DataForge\Support\Logger;
use DataForge\Support\Theme;
use DataForge\Tests\Support\DatabaseTestCase;

final class AuthServiceTest extends DatabaseTestCase
{
    private const SYSTEM_DB = 'dataforge_auth_test';

    private AuthService $auth;

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
        (new Migrator($connections, dirname(__DIR__, 2).'/database/migrations', $connections->system()))->migrate();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::isAvailable()) {
            self::adminConnections()->server()->exec('DROP DATABASE IF EXISTS `'.self::SYSTEM_DB.'`');
        }

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connections = self::connectionsFor(self::SYSTEM_DB);

        $_SESSION = [];

        $logger = new Logger(sys_get_temp_dir().'/dataforge-test-logs');
        $avatar = new AvatarService(sys_get_temp_dir().'/dataforge-test-avatars');

        $this->auth = new AuthService(
            new \DataForge\Repository\UserRepository($connections->system()),
            new LoginAttemptRepository($connections->system()),
            new ActivityRepository($connections->system()),
            new PasswordHasher(),
            new SessionManager(1800, 28800),
            $avatar,
            $logger,
        );
    }

    public function testRegistraUnUsuarioValido(): void
    {
        $id = $this->auth->register('usuario_uno', 'uno@example.com', 'Contrasena2026', 'Contrasena2026');

        self::assertGreaterThan(0, $id);

        $user = $this->auth->login('uno@example.com', 'Contrasena2026', '127.0.0.1');

        self::assertSame('usuario_uno', $user['username']);
        self::assertArrayNotHasKey('password_hash', $user);
        self::assertTrue($this->auth->isAuthenticated());
    }

    public function testRechazaUnRegistroConContrasenaDebil(): void
    {
        $this->expectException(ValidationException::class);
        $this->auth->register('usuario_dos', 'dos@example.com', 'corta123', 'corta123');
    }

    public function testRechazaUnRegistroDuplicado(): void
    {
        $this->auth->register('usuario_tres', 'tres@example.com', 'Contrasena2026', 'Contrasena2026');

        $this->expectException(ValidationException::class);
        $this->auth->register('usuario_tres', 'tres@example.com', 'Contrasena2026', 'Contrasena2026');
    }

    public function testRechazaCredencialesIncorrectas(): void
    {
        $this->auth->register('usuario_cuatro', 'cuatro@example.com', 'Contrasena2026', 'Contrasena2026');

        $this->expectException(ValidationException::class);
        $this->auth->login('cuatro@example.com', 'ContrasenaErronea', '127.0.0.1');
    }

    public function testRegistraLosIntentosFallidos(): void
    {
        $this->auth->register('usuario_cinco', 'cinco@example.com', 'Contrasena2026', 'Contrasena2026');

        try {
            $this->auth->login('cinco@example.com', 'Incorrecta', '10.0.0.1');
            self::fail('Debio rechazar el acceso');
        } catch (ValidationException) {
            // esperado
        }

        $repository = new LoginAttemptRepository(self::connectionsFor(self::SYSTEM_DB)->system());

        self::assertSame(1, $repository->failedAttemptsSince('cinco@example.com', time() - 60));
    }

    public function testBloqueaLaCuentaTrasMuchosIntentosFallidos(): void
    {
        $this->auth->register('usuario_seis', 'seis@example.com', 'Contrasena2026', 'Contrasena2026');

        $repository = new LoginAttemptRepository(self::connectionsFor(self::SYSTEM_DB)->system());

        for ($index = 0; $index < 10; ++$index) {
            $repository->record('seis@example.com', '10.0.0.2', false);
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Demasiados intentos');

        $this->auth->login('seis@example.com', 'Contrasena2026', '10.0.0.2');
    }

    public function testActualizaElPerfilYElTema(): void
    {
        $id = $this->auth->register('usuario_siete', 'siete@example.com', 'Contrasena2026', 'Contrasena2026');
        $this->auth->login('siete@example.com', 'Contrasena2026', '127.0.0.1');

        $this->auth->updateProfile($id, 'usuario_siete_b', 'siete2@example.com', 'light', null);

        $user = $this->auth->user();
        self::assertNotNull($user);
        self::assertSame('usuario_siete_b', $user['username']);
        self::assertSame('light', $user['color_mode']);

        $this->auth->changeTheme($id, Theme::MediCare->value);

        $themed = $this->auth->user();
        self::assertNotNull($themed);
        self::assertSame('medico', $themed['theme']);
    }

    public function testRechazaUnTemaDesconocido(): void
    {
        $id = $this->auth->register('usuario_ocho', 'ocho@example.com', 'Contrasena2026', 'Contrasena2026');

        $this->expectException(ValidationException::class);
        $this->auth->changeTheme($id, 'no-existe');
    }

    public function testCambiaLaContrasenaYExigeLaActual(): void
    {
        $id = $this->auth->register('usuario_nueve', 'nueve@example.com', 'Contrasena2026', 'Contrasena2026');

        $this->expectException(ValidationException::class);
        $this->auth->changePassword($id, 'Incorrecta', 'NuevaContrasena2026', 'NuevaContrasena2026');
    }

    public function testEliminaLaCuenta(): void
    {
        $id = $this->auth->register('usuario_diez', 'diez@example.com', 'Contrasena2026', 'Contrasena2026');
        $this->auth->login('diez@example.com', 'Contrasena2026', '127.0.0.1');

        $this->auth->deleteAccount($id);

        self::assertNull($this->auth->user());
    }

}
