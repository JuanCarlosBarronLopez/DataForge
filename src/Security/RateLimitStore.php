<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Almacen de contadores de rate limiting.
 */
interface RateLimitStore
{
    /**
     * Registra un intento y devuelve el total de intentos de la ventana.
     *
     * @return array{count: int, ttl: int}
     */
    public function hit(string $key, int $windowSeconds): array;
}
