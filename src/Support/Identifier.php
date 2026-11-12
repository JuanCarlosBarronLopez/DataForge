<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Identificadores de MySQL (bases de datos, tablas y columnas).
 *
 * Todo identificador que llegue desde fuera debe pasar por aqui antes de
 * interpolarse en una sentencia SQL. La validacion estricta con lista blanca
 * es la unica forma segura de interpolar identificadores, porque los
 * prepared statements de PDO no admiten marcadores para nombres de objetos.
 */
final class Identifier
{
    public const MAX_LENGTH = 64;

    private const PATTERN = '/^[A-Za-z0-9_]{1,64}$/';

    public static function isValid(string $name): bool
    {
        return preg_match(self::PATTERN, $name) === 1;
    }

    /**
     * Devuelve el identificador entre acentos graves tras validarlo.
     *
     * @throws \InvalidArgumentException si el identificador no es valido
     */
    public static function quote(string $name): string
    {
        if (!self::isValid($name)) {
            throw new \InvalidArgumentException(sprintf(
                'El identificador "%s" no cumple el formato permitido (letras, digitos y guion bajo, hasta %d caracteres).',
                $name,
                self::MAX_LENGTH,
            ));
        }

        return '`'.$name.'`';
    }

    /**
     * @throws \InvalidArgumentException si el identificador no es valido
     */
    public static function require(string $name): string
    {
        return self::quote($name);
    }
}
