<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Representacion inmutable de una respuesta HTTP saliente.
 *
 * Una respuesta puede ser normal (con cuerpo) o en flujo: en ese caso el
 * cuerpo lo produce un callback que se ejecuta al enviar la respuesta, lo
 * que permite servidores de eventos (SSE) sin buffering.
 */
final class Response
{
    /** @var callable|null */
    private $streamer;

    /**
     * @param array<string, string> $headers
     */
    private function __construct(
        private readonly string $body,
        private readonly int $status = 200,
        private readonly array $headers = [],
        ?callable $streamer = null,
    ) {
        $this->streamer = $streamer;
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * @param mixed $data
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self(
            $encoded === false ? '{}' : $encoded,
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public static function download(string $body, string $filename, string $contentType): self
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? 'descarga';

        return new self($body, 200, [
            'Content-Type'           => $contentType,
            'Content-Disposition'    => 'attachment; filename="'.$safeName.'"',
            'Content-Length'         => (string) strlen($body),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Respuesta con contenido binario que se muestra en el navegador.
     *
     * @param array<string, string> $headers
     */
    public static function binary(string $body, string $contentType, array $headers = []): self
    {
        return new self($body, 200, array_merge([
            'Content-Type'           => $contentType,
            'Content-Length'         => (string) strlen($body),
            'X-Content-Type-Options' => 'nosniff',
        ], $headers));
    }

    /**
     * Respuesta en flujo. El callback recibe un emisor con el que escribir
     * cada fragmento.
     *
     * @param callable(callable(string): void): void $streamer
     */
    public static function stream(callable $streamer): self
    {
        return new self('', 200, [
            'Content-Type'      => 'text/event-stream; charset=UTF-8',
            'Cache-Control'     => 'no-cache, no-store, no-transform',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ], $streamer);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): self
    {
        $headers        = $this->headers;
        $headers[$name] = $value;

        return new self($this->body, $this->status, $headers, $this->streamer);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name.': '.$value);
            }
        }

        if ($this->streamer !== null) {
            ($this->streamer)();

            return;
        }

        echo $this->body;
    }
}
