<?php

declare(strict_types=1);

namespace DataForge\Http\Middleware;

use DataForge\Core\Middleware;
use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Service\AuthService;

/**
 * Exige una sesion iniciada. Las rutas que lo usan se declaran de forma
 * explicita en config/routes.php.
 */
final class RequireAuthMiddleware implements Middleware
{
    public function __construct(private readonly AuthService $auth) {}

    public function process(Request $request, callable $next): Response
    {
        if (!$this->auth->isAuthenticated()) {
            return Response::redirect('/login?next='.rawurlencode($request->fullPath()));
        }

        return $next($request);
    }
}
