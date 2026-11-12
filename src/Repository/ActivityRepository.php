<?php

declare(strict_types=1);

namespace DataForge\Repository;

/**
 * Lectura de la bitacora de auditoria.
 */
final class ActivityRepository
{
    public function __construct(private readonly \PDO $connection) {}

    public function record(
        int $userId,
        string $action,
        string $targetType,
        ?string $targetName = null,
        ?string $databaseContext = null,
    ): void {
        $statement = $this->connection->prepare(
            'INSERT INTO `activity_log` (`user_id`, `action`, `target_type`, `target_name`, `db_context`)'
            .' VALUES (:user, :action, :type, :name, :context)',
        );
        $statement->execute([
            'user'    => $userId,
            'action'  => $action,
            'type'    => $targetType,
            'name'    => $targetName,
            'context' => $databaseContext,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentFor(int $userId, int $limit = 10): array
    {
        $statement = $this->connection->prepare(
            'SELECT `id`, `action`, `target_type`, `target_name`, `db_context`, `created_at`'
            .' FROM `activity_log` WHERE `user_id` = :user ORDER BY `created_at` DESC, `id` DESC LIMIT :limit',
        );
        $statement->bindValue('user', $userId, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function paginateFor(int $userId, int $limit, int $offset): array
    {
        $statement = $this->connection->prepare(
            'SELECT `id`, `action`, `target_type`, `target_name`, `db_context`, `created_at`'
            .' FROM `activity_log` WHERE `user_id` = :user ORDER BY `created_at` DESC, `id` DESC LIMIT :limit OFFSET :offset',
        );
        $statement->bindValue('user', $userId, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }

    public function countFor(int $userId): int
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM `activity_log` WHERE `user_id` = :user');
        $statement->execute(['user' => $userId]);

        return (int) $statement->fetchColumn();
    }
}
