<?php

declare(strict_types=1);

namespace DataForge\Http\Middleware;

use DataForge\Core\Middleware;
use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Security\Csrf;
use DataForge\Support\Logger;

/**
 * Verifica el token CSRF de las peticiones que modifican estado.
 *
 * El token se liga a la accion, de modo que un token capturado en un
 * formulario no sirve para ejecutar otra operacion.
 */
final class VerifyCsrfMiddleware implements Middleware
{
    public function __construct(
        private readonly Csrf $csrf,
        private readonly Logger $logger,
    ) {}

    public function process(Request $request, callable $next): Response
    {
        if (!$request->isPost()) {
            return $next($request);
        }

        $action = $request->path();
        $token  = $request->input('csrf_token', '') ?? '';

        if (!$this->csrf->validate($action, $token)) {
            $this->logger->warning('Token CSRF invalido', [
                'path'   => $request->path(),
                'method' => $request->method(),
            ]);

            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>403</title></head>'
                .'<body><h1>403 Token de seguridad invalido</h1>'
                .'<p>La sesion del formulario expiro. Recarga la pagina e intentalo de nuevo.</p></body></html>',
                403,
            );
        }

        return $next($request);
    }
}
