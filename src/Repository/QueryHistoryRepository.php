<?php

declare(strict_types=1);

namespace DataForge\Repository;

/**
 * Historial de consultas ejecutadas desde la consola SQL.
 */
final class QueryHistoryRepository
{
    public function __construct(private readonly \PDO $connection) {}

    public function record(int $userId, string $statement, int $rowCount, int $durationMs): void
    {
        $insert = $this->connection->prepare(
            'INSERT INTO `query_history` (`user_id`, `statement`, `row_count`, `duration_ms`)'
            .' VALUES (:user, :statement, :rows, :duration)',
        );
        $insert->execute([
            'user'      => $userId,
            'statement' => mb_substr($statement, 0, 65_535),
            'rows'      => $rowCount,
            'duration'  => $durationMs,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentFor(int $userId, int $limit = 15): array
    {
        $statement = $this->connection->prepare(
            'SELECT `id`, `statement`, `row_count`, `duration_ms`, `executed_at`'
            .' FROM `query_history` WHERE `user_id` = :user ORDER BY `executed_at` DESC, `id` DESC LIMIT :limit',
        );
        $statement->bindValue('user', $userId, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }
}
