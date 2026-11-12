<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Repository\ActivityRepository;

/**
 * Alimenta el canal de eventos en vivo con la actividad del usuario.
 */
final class LiveFeed
{
    public function __construct(
        private readonly ActivityRepository $activity,
        private readonly DatabaseService $databases,
    ) {}

    /**
     * Estado inicial: metricas y las ultimas entradas de la bitacora.
     *
     * @return array{metrics: array{databases: int, tables: int, rows: int}, entries: list<array<string, mixed>>, lastId: int}
     */
    public function snapshot(int $userId): array
    {
        $entries = $this->activity->recentFor($userId, 8);

        return [
            'metrics' => $this->metrics($userId),
            'entries' => $entries,
            'lastId'  => $this->lastId($entries),
        ];
    }

    /**
     * Entradas nuevas posteriores a un identificador.
     *
     * @return array{entries: list<array<string, mixed>>, lastId: int, metrics: array{databases: int, tables: int, rows: int}}
     */
    public function since(int $userId, int $lastId): array
    {
        $entries = array_values(array_filter(
            $this->activity->recentFor($userId, 25),
            static fn(array $entry): bool => (int) $entry['id'] > $lastId,
        ));

        return [
            'entries' => $entries,
            'lastId'  => $this->lastId($entries) ?: $lastId,
            'metrics' => $this->metrics($userId),
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     */
    private function lastId(array $entries): int
    {
        $ids = array_map(static fn(array $entry): int => (int) $entry['id'], $entries);

        return $ids === [] ? 0 : max($ids);
    }

    /**
     * @return array{databases: int, tables: int, rows: int}
     */
    private function metrics(int $userId): array
    {
        $databases = $this->databases->listFor($userId);
        $tables    = 0;
        $rows      = 0;

        foreach ($databases as $database) {
            try {
                $tables += $this->databases->tableCount($database);
                $rows   += $this->databases->totalRows($database);
            } catch (\Throwable) {
                // Una base ilegible no debe romper el canal.
                continue;
            }
        }

        return ['databases' => count($databases), 'tables' => $tables, 'rows' => $rows];
    }
}
