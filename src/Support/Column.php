<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Utilidades para presentar la estructura de una tabla en un formulario.
 *
 * Devuelven siempre valores de un conjunto cerrado, de modo que las
 * plantillas pueden usarlos sin escapar.
 */
final class Column
{
    /**
     * Tipo de control HTML adecuado para un tipo de columna de MySQL.
     */
    public static function inputType(string $sqlType): string
    {
        $upper = strtoupper($sqlType);

        return match (true) {
            str_contains($upper, 'INT'),
            str_contains($upper, 'DECIMAL'),
            str_contains($upper, 'DOUBLE'),
            str_contains($upper, 'FLOAT') => 'number',
            str_contains($upper, 'DATETIME'),
            str_contains($upper, 'TIMESTAMP') => 'datetime-local',
            str_contains($upper, 'DATE')      => 'date',
            default                           => 'text',
        };
    }

    /**
     * Longitud declarada de una columna, o 255 si no la tiene.
     */
    public static function length(string $sqlType): int
    {
        if (preg_match('/\((\d+)\)/', $sqlType, $matches) === 1) {
            return (int) $matches[1];
        }

        return 255;
    }

    /**
     * Indica si la columna se representa con una casilla de verificacion.
     */
    public static function isBoolean(string $sqlType): bool
    {
        $upper = strtoupper($sqlType);

        return str_starts_with($upper, 'TINYINT(1)') || str_starts_with($upper, 'BOOLEAN');
    }
}
