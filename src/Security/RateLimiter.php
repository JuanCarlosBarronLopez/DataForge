<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Limitador de peticiones por clave (normalmente IP + ambito).
 */
final class RateLimiter
{
    public function __construct(private readonly RateLimitStore $store) {}

    public function attempt(string $key, int $maxAttempts, int $windowSeconds): RateLimitResult
    {
        $result = $this->store->hit($key, $windowSeconds);

        return new RateLimitResult(
            $result['count'] <= $maxAttempts,
            $maxAttempts,
            max(0, $maxAttempts - $result['count']),
            $result['ttl'],
        );
    }
}
