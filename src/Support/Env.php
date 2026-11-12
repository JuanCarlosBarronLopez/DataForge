<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Lector de variables de entorno con soporte para archivos .env.
 *
 * Las variables ya presentes en el entorno real tienen prioridad sobre el
 * archivo .env, de modo que un contenedor de produccion no puede ser
 * sobrescrito por un archivo commiteado por error.
 */
final class Env
{
    private static bool $loaded = false;

    /**
     * @var array<string, string>
     */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key           = trim($key);
            $value         = trim($value, " \t\n\r\0\x0B\"'");

            self::$values[$key] = $value;

            if (getenv($key) === false) {
                putenv($key.'='.$value);
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::$values[$key] ?? null;

        if ($value === null) {
            $env   = getenv($key);
            $value = $env === false ? null : $env;
        }

        if ($value === null) {
            $server = $_SERVER[$key] ?? null;
            $value  = is_scalar($server) ? (string) $server : null;
        }

        return $value ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value === null ? $default : (int) $value;
    }
}
