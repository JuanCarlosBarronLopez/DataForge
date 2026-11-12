<?php

declare(strict_types=1);

namespace DataForge\Tests\Feature;

use DataForge\Core\Application;
use DataForge\Core\Config;
use DataForge\Core\Request;
use DataForge\Tests\Support\DatabaseTestCase;

/**
 * Pruebas de integracion sobre el ciclo HTTP completo: rutas, middleware,
 * tokens CSRF y respuestas.
 */
final class HttpTest extends DatabaseTestCase
{
    private const SYSTEM_DB = 'dataforge_http_test';

    private static Application $application;

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

        $users = new \DataForge\Repository\UserRepository($connections->system());
        $users->create('usuario_http', 'http@example.com', (new \DataForge\Security\PasswordHasher())->hash('Contrasena2026'));

        self::$application = new Application(dirname(__DIR__, 2), self::config());
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

        $_SESSION = [];
    }

    public function testLaPortadaRespondeConLasCabecerasDeSeguridad(): void
    {
        $response = self::$application->handle(Request::create('GET', '/'));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Content-Security-Policy', implode(' ', array_keys($response->headers())));
        self::assertStringContainsString('X-Content-Type-Options', implode(' ', array_keys($response->headers())));
        self::assertSame('0', $response->headers()['X-XSS-Protection'] ?? null);
        self::assertStringContainsString('DataForge', $response->body());
    }

    public function testLasCabecerasCambianEnCadaRespuesta(): void
    {
        $first  = self::$application->handle(Request::create('GET', '/'));
        $second = self::$application->handle(Request::create('GET', '/'));

        self::assertNotSame(
            $first->headers()['Content-Security-Policy'],
            $second->headers()['Content-Security-Policy'],
        );
    }

    public function testUnaRutaInexistenteDevuelve404(): void
    {
        $response = self::$application->handle(Request::create('GET', '/no-existe'));

        self::assertSame(404, $response->status());
    }

    public function testLasRutasPrivadasRedirigenAlAcceso(): void
    {
        $response = self::$application->handle(Request::create('GET', '/dashboard'));

        self::assertSame(302, $response->status());
        self::assertSame('/login?next=%2Fdashboard', $response->headers()['Location']);
    }

    public function testUnPostSinTokenCsrfSeRechaza(): void
    {
        $response = self::$application->handle(Request::create('POST', '/login', [
            'email'    => 'alguien@example.com',
            'password' => 'Contrasena2026',
        ]));

        self::assertSame(403, $response->status());
        self::assertStringContainsString('Token de seguridad invalido', $response->body());
    }

    public function testElFlujoDeAccesoFunciona(): void
    {
        $login = self::$application->handle(Request::create('GET', '/login'));
        self::assertSame(200, $login->status());

        $token = $this->extractCsrfToken($login->body());
        self::assertNotSame('', $token);

        $response = self::$application->handle(Request::create('POST', '/login', [
            'csrf_token' => $token,
            'email'      => 'http@example.com',
            'password'   => 'Contrasena2026',
        ]));

        self::assertSame(302, $response->status());
        self::assertSame('/onboarding', $response->headers()['Location']);
        self::assertArrayHasKey('auth_user', $_SESSION);
    }

    public function testUnTokenCsrfNoSirveParaOtraRuta(): void
    {
        $login = self::$application->handle(Request::create('GET', '/login'));
        $token = $this->extractCsrfToken($login->body());

        $response = self::$application->handle(Request::create('POST', '/databases', [
            'csrf_token' => $token,
            'name'       => 'base_falsa',
        ]));

        self::assertSame(403, $response->status());
    }

    public function testElTokenCsrfSeConsumeTrasUsarlo(): void
    {
        $login = self::$application->handle(Request::create('GET', '/login'));
        $token = $this->extractCsrfToken($login->body());

        $body = [
            'csrf_token' => $token,
            'email'      => 'http@example.com',
            'password'   => 'Contrasena2026',
        ];

        self::$application->handle(Request::create('POST', '/login', $body));
        $second = self::$application->handle(Request::create('POST', '/login', $body));

        self::assertSame(403, $second->status());
    }

    public function testElRegistroCreaLaCuentaYDevuelveElFormulario(): void
    {
        $register = self::$application->handle(Request::create('GET', '/register'));
        self::assertSame(200, $register->status());
        self::assertStringContainsString('Crear cuenta', $register->body());

        $token = $this->extractCsrfToken($register->body());

        $response = self::$application->handle(Request::create('POST', '/register', [
            'csrf_token'            => $token,
            'username'              => 'usuario_nuevo',
            'email'                 => 'nuevo@example.com',
            'password'              => 'Contrasena2026',
            'password_confirmation' => 'Contrasena2026',
        ]));

        self::assertSame(302, $response->status());
        self::assertSame('/login', $response->headers()['Location']);
    }

    public function testUnaContrasenaCortaSeRechazaConElFormulario(): void
    {
        $register = self::$application->handle(Request::create('GET', '/register'));
        $token    = $this->extractCsrfToken($register->body());

        $response = self::$application->handle(Request::create('POST', '/register', [
            'csrf_token'            => $token,
            'username'              => 'usuario_corto',
            'email'                 => 'corto@example.com',
            'password'              => 'corta',
            'password_confirmation' => 'corta',
        ]));

        self::assertSame(422, $response->status());
        self::assertStringContainsString('12 caracteres', $response->body());
    }

    public function testLaDocumentacionEsPublica(): void
    {
        $response = self::$application->handle(Request::create('GET', '/docs'));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Limitaciones conocidas', $response->body());
    }

    public function testLaPaginaSobreElProyectoEsPublica(): void
    {
        $response = self::$application->handle(Request::create('GET', '/about'));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('OMNARA', $response->body());
        self::assertStringNotContainsString('Raju Technology', $response->body());
    }

    public function testLaPaginaNoEscapaLosDatosDelUsuario(): void
    {
        $register = self::$application->handle(Request::create('GET', '/register'));
        $token    = $this->extractCsrfToken($register->body());

        $response = self::$application->handle(Request::create('POST', '/register', [
            'csrf_token'            => $token,
            'username'              => '<script>alert(1)</script>',
            'email'                 => 'xss@example.com',
            'password'              => 'Contrasena2026',
            'password_confirmation' => 'Contrasena2026',
        ]));

        self::assertSame(422, $response->status());
        self::assertStringNotContainsString('<script>alert(1)</script>', $response->body());
    }

    private function extractCsrfToken(string $html): string
    {
        if (preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private static function config(): Config
    {
        $root = dirname(__DIR__, 2);

        return new Config([
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
                    'logs'       => $root.'/storage/logs',
                    'cache'      => sys_get_temp_dir().'/dataforge-http-cache',
                    'framework'  => $root.'/storage/framework',
                    'uploads'    => sys_get_temp_dir().'/dataforge-http-uploads',
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
