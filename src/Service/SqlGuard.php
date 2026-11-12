<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Exception\ValidationException;

/**
 * Verificaciones previas a ejecutar una consulta en la consola SQL.
 *
 * La clase existe separada del servicio para que las reglas se puedan probar
 * sin necesitar una conexion a MySQL.
 */
final class SqlGuard
{
    private const ALLOWED_KEYWORDS = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'WITH'];

    private const MAX_LENGTH = 20_000;

    /**
     * Devuelve el enunciado listo para ejecutar o lanza una excepcion.
     *
     * @throws ValidationException
     */
    public function inspect(string $sql, int $maxRows): string
    {
        $sql = trim($sql);

        if ($sql === '') {
            throw new ValidationException(['Escribe la consulta que quieres ejecutar.']);
        }

        if (strlen($sql) > self::MAX_LENGTH) {
            throw new ValidationException(['La consulta es demasiado larga.']);
        }

        $this->assertSingleStatement($sql);
        $this->assertReadOnly($sql);

        return $this->applyRowLimit($sql, $maxRows);
    }

    private function assertSingleStatement(string $sql): void
    {
        $trimmed = rtrim($this->stripLiteralsAndComments($sql), "; \t\n\r");

        if (str_contains($trimmed, ';')) {
            throw new ValidationException([
                'La consola ejecuta un unico enunciado por consulta. Elimina los punto y coma intermedios.',
            ]);
        }
    }

    private function assertReadOnly(string $sql): void
    {
        $normalized = strtoupper(ltrim($this->stripLiteralsAndComments($sql)));

        foreach (self::ALLOWED_KEYWORDS as $keyword) {
            if ($normalized === $keyword || str_starts_with($normalized, $keyword.' ')) {
                return;
            }
        }

        throw new ValidationException([
            'La consola solo admite consultas de lectura (SELECT, SHOW, DESCRIBE, EXPLAIN).',
        ]);
    }

    private function applyRowLimit(string $sql, int $maxRows): string
    {
        $withoutComments = $this->stripLiteralsAndComments($sql);

        if (preg_match('/\bLIMIT\b/i', $withoutComments) === 1) {
            return $sql;
        }

        // Un comentario de linea al final dejaria el LIMIT comentado, asi que
        // se retira antes de anadir el limite.
        $trimmed = preg_replace('/(--[^\n]*|#[^\n]*|\/\*.*?\*\/)\s*$/s', '', rtrim($sql));

        return rtrim($trimmed ?? $sql, " \t\n\r;").sprintf(' LIMIT %d', $maxRows);
    }

    /**
     * Elimina literales de cadena, identificadores y comentarios para poder
     * inspeccionar la estructura del enunciado sin falsos positivos.
     */
    private function stripLiteralsAndComments(string $sql): string
    {
        $pattern = "/('(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\"|`(?:[^`\\\\]|\\\\.)*`|--[^\n]*|#[^\n]*|\/\*.*?\*\/)/s";

        $cleaned = preg_replace($pattern, ' ', $sql);

        return $cleaned ?? $sql;
    }
}
