<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Resultado de una comprobacion de rate limiting.
 */
final class RateLimitResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $retryAfter,
    ) {}
}
