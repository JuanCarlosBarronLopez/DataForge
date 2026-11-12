<?php

declare(strict_types=1);

namespace DataForge\Http\Middleware;

use DataForge\Core\Middleware;
use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Security\SessionManager;

/**
 * Inicia la sesion antes de que cualquier middleware toque los datos del
 * usuario autenticado.
 */
final class StartSessionMiddleware implements Middleware
{
    public function __construct(private readonly SessionManager $session) {}

    public function process(Request $request, callable $next): Response
    {
        $this->session->start();

        return $next($request);
    }
}
