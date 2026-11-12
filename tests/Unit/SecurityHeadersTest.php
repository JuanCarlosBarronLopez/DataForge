<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Security\SecurityHeaders;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function testIncluyeUnaCspConNonce(): void
    {
        $headers = (new SecurityHeaders(false))->build('YWJjZGVmZ2hpamtsbW5vcA==');

        self::assertStringContainsString("script-src 'self' 'nonce-YWJjZGVmZ2hpamtsbW5vcA=='", $headers['Content-Security-Policy']);
    }

    public function testLaCspNoPermiteScriptsEnLinea(): void
    {
        $headers = (new SecurityHeaders(false))->build('nonce');
        $csp     = $headers['Content-Security-Policy'];

        preg_match('/script-src ([^;]*)/', $csp, $matches);

        $directive = $matches[1] ?? '';
        self::assertNotSame('', $directive, 'La CSP debe declarar script-src');
        self::assertStringNotContainsString('unsafe-inline', $directive);
        self::assertStringContainsString("'nonce-nonce'", $directive);
    }

    public function testNoEnviaXXssProtection(): void
    {
        $headers = (new SecurityHeaders(false))->build('nonce');

        self::assertArrayNotHasKey('X-XSS-Protection', $headers);
    }

    public function testEnviaHstsSoloEnProduccion(): void
    {
        self::assertArrayNotHasKey('Strict-Transport-Security', (new SecurityHeaders(false))->build('nonce'));
        self::assertArrayHasKey('Strict-Transport-Security', (new SecurityHeaders(true))->build('nonce'));
    }

    public function testNiegaElEncuadre(): void
    {
        $headers = (new SecurityHeaders(false))->build('nonce');

        self::assertSame('DENY', $headers['X-Frame-Options']);
        self::assertStringContainsString("frame-ancestors 'none'", $headers['Content-Security-Policy']);
    }

    public function testCadaRespuestaGeneraUnNonceDistinto(): void
    {
        $first  = (new SecurityHeaders(false))->build('uno');
        $second = (new SecurityHeaders(false))->build('dos');

        self::assertNotSame($first['Content-Security-Policy'], $second['Content-Security-Policy']);
    }
}
