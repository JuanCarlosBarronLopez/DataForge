<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Contrato de middleware. Cada middleware envuelve la peticion y decide si la
 * deja continuar hacia el siguiente eslabon de la cadena.
 */
interface Middleware
{
    /**
     * @param callable(Request): Response $next
     */
    public function process(Request $request, callable $next): Response;
}
