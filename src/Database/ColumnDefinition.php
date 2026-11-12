<?php

declare(strict_types=1);

namespace DataForge\Database;

use DataForge\Support\Identifier;

/**
 * Definicion de una columna de tabla.
 */
final class ColumnDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly ColumnType $type,
        public readonly bool $nullable = false,
        public readonly ?int $length = null,
        public readonly ?string $default = null,
        public readonly bool $autoIncrement = false,
    ) {}

    /**
     * @param array<string, mixed> $input
     */
    public static function fromInput(array $input): ?self
    {
        $name = trim((string) ($input['name'] ?? ''));
        $type = ColumnType::fromInput((string) ($input['type'] ?? ''));

        if ($name === '' || $type === null || !Identifier::isValid($name)) {
            return null;
        }

        $length = isset($input['length']) && $input['length'] !== '' ? (int) $input['length'] : null;
        if ($type === ColumnType::Varchar && ($length === null || $length < 1 || $length > 16_383)) {
            return null;
        }

        $default = isset($input['default']) ? trim((string) $input['default']) : null;

        return new self(
            $name,
            $type,
            filter_var($input['nullable'] ?? false, FILTER_VALIDATE_BOOLEAN),
            $length,
            $default === '' ? null : $default,
            filter_var($input['auto_increment'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }
}
