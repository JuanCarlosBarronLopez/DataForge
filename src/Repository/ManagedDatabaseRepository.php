<?php

declare(strict_types=1);

namespace DataForge\Repository;

/**
 * Registro de las bases de datos que cada usuario administra.
 *
 * Este repositorio es la fuente de verdad del aislamiento entre cuentas:
 * una base solo es accesible si aparece en la lista del usuario.
 */
final class ManagedDatabaseRepository
{
    public function __construct(private readonly \PDO $connection) {}

    /**
     * @return list<string>
     */
    public function namesFor(int $ownerId): array
    {
        $statement = $this->connection->prepare(
            'SELECT `name` FROM `managed_databases` WHERE `owner_id` = :owner ORDER BY `name` ASC',
        );
        $statement->execute(['owner' => $ownerId]);

        /** @var list<string> $names */
        $names = $statement->fetchAll(\PDO::FETCH_COLUMN);

        return $names;
    }

    public function owns(int $ownerId, string $name): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM `managed_databases` WHERE `owner_id` = :owner AND `name` = :name LIMIT 1',
        );
        $statement->execute(['owner' => $ownerId, 'name' => $name]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allFor(int $ownerId): array
    {
        $statement = $this->connection->prepare(
            'SELECT `name`, `created_at` FROM `managed_databases` WHERE `owner_id` = :owner ORDER BY `name` ASC',
        );
        $statement->execute(['owner' => $ownerId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }

    public function register(int $ownerId, string $name): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `managed_databases` (`owner_id`, `name`) VALUES (:owner, :name)',
        );
        $statement->execute(['owner' => $ownerId, 'name' => $name]);
    }

    public function unregister(int $ownerId, string $name): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM `managed_databases` WHERE `owner_id` = :owner AND `name` = :name',
        );
        $statement->execute(['owner' => $ownerId, 'name' => $name]);
    }

    public function rename(int $ownerId, string $from, string $to): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `managed_databases` SET `name` = :to WHERE `owner_id` = :owner AND `name` = :from',
        );
        $statement->execute(['to' => $to, 'owner' => $ownerId, 'from' => $from]);
    }
}
