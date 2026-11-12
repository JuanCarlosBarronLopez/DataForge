<?php

declare(strict_types=1);

namespace DataForge\Http\Middleware;

use DataForge\Core\Middleware;
use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Security\ClientIp;
use DataForge\Security\RateLimiter;
use DataForge\Support\Logger;

/**
 * Limita el numero de peticiones por direccion IP.
 *
 * Las rutas de autenticacion usan un limite mas estricto, de modo que el
 * ataque de fuerza bruta no depende solo del limite global.
 */
final class RateLimitMiddleware implements Middleware
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly ClientIp $clientIp,
        private readonly Logger $logger,
        private readonly int $globalMax = 120,
        private readonly int $globalWindow = 60,
        private readonly int $loginMax = 10,
        private readonly int $loginWindow = 300,
    ) {}

    public function process(Request $request, callable $next): Response
    {
        $ip         = $this->clientIp->resolve($request);
        $isAuthPath = $this->isAuthenticationPath($request->path());

        $max    = $isAuthPath ? $this->loginMax : $this->globalMax;
        $window = $isAuthPath ? $this->loginWindow : $this->globalWindow;
        $scope  = $isAuthPath ? 'auth' : 'global';

        $result = $this->limiter->attempt($scope.':'.$ip, $max, $window);

        if (!$result->allowed) {
            $this->logger->warning('Limite de peticiones alcanzado', [
                'scope' => $scope,
                'path'  => $request->path(),
            ]);

            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>429</title></head>'
                .'<body><h1>429 Demasiadas solicitudes</h1>'
                .'<p>Espera '.$result->retryAfter.' segundos antes de volver a intentarlo.</p></body></html>',
                429,
            )
                ->withHeader('Retry-After', (string) $result->retryAfter)
                ->withHeader('X-RateLimit-Limit', (string) $result->limit)
                ->withHeader('X-RateLimit-Remaining', '0');
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $result->limit)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining);
    }

    private function isAuthenticationPath(string $path): bool
    {
        return in_array($path, ['/login', '/register', '/logout'], true);
    }
}
