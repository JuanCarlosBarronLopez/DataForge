<?php

declare(strict_types=1);

namespace DataForge\Http\Middleware;

use DataForge\Core\Middleware;
use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Security\SecurityHeaders;
use DataForge\Support\CspNonce;

/**
 * Anade las cabeceras de seguridad a la respuesta, incluida la CSP con un
 * nonce propio de cada peticion.
 */
final class SecurityHeadersMiddleware implements Middleware
{
    public function __construct(
        private readonly SecurityHeaders $headers,
        private readonly CspNonce $nonce,
    ) {}

    public function process(Request $request, callable $next): Response
    {
        // Un nonce por peticion: la plantilla y la cabecera comparten el mismo.
        $this->nonce->reset();
        $nonce = $this->nonce->generate();

        $response = $next($request);

        foreach ($this->headers->build($nonce) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
