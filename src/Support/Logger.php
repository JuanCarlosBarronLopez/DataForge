<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Registro de eventos de la aplicacion.
 *
 * Los registros se escriben en archivos diarios dentro de storage/logs y se
 * rotan de forma automatica. Las claves sensibles nunca se escriben.
 */
final class Logger
{
    private const LEVELS = [
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    ];

    private const SENSITIVE_KEYS = [
        'password', 'pass', 'secret', 'token', 'authorization', 'cookie',
        'password_hash', 'api_key', 'app_key', 'csrf', 'csrf_token',
    ];

    /**
     * @param string $directory Ruta absoluta del directorio de registros
     * @param string $minLevel  Nivel minimo que se escribe
     */
    public function __construct(
        private readonly string $directory,
        private readonly string $minLevel = 'debug',
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function write(string $level, string $message, array $context): void
    {
        if ((self::LEVELS[$level] ?? 0) < (self::LEVELS[$this->minLevel] ?? 0)) {
            return;
        }

        if (!is_dir($this->directory) && !@mkdir($this->directory, 0o750, true) && !is_dir($this->directory)) {
            return;
        }

        $entry = [
            'ts'      => date('c'),
            'level'   => $level,
            'message' => $message,
            'context' => self::redact($context),
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = json_encode(['ts' => date('c'), 'level' => $level, 'message' => $message]);
        }

        @file_put_contents(
            $this->directory.'/app-'.date('Y-m-d').'.log',
            $line.PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );

        $this->rotate();
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            $normalized = strtolower((string) $key);
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($normalized, $sensitive)) {
                    $clean[$key] = '[redactado]';

                    continue 2;
                }
            }

            $clean[$key] = is_array($value) ? self::redact($value) : $value;
        }

        return $clean;
    }

    private function rotate(): void
    {
        $files = glob($this->directory.'/app-*.log') ?: [];
        if (count($files) <= 30) {
            return;
        }

        sort($files);
        foreach (array_slice($files, 0, count($files) - 30) as $file) {
            @unlink($file);
        }
    }
}
