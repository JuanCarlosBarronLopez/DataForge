<?php

declare(strict_types=1);

use DataForge\Support\CspNonce;
use DataForge\Support\Theme;

if (!function_exists('e')) {
    /**
     * Escapa un valor para insertarlo en HTML.
     *
     * Aceptar solo cadenas (strict_types) convertia un error de tipo en un
     * fallo 500 con traza. Aqui los escalares se convierten y los tipos
     * compuestos lanzan una excepcion con un mensaje util.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        }

        throw new InvalidArgumentException(sprintf(
            'No se puede escapar un valor de tipo "%s". Conviertelo a cadena antes de imprimirlo.',
            get_debug_type($value),
        ));
    }
}

if (!function_exists('attr')) {
    /**
     * Escapa un valor para insertarlo dentro de un atributo HTML.
     */
    function attr(mixed $value): string
    {
        return e($value);
    }
}

if (!function_exists('csp_nonce')) {
    /**
     * Nonce de la Content Security Policy de la peticion en curso.
     */
    function csp_nonce(): string
    {
        return (new CspNonce())->value();
    }
}

if (!function_exists('theme_label')) {
    /**
     * Nombre legible de un tema, con reserva para valores desconocidos.
     */
    function theme_label(?string $theme): string
    {
        $resolved = Theme::tryFromName((string) $theme);

        return $resolved?->label() ?? 'Grafito';
    }
}

if (!function_exists('format_bytes')) {
    /**
     * Convierte un numero de bytes en una cadena legible.
     */
    function format_bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $index = 0;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            ++$index;
        }

        return round($value, 1).' '.$units[$index];
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Formatea una fecha de MySQL en formato corto y legible.
     */
    function format_datetime(?string $value): string
    {
        if ($value === null || $value === '' || str_starts_with($value, '0000')) {
            return 'sin registro';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? 'sin registro' : date('d/m/Y H:i', $timestamp);
    }
}

if (!function_exists('e_url')) {
    /**
     * Escapa un valor para usarlo dentro de una URL.
     */
    function e_url(?string $value): string
    {
        return rawurlencode((string) $value);
    }
}

if (!function_exists('n')) {
    /**
     * Convierte un valor a entero para poder imprimirlo en un atributo.
     */
    function n(mixed $value): string
    {
        return (string) (int) $value;
    }
}
