<?php

declare(strict_types=1);

use DataForge\Support\CspNonce;
use DataForge\Support\Theme;

if (!function_exists('e')) {
    /**
     * Escapa un valor para insertarlo en HTML.
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('attr')) {
    /**
     * Escapa un valor para insertarlo dentro de un atributo HTML.
     */
    function attr(?string $value): string
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
