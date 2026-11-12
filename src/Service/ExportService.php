<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Database\ConnectionFactory;
use DataForge\Support\Identifier;

/**
 * Exportacion de datos en formato CSV y volcado SQL.
 */
final class ExportService
{
    public function __construct(
        private readonly ConnectionFactory $connections,
        private readonly TableService $tables,
    ) {}

    /**
     * Genera un CSV con el contenido de una tabla.
     */
    public function tableToCsv(string $database, string $table): string
    {
        if (!Identifier::isValid($table)) {
            return '';
        }

        $connection = $this->connections->forDatabase($database);
        $statement  = $connection->query(sprintf('SELECT * FROM `%s`', $table));

        if ($statement === false) {
            return '';
        }

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, $this->columnNames($statement), ',', '"', '');

        while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
            fputcsv($handle, array_map(
                static fn(mixed $value): string => $value === null ? '' : (string) $value,
                $row,
            ), ',', '"', '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Genera un volcado SQL con la estructura y los datos de una base.
     */
    public function databaseToSql(string $database): string
    {
        if (!Identifier::isValid($database)) {
            return '';
        }

        $connection = $this->connections->forDatabase($database);
        $dump       = sprintf("-- DataForge: volcado de `%s`\n", $database);
        $dump .= sprintf("-- Generado el %s\n\n", date('c'));
        $dump .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($this->tables->list($database) as $table) {
            $create = $connection->query(sprintf('SHOW CREATE TABLE `%s`', $table));
            if ($create === false) {
                continue;
            }

            $row = $create->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                continue;
            }

            $dump .= sprintf("DROP TABLE IF EXISTS `%s`;\n", $table);
            $dump .= sprintf("%s;\n\n", (string) ($row['Create Table'] ?? ''));

            $data = $connection->query(sprintf('SELECT * FROM `%s`', $table));
            if ($data === false) {
                continue;
            }

            while (($record = $data->fetch(\PDO::FETCH_ASSOC)) !== false) {
                $columns = array_keys($record);
                $values  = array_map(
                    static fn(mixed $value): string => $value === null ? 'NULL' : $connection->quote((string) $value),
                    $record,
                );

                $dump .= sprintf(
                    "INSERT INTO `%s` (`%s`) VALUES (%s);\n",
                    $table,
                    implode('`, `', $columns),
                    implode(', ', $values),
                );
            }

            $dump .= "\n";
        }

        $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $dump;
    }

    /**
     * @return list<string>
     */
    private function columnNames(\PDOStatement $statement): array
    {
        $names = [];

        for ($index = 0, $count = $statement->columnCount(); $index < $count; ++$index) {
            $meta = $statement->getColumnMeta($index);
            if (is_array($meta) && isset($meta['name'])) {
                $names[] = (string) $meta['name'];
            }
        }

        return $names;
    }
}
