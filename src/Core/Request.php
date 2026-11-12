<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Representacion inmutable de una peticion HTTP entrante.
 */
final class Request
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $body
     * @param array<string, mixed>  $files
     * @param array<string, string> $cookies
     * @param array<string, mixed>  $server
     * @param array<string, string> $headers
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $cookies,
        private readonly array $server,
        private readonly array $headers,
    ) {}

    /**
     * Construye una peticion de forma explicita. Se usa en las pruebas.
     *
     * @param array<string, mixed>  $body
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $files
     * @param array<string, string> $headers
     */
    public static function create(
        string $method,
        string $path,
        array $body = [],
        array $query = [],
        array $files = [],
        array $headers = [],
    ): self {
        $server = [
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI'    => $path.($query === [] ? '' : '?'.http_build_query($query)),
            'REMOTE_ADDR'    => '127.0.0.1',
            'SERVER_NAME'    => 'localhost',
            'HTTP_HOST'      => 'localhost',
        ];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return new self(
            strtoupper($method),
            self::normalizePath(parse_url($path, PHP_URL_PATH) ?: '/'),
            $query,
            $body,
            $files,
            [],
            $server,
            $headers,
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function fromGlobals(array $overrides = []): self
    {
        $server = $overrides['server'] ?? $_SERVER;
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $uri    = (string) ($server['REQUEST_URI'] ?? '/');
        $path   = parse_url($uri, PHP_URL_PATH);
        $path   = is_string($path) ? $path : '/';

        $body = $overrides['body'] ?? $_POST;
        if ($body === [] && str_contains((string) ($server['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self(
            $method,
            self::normalizePath($path),
            $overrides['query'] ?? $_GET,
            $body,
            $overrides['files']   ?? $_FILES,
            $overrides['cookies'] ?? $_COOKIE,
            $server,
            self::extractHeaders($server),
        );
    }

    /**
     * @param array<string, mixed> $server
     *
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with((string) $key, 'HTTP_')) {
                $name           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr((string) $key, 5)))));
                $headers[$name] = (string) $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $server['CONTENT_TYPE'];
        }

        return $headers;
    }

    private static function normalizePath(string $path): string
    {
        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function query(string $key, ?string $default = null): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function queryParams(): array
    {
        return $this->query;
    }

    public function input(string $key, ?string $default = null): ?string
    {
        $value = $this->body[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[$name] ?? $default;
    }

    public function server(string $key, ?string $default = null): ?string
    {
        $value = $this->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function serverParams(): array
    {
        return $this->server;
    }

    public function fullPath(): string
    {
        $query = http_build_query($this->query);

        return $query === '' ? $this->path : $this->path.'?'.$query;
    }
}
