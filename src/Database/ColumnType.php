<?php

declare(strict_types=1);

namespace DataForge\Database;

/**
 * Tipos de columna soportados por el constructor de tablas.
 */
enum ColumnType: string
{
    case Int       = 'INT';
    case BigInt    = 'BIGINT';
    case Varchar   = 'VARCHAR';
    case Text      = 'TEXT';
    case Decimal   = 'DECIMAL';
    case Float     = 'FLOAT';
    case Boolean   = 'BOOLEAN';
    case Date      = 'DATE';
    case DateTime  = 'DATETIME';
    case Timestamp = 'TIMESTAMP';
    case Json      = 'JSON';

    public function sql(bool $nullable, ?int $length, ?string $default, bool $autoIncrement): string
    {
        $parts = [match ($this) {
            self::Int       => 'INT',
            self::BigInt    => 'BIGINT',
            self::Varchar   => 'VARCHAR('.($length !== null && $length > 0 ? $length : 255).')',
            self::Text      => 'TEXT',
            self::Decimal   => 'DECIMAL(12, 4)',
            self::Float     => 'DOUBLE',
            self::Boolean   => 'TINYINT(1)',
            self::Date      => 'DATE',
            self::DateTime  => 'DATETIME',
            self::Timestamp => 'TIMESTAMP',
            self::Json      => 'JSON',
        }];

        if ($autoIncrement && $this === self::Int) {
            $parts[] = 'AUTO_INCREMENT';
            $parts[] = 'PRIMARY KEY';

            return implode(' ', $parts);
        }

        $parts[] = $nullable ? 'NULL' : 'NOT NULL';

        if ($default !== null && $default !== '') {
            $parts[] = 'DEFAULT '.(is_numeric($default) ? $default : "'".str_replace("'", "''", $default)."'");
        }

        return implode(' ', $parts);
    }

    public static function fromInput(string $value): ?self
    {
        return self::tryFrom(strtoupper(trim($value)));
    }
}
