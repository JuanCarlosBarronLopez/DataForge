<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Enrutador con parametros de ruta y middleware por ruta.
 *
 * Los handlers se registran como [Clase::class, 'metodo'] y se resuelven desde
 * el contenedor de dependencias.
 */
final class Router
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    /**
     * @param list<class-string<Middleware>> $middleware
     */
    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /**
     * @param list<class-string<Middleware>> $middleware
     */
    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /**
     * @param list<class-string<Middleware>> $middleware
     */
    public function add(string $method, string $path, mixed $handler, array $middleware = []): void
    {
        [$regex, $params] = self::compile($path);

        $this->routes[] = new Route($method, $path, $regex, $params, $handler, $middleware);
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private static function compile(string $path): array
    {
        $params = [];

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $matches) use (&$params): string {
                $params[] = $matches[1];

                return '(?P<'.$matches[1].'>[^/]+)';
            },
            $path,
        );

        if (!is_string($regex)) {
            throw new \RuntimeException(sprintf('La ruta "%s" no se pudo compilar.', $path));
        }

        return ['#^'.$regex.'$#u', $params];
    }

    public function dispatch(Request $request, Container $container): Response
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();

        foreach ($this->routes as $route) {
            if ($route->method !== $method) {
                continue;
            }

            if (preg_match($route->regex, $request->path(), $matches) !== 1) {
                continue;
            }

            $arguments = [];
            foreach ($route->params as $param) {
                $arguments[] = rawurldecode($matches[$param] ?? '');
            }

            $handler = function (Request $request) use ($route, $arguments, $container): Response {
                return $this->call($route->handler, $arguments, $container, $request);
            };

            if ($route->middleware === []) {
                return $handler($request);
            }

            $pipeline = new MiddlewarePipeline($container, $route->middleware);

            return $pipeline->handle($request, $handler);
        }

        return $this->methodNotAllowed($method, $request->path());
    }

    private function methodNotAllowed(string $method, string $path): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if ($route->method !== $method && preg_match($route->regex, $path) === 1) {
                $allowed[] = $route->method;
            }
        }

        if ($allowed !== []) {
            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>405</title></head>'
                .'<body><h1>405 Metodo no permitido</h1></body></html>',
                405,
            )->withHeader('Allow', implode(', ', array_unique($allowed)));
        }

        return Response::html(
            '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>404</title></head>'
            .'<body><h1>404 Pagina no encontrada</h1></body></html>',
            404,
        );
    }

    /**
     * @param list<string> $arguments
     */
    private function call(mixed $handler, array $arguments, Container $container, Request $request): Response
    {
        if (is_callable($handler) && !is_array($handler)) {
            /** @var Response $response */
            $response = $handler(...$arguments);

            return $response;
        }

        if (!is_array($handler) || count($handler) !== 2) {
            throw new \RuntimeException('El handler de la ruta no es valido.');
        }

        [$class, $method] = $handler;
        $controller       = $container->get($class);

        if ($controller instanceof \DataForge\Http\RequestAware) {
            $controller->setRequest($request);
        }

        $result = $controller->{$method}(...$this->coerceArguments($class, $method, $arguments));

        if (!$result instanceof Response) {
            throw new \RuntimeException(sprintf(
                'El controlador %s::%s debe devolver una instancia de Response.',
                is_string($class) ? $class : '(closure)',
                is_string($method) ? $method : '(closure)',
            ));
        }

        return $result;
    }

    /**
     * Los parametros de ruta llegan como cadenas. Se convierten al tipo que
     * declara la accion, para que un identificador numerico no provoque un
     * error de tipo en un archivo con strict_types.
     *
     * @param class-string         $class
     * @param list<string>         $arguments
     *
     * @return list<mixed>
     */
    private function coerceArguments(string $class, string $method, array $arguments): array
    {
        $reflection = new \ReflectionMethod($class, $method);

        foreach ($reflection->getParameters() as $index => $parameter) {
            if (!isset($arguments[$index]) || !is_string($arguments[$index])) {
                continue;
            }

            $type = $parameter->getType();
            if (!$type instanceof \ReflectionNamedType) {
                continue;
            }

            $arguments[$index] = match ($type->getName()) {
                'int'   => (int) $arguments[$index],
                'float' => (float) $arguments[$index],
                'bool'  => filter_var($arguments[$index], FILTER_VALIDATE_BOOLEAN),
                default => $arguments[$index],
            };
        }

        return $arguments;
    }
}
