<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Nonce de la Content Security Policy, compartido por la peticion en curso.
 *
 * El valor vive en una propiedad estatica para que las plantillas puedan
 * obtenerlo sin recibir el objeto por parametro.
 */
final class CspNonce
{
    private static string $value = '';

    public function generate(): string
    {
        if (self::$value === '') {
            self::$value = base64_encode(random_bytes(16));
        }

        return self::$value;
    }

    public function value(): string
    {
        return self::$value !== '' ? self::$value : $this->generate();
    }

    public static function reset(): void
    {
        self::$value = '';
    }
}
