<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Support\Env;
use DataForge\Support\Logger;
use DataForge\Support\Str;
use DataForge\Support\Theme;
use DataForge\Support\Url;
use PHPUnit\Framework\TestCase;

final class SupportTest extends TestCase
{
    public function testGeneraTokensHexadecimalesAleatorios(): void
    {
        $token = Str::token(32);

        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        self::assertNotSame(Str::token(32), $token);
    }

    public function testEscapaCaracteresEspeciales(): void
    {
        // '<', '&', '"' y "'"
        $raw = '<b>'.chr(38).chr(34).chr(39);
        $out = Str::e($raw);

        self::assertSame(
            chr(38).'lt;b'.chr(38).'gt;'.chr(38).'amp;'.chr(38).'quot;'.chr(38).'apos;',
            $out,
        );
    }

    public function testElEscapeNeutralizaEtiquetasHtml(): void
    {
        $out = Str::e('<script>alert(1)</script>');

        self::assertSame(
            chr(38).'lt;script'.chr(38).'gt;alert(1)'.chr(38).'lt;/script'.chr(38).'gt;',
            $out,
        );
    }

    public function testLimitaCadenasLargas(): void
    {
        self::assertSame('abcdef', Str::limit('abcdef', 10));
        self::assertSame('abc...', Str::limit('abcdef', 3));
    }

    public function testExistenSieteTemas(): void
    {
        self::assertCount(7, Theme::all());
    }

    public function testLosIdentificadoresSeConservanDelProyectoOriginal(): void
    {
        foreach (['neutral', 'medico', 'alimentos', 'ferreteria', 'legal', 'educacion', 'retail'] as $value) {
            self::assertNotNull(Theme::tryFromName($value), 'Falta el tema '.$value);
        }
    }

    public function testDevuelveNuloParaUnTemaDesconocido(): void
    {
        self::assertNull(Theme::tryFromName('no-existe'));
    }

    public function testCodificaLosIdentificadoresEnLasRutas(): void
    {
        self::assertSame('/databases/mi_base', Url::database('mi_base'));
        self::assertSame('/databases/mi%20base', Url::database('mi base'));
        self::assertSame('/databases/a%2Fb/tables/c', Url::table('a/b', 'c'));
    }

    public function testLasRutasDeFormularioCoincidenConLasDeAccion(): void
    {
        self::assertSame(Url::databaseCreate(), Url::databases());
        self::assertSame(Url::recordUpdate('db', 'tb', 5), Url::record('db', 'tb', 5));
    }

    public function testElLoggerRedactaLasClavesSensibles(): void
    {
        $directory = sys_get_temp_dir().'/dataforge-logs-'.uniqid();
        $logger    = new Logger($directory, 'debug');

        $logger->info('Intento de acceso', [
            'email'      => 'usuario@example.com',
            'password'   => 'secreto',
            'csrf_token' => 'abc',
        ]);

        $files = glob($directory.'/app-*.log') ?: [];
        self::assertNotSame([], $files);

        $contents = (string) file_get_contents($files[0]);

        self::assertStringContainsString('usuario@example.com', $contents);
        self::assertStringNotContainsString('secreto', $contents);
        self::assertStringContainsString('[redactado]', $contents);

        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($directory);
    }

    public function testElLoggerRespetaElNivelMinimo(): void
    {
        $directory = sys_get_temp_dir().'/dataforge-logs-'.uniqid();
        $logger    = new Logger($directory, 'error');

        $logger->debug('no se escribe');
        $logger->error('si se escribe');

        $files = glob($directory.'/app-*.log') ?: [];
        self::assertCount(1, $files);

        $contents = (string) file_get_contents($files[0]);
        self::assertStringNotContainsString('no se escribe', $contents);
        self::assertStringContainsString('si se escribe', $contents);

        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($directory);
    }

    public function testLeeValoresDeUnArchivoEnv(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'env');
        self::assertIsString($file);
        file_put_contents($file, "APP_ENV=testing\nAPP_DEBUG=true\n# comentario\n");

        Env::load($file);

        self::assertSame('testing', Env::get('APP_ENV'));
        self::assertTrue(Env::bool('APP_DEBUG'));
        self::assertSame('predeterminado', Env::get('NO_EXISTE', 'predeterminado'));
        self::assertSame(0, Env::int('NO_EXISTE', 0));

        @unlink($file);
    }
}
