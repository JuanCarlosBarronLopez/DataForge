<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Una ruta registrada en el enrutador.
 */
final class Route
{
    /**
     * @param list<class-string<Middleware>> $middleware
     * @param list<string>                   $params
     * @param class-string|callable          $handler
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $regex,
        public readonly array $params,
        public readonly mixed $handler,
        public readonly array $middleware,
    ) {}
}
