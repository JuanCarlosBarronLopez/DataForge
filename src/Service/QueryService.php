<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Database\ConnectionFactory;
use DataForge\Support\Logger;

/**
 * Consola de consultas SQL en modo de solo lectura.
 *
 * La consola no es un interprete de SQL: acepta un unico enunciado de
 * lectura, le aplica un limite de filas y un tiempo de ejecucion maximo, y
 * nunca ejecuta sentencias de escritura.
 */
final class QueryService
{
    public function __construct(
        private readonly ConnectionFactory $connections,
        private readonly Logger $logger,
        private readonly SqlGuard $guard,
        private readonly int $maxRows = 500,
        private readonly int $timeoutSeconds = 10,
    ) {}

    /**
     * @return array{columns: list<string>, rows: list<array<string, mixed>>, durationMs: int, truncated: bool, statement: string}
     *
     * @throws \DataForge\Exception\ValidationException
     */
    public function execute(string $database, string $sql): array
    {
        $statement = $this->guard->inspect($sql, $this->maxRows);

        $connection = $this->connections->forDatabase($database);
        $this->applyTimeout($connection);

        $started = microtime(true);

        try {
            $result = $connection->query($statement);
        } catch (\PDOException $exception) {
            $this->logger->warning('Error al ejecutar la consulta', [
                'database' => $database,
                'reason'   => $exception->getMessage(),
            ]);

            throw new \DataForge\Exception\ValidationException([
                'El servidor MySQL rechazo la consulta: '.$exception->getMessage(),
            ]);
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        if ($result === false) {
            return ['columns' => [], 'rows' => [], 'durationMs' => $durationMs, 'truncated' => false, 'statement' => $statement];
        }

        $rows = $result->fetchAll(\PDO::FETCH_ASSOC);

        /** @var list<array<string, mixed>> $rows */
        $truncated = count($rows) >= $this->maxRows;

        return [
            'columns'    => $this->columnNames($result),
            'rows'       => array_slice($rows, 0, $this->maxRows),
            'durationMs' => $durationMs,
            'truncated'  => $truncated,
            'statement'  => $statement,
        ];
    }

    /**
     * @return list<string>
     */
    private function columnNames(\PDOStatement $result): array
    {
        $names = [];

        for ($index = 0, $count = $result->columnCount(); $index < $count; ++$index) {
            $meta = $result->getColumnMeta($index);
            if (is_array($meta) && isset($meta['name'])) {
                $names[] = (string) $meta['name'];
            }
        }

        return $names;
    }

    private function applyTimeout(\PDO $connection): void
    {
        try {
            $connection->exec(sprintf('SET SESSION MAX_EXECUTION_TIME = %d', $this->timeoutSeconds * 1000));
        } catch (\PDOException $exception) {
            $this->logger->debug('El motor no admite MAX_EXECUTION_TIME', [
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
