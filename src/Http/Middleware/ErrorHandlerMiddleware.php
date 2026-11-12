<?php

declare(strict_types=1);

namespace DataForge\Http\Middleware;

use DataForge\Core\Middleware;
use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Support\Logger;

/**
 * Captura los errores no controlados y devuelve una pagina de error sin
 * filtrar detalles internos al navegador.
 */
final class ErrorHandlerMiddleware implements Middleware
{
    public function __construct(
        private readonly Logger $logger,
        private readonly bool $debug = false,
    ) {}

    public function process(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $exception) {
            $this->logger->error('Error no controlado', [
                'type'    => $exception::class,
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'path'    => $request->path(),
            ]);

            $details = '';
            if ($this->debug) {
                $details = sprintf(
                    '<pre>%s</pre>',
                    htmlspecialchars(
                        $exception::class.': '.$exception->getMessage()."\n"
                        .$exception->getFile().':'.$exception->getLine()."\n\n"
                        .$exception->getTraceAsString(),
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8',
                    ),
                );
            }

            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>500</title>'
                .'<link rel="stylesheet" href="/assets/css/app.css"></head>'
                .'<body class="error-page"><main><h1>500 Error interno</h1>'
                .'<p>La solicitud no pudo completarse. El error quedo registrado en el log del servidor.</p>'
                .$details
                .'<p><a href="/">Volver al inicio</a></p></main></body></html>',
                500,
            );
        }
    }
}
