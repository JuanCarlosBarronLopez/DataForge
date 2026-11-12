<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Cadena de middleware. El primer middleware registrado es el mas externo.
 */
final class MiddlewarePipeline
{
    /**
     * @param list<class-string<Middleware>|Middleware> $middleware
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $middleware,
    ) {}

    /**
     * @param callable(Request): Response $destination
     */
    public function handle(Request $request, callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function (callable $next, string|Middleware $layer): callable {
                return function (Request $request) use ($next, $layer): Response {
                    $instance = is_string($layer) ? $this->container->get($layer) : $layer;

                    return $instance->process($request, $next);
                };
            },
            $destination,
        );

        return $pipeline($request);
    }
}
