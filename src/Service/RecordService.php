<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Database\ConnectionFactory;
use DataForge\Exception\ValidationException;
use DataForge\Support\Identifier;

/**
 * Operaciones CRUD sobre los registros de una tabla.
 *
 * Los nombres de columna que llegan desde el formulario se filtran contra la
 * estructura real de la tabla antes de construir la sentencia.
 */
final class RecordService
{
    public function __construct(
        private readonly ConnectionFactory $connections,
        private readonly TableService $tables,
    ) {}

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, primaryKey: string}
     */
    public function paginate(
        string $database,
        string $table,
        int $limit,
        int $offset,
        string $sort = '',
        string $direction = 'asc',
        string $search = '',
    ): array {
        $this->assertTable($table);

        $limit  = max(1, min($limit, 200));
        $offset = max(0, $offset);

        $columns = $this->tables->columnNames($database, $table);
        $primary = $this->tables->primaryKey($database, $table);

        [$where, $params] = $this->buildSearch($columns, $search);

        $connection = $this->connections->forDatabase($database);

        $countStatement = $connection->prepare(sprintf('SELECT COUNT(*) FROM `%s`%s', $table, $where));
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $orderBy = '';
        if ($sort !== '' && in_array($sort, $columns, true)) {
            $orderBy = sprintf(' ORDER BY `%s` %s', $sort, $direction === 'desc' ? 'DESC' : 'ASC');
        }

        $statement = $connection->prepare(sprintf(
            'SELECT * FROM `%s`%s%s LIMIT :limit OFFSET :offset',
            $table,
            $where,
            $orderBy,
        ));

        foreach ($params as $key => $value) {
            $statement->bindValue(':'.$key, $value, \PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return ['rows' => $rows, 'total' => $total, 'primaryKey' => $primary];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $database, string $table, int $id): ?array
    {
        $this->assertTable($table);

        $primary = $this->tables->primaryKey($database, $table);
        if (!Identifier::isValid($primary)) {
            return null;
        }

        $connection = $this->connections->forDatabase($database);

        $statement = $connection->prepare(sprintf('SELECT * FROM `%s` WHERE `%s` = :id LIMIT 1', $table, $primary));
        $statement->execute(['id' => $id]);

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Inserta un registro y devuelve su identificador.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $database, string $table, array $data): int
    {
        $this->assertTable($table);

        $allowed = $this->tables->columnNames($database, $table);
        $values  = $this->filter($data, $allowed);

        if ($values === []) {
            throw new ValidationException(['No hay ningun valor valido para guardar.']);
        }

        $columns      = array_keys($values);
        $placeholders = array_map(static fn(string $column): string => ':'.$column, $columns);

        $connection = $this->connections->forDatabase($database);

        $statement = $connection->prepare(sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders),
        ));

        $this->bindValues($statement, $values);
        $statement->execute();

        return (int) $connection->lastInsertId();
    }

    /**
     * Actualiza un registro.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $database, string $table, int $id, array $data): void
    {
        $this->assertTable($table);

        $allowed = $this->tables->columnNames($database, $table);
        $values  = $this->filter($data, $allowed);

        if ($values === []) {
            throw new ValidationException(['No hay ningun valor valido para actualizar.']);
        }

        $primary = $this->tables->primaryKey($database, $table);
        if (!Identifier::isValid($primary)) {
            throw new ValidationException(['La tabla no tiene una clave primaria utilizable.']);
        }

        $assignments = [];
        foreach (array_keys($values) as $column) {
            $assignments[] = sprintf('`%s` = :%s', $column, $column);
        }

        $connection = $this->connections->forDatabase($database);

        $statement = $connection->prepare(sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = :__id',
            $table,
            implode(', ', $assignments),
            $primary,
        ));

        foreach ($values as $column => $value) {
            $this->bindColumn($statement, (string) $column, $value);
        }
        $statement->bindValue(':__id', $id, \PDO::PARAM_INT);
        $statement->execute();
    }

    public function delete(string $database, string $table, int $id): void
    {
        $this->assertTable($table);

        $primary = $this->tables->primaryKey($database, $table);
        if (!Identifier::isValid($primary)) {
            throw new ValidationException(['La tabla no tiene una clave primaria utilizable.']);
        }

        $connection = $this->connections->forDatabase($database);

        $statement = $connection->prepare(sprintf('DELETE FROM `%s` WHERE `%s` = :id', $table, $primary));
        $statement->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $allowed
     *
     * @return array<string, mixed>
     */
    private function filter(array $data, array $allowed): array
    {
        $values = [];

        foreach ($data as $column => $value) {
            $column = (string) $column;

            if (!in_array($column, $allowed, true)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $values[$column] = $value;
        }

        return $values;
    }

    /**
     * @param list<string> $columns
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildSearch(array $columns, string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return ['', []];
        }

        $clauses = [];
        $params  = [];

        foreach ($columns as $index => $column) {
            $key          = 'search'.$index;
            $clauses[]    = sprintf('`%s` LIKE :%s', $column, $key);
            $params[$key] = '%'.$search.'%';
        }

        if ($clauses === []) {
            return ['', []];
        }

        return [' WHERE '.implode(' OR ', $clauses), $params];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function bindValues(\PDOStatement $statement, array $values): void
    {
        foreach ($values as $column => $value) {
            $this->bindColumn($statement, (string) $column, $value);
        }
    }

    private function bindColumn(\PDOStatement $statement, string $column, mixed $value): void
    {
        if ($value === null) {
            $statement->bindValue(':'.$column, null, \PDO::PARAM_NULL);

            return;
        }

        if (is_bool($value)) {
            $statement->bindValue(':'.$column, $value ? 1 : 0, \PDO::PARAM_BOOL);

            return;
        }

        if (is_int($value)) {
            $statement->bindValue(':'.$column, $value, \PDO::PARAM_INT);

            return;
        }

        if (is_float($value)) {
            $statement->bindValue(':'.$column, $value, \PDO::PARAM_STR);

            return;
        }

        $statement->bindValue(':'.$column, (string) $value, \PDO::PARAM_STR);
    }

    private function assertTable(string $table): void
    {
        if (!Identifier::isValid($table)) {
            throw new ValidationException(['El nombre de la tabla no es valido.']);
        }
    }
}
