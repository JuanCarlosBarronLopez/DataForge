<?php

declare(strict_types=1);

namespace DataForge\Tests\Unit;

use DataForge\Security\FileRateLimitStore;
use DataForge\Security\RateLimiter;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/dataforge-tests-'.uniqid();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*.json') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->directory);
    }

    private function limiter(int $max = 3): RateLimiter
    {
        return new RateLimiter(new FileRateLimitStore($this->directory));
    }

    public function testPermitePeticionesBajoElLimite(): void
    {
        $limiter = $this->limiter();

        $result = $limiter->attempt('ip', 3, 60);

        self::assertTrue($result->allowed);
        self::assertSame(2, $result->remaining);
        self::assertSame(3, $result->limit);
    }

    public function testBloqueaAlSuperarElLimite(): void
    {
        $limiter = $this->limiter();

        $limiter->attempt('ip', 3, 60);
        $limiter->attempt('ip', 3, 60);
        $limiter->attempt('ip', 3, 60);
        $result = $limiter->attempt('ip', 3, 60);

        self::assertFalse($result->allowed);
        self::assertSame(0, $result->remaining);
    }

    public function testClavesDistintasNoSeAfectan(): void
    {
        $limiter = $this->limiter();

        for ($index = 0; $index < 5; ++$index) {
            $limiter->attempt('ip-a', 3, 60);
        }

        $result = $limiter->attempt('ip-b', 3, 60);

        self::assertTrue($result->allowed);
    }

    public function testIndicaLosSegundosDeEspera(): void
    {
        $limiter = $this->limiter();

        for ($index = 0; $index < 4; ++$index) {
            $limiter->attempt('ip', 3, 60);
        }

        $result = $limiter->attempt('ip', 3, 60);

        self::assertGreaterThan(0, $result->retryAfter);
        self::assertLessThanOrEqual(60, $result->retryAfter);
    }

    public function testElAlmacenCreaElDirectorioSiNoExiste(): void
    {
        $store = new FileRateLimitStore($this->directory.'/anidado');

        $state = $store->hit('clave', 60);

        self::assertSame(1, $state['count']);
        self::assertFileExists($this->directory.'/anidado');
    }
}
