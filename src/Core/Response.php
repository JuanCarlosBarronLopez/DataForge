<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Representacion inmutable de una respuesta HTTP saliente.
 */
final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $body,
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {}

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

        return new self($this->body, $this->status, $headers);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name.': '.$value);
            }
        }

        echo $this->body;
    }
}
