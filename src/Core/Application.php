<?php

declare(strict_types=1);

namespace DataForge\Core;

use DataForge\Http\Middleware\ErrorHandlerMiddleware;
use DataForge\Http\Middleware\RateLimitMiddleware;
use DataForge\Http\Middleware\SecurityHeadersMiddleware;
use DataForge\Http\Middleware\StartSessionMiddleware;
use DataForge\Providers\AppServiceProvider;
use DataForge\Support\Env;

/**
 * Nucleo de la aplicacion: arma el contenedor, registra las rutas y ejecuta
 * la peticion a traves de la cadena de middleware.
 */
final class Application
{
    private readonly Container $container;

    private readonly Router $router;

    public function __construct(private readonly string $basePath, ?Config $config = null)
    {
        Env::load($this->basePath.'/.env');

        $config ??= Config::fromDirectory($this->basePath.'/config');

        $this->container = new Container();
        (new AppServiceProvider())->register($this->container, $config);

        $this->router = new Router();
        $this->registerRoutes($config);
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function handle(Request $request): Response
    {
        $pipeline = new MiddlewarePipeline($this->container, [
            SecurityHeadersMiddleware::class,
            ErrorHandlerMiddleware::class,
            StartSessionMiddleware::class,
            RateLimitMiddleware::class,
        ]);

        return $pipeline->handle(
            $request,
            fn(Request $request): Response => $this->router->dispatch($request, $this->container),
        );
    }

    public function run(): void
    {
        $this->handle(Request::fromGlobals())->send();
    }

    private function registerRoutes(Config $config): void
    {
        /** @var list<array{0: string, 1: string, 2: mixed, 3?: list<class-string<Middleware>>}> $routes */
        $routes = require $this->basePath.'/config/routes.php';

        foreach ($routes as $definition) {
            $method     = $definition[0];
            $path       = $definition[1];
            $handler    = $definition[2];
            $middleware = $definition[3] ?? [];

            $this->router->add($method, $path, $handler, $middleware);
        }
    }
}
