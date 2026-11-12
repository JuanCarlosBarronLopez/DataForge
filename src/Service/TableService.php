<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Database\ColumnDefinition;
use DataForge\Database\ConnectionFactory;
use DataForge\Exception\ValidationException;
use DataForge\Support\Identifier;

/**
 * Operaciones sobre la estructura de las tablas.
 */
final class TableService
{
    public function __construct(private readonly ConnectionFactory $connections) {}

    /**
     * @return list<string>
     */
    public function list(string $database): array
    {
        $connection = $this->connections->forDatabase($database);

        $statement = $connection->query('SHOW TABLES');
        if ($statement === false) {
            return [];
        }

        /** @var list<string> $tables */
        $tables = $statement->fetchAll(\PDO::FETCH_COLUMN);

        return $tables;
    }

    public function exists(string $database, string $table): bool
    {
        return in_array($table, $this->list($database), true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function describe(string $database, string $table): array
    {
        $connection = $this->connections->forDatabase($database);

        $statement = $connection->query(sprintf('DESCRIBE `%s`', $table));
        if ($statement === false) {
            return [];
        }

        /** @var list<array<string, mixed>> $columns */
        $columns = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $columns;
    }

    /**
     * @return list<string>
     */
    public function columnNames(string $database, string $table): array
    {
        return array_map(
            static fn(array $column): string => (string) $column['Field'],
            $this->describe($database, $table),
        );
    }

    /**
     * Nombre de la clave primaria de la tabla.
     */
    public function primaryKey(string $database, string $table): string
    {
        if (!Identifier::isValid($table)) {
            return 'id';
        }

        $connection = $this->connections->forDatabase($database);

        $statement = $connection->prepare(
            sprintf('SHOW KEYS FROM `%s` WHERE `Key_name` = :key', $table),
        );
        $statement->execute(['key' => 'PRIMARY']);

        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row) || !isset($row['Column_name'])) {
            return 'id';
        }

        return (string) $row['Column_name'];
    }

    /**
     * Crea una tabla con las columnas indicadas.
     *
     * Toda tabla nace con una columna `id` autoincrementable que actua como
     * clave primaria, que es la que usa el resto de la aplicacion para
     * referirse a un registro.
     *
     * @param list<ColumnDefinition> $columns
     *
     * @throws ValidationException
     */
    public function create(string $database, string $table, array $columns): void
    {
        if (!Identifier::isValid($table)) {
            throw new ValidationException(['El nombre de la tabla no es valido.']);
        }

        if ($columns === []) {
            throw new ValidationException(['Define al menos una columna para la tabla.']);
        }

        $definitions = ['`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'];

        foreach ($columns as $column) {
            if ($column->name === 'id') {
                continue;
            }

            $definitions[] = sprintf(
                '`%s` %s',
                $column->name,
                $column->type->sql($column->nullable, $column->length, $column->default, false),
            );
        }

        $sql = sprintf(
            'CREATE TABLE `%s` (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $table,
            implode(', ', $definitions),
        );

        $connection = $this->connections->forDatabase($database);

        try {
            $connection->exec($sql);
        } catch (\PDOException $exception) {
            if (str_contains($exception->getMessage(), 'Duplicate column name')) {
                throw new ValidationException(['La tabla ya tiene una columna con ese nombre.']);
            }

            if (str_contains($exception->getMessage(), 'already exists')) {
                throw new ValidationException(['Ya existe una tabla con ese nombre en esta base de datos.']);
            }

            throw new ValidationException(['El servidor MySQL no pudo crear la tabla. Revisa el tipo de los campos.']);
        }
    }

    public function drop(string $database, string $table): void
    {
        if (!Identifier::isValid($table)) {
            throw new ValidationException(['El nombre de la tabla no es valido.']);
        }

        $connection = $this->connections->forDatabase($database);
        $connection->exec(sprintf('DROP TABLE `%s`', $table));
    }

    /**
     * Anade o modifica columnas.
     *
     * @param list<ColumnDefinition> $columns
     *
     * @throws ValidationException
     */
    public function alter(string $database, string $table, array $columns): void
    {
        if (!Identifier::isValid($table)) {
            throw new ValidationException(['El nombre de la tabla no es valido.']);
        }

        if ($columns === []) {
            throw new ValidationException(['No se detectaron cambios en la estructura.']);
        }

        $existing = $this->columnNames($database, $table);

        $statements = [];
        foreach ($columns as $column) {
            $definition = sprintf(
                '`%s` %s',
                $column->name,
                $column->type->sql($column->nullable, $column->length, $column->default, false),
            );

            if (!in_array($column->name, $existing, true)) {
                $statements[] = 'ADD COLUMN '.$definition;

                continue;
            }

            $statements[] = 'MODIFY COLUMN '.$definition;
        }

        $connection = $this->connections->forDatabase($database);

        try {
            $connection->exec(sprintf(
                'ALTER TABLE `%s` %s',
                $table,
                implode(', ', $statements),
            ));
        } catch (\PDOException $exception) {
            throw new ValidationException(['El servidor MySQL no pudo aplicar los cambios de estructura.']);
        }
    }

    public function dropColumn(string $database, string $table, string $column): void
    {
        if (!Identifier::isValid($table) || !Identifier::isValid($column)) {
            throw new ValidationException(['El nombre de la tabla o de la columna no es valido.']);
        }

        $connection = $this->connections->forDatabase($database);

        try {
            $connection->exec(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $table, $column));
        } catch (\PDOException $exception) {
            throw new ValidationException(['El servidor MySQL no pudo eliminar la columna.']);
        }
    }
}
