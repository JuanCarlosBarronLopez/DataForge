<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Modo de color de la interfaz.
 */
enum ColorMode: string
{
    case Dark  = 'dark';
    case Light = 'light';

    public function label(): string
    {
        return match ($this) {
            self::Dark  => 'Oscuro',
            self::Light => 'Claro',
        };
    }

    public static function tryFromName(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
