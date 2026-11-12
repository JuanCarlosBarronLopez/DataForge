<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Utilidades de cadena y generacion de valores aleatorios.
 */
final class Str
{
    public static function token(int $bytes = 32): string
    {
        return bin2hex(random_bytes(max(1, $bytes)));
    }

    public static function slug(string $value): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $clean = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $ascii === false ? $value : $ascii) ?? '');

        return trim($clean, '-');
    }

    public static function limit(string $value, int $limit, string $suffix = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit)).$suffix;
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
