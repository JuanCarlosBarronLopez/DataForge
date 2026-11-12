<?php

declare(strict_types=1);

namespace DataForge\Repository;

/**
 * Registro de intentos de inicio de sesion.
 *
 * Permite bloquear una cuenta concreta con independencia de la direccion IP
 * desde la que lleguen los intentos.
 */
final class LoginAttemptRepository
{
    public function __construct(private readonly \PDO $connection) {}

    public function record(string $identifier, string $ip, bool $succeeded): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO `login_attempts` (`identifier`, `ip`, `succeeded`) VALUES (:identifier, :ip, :succeeded)',
        );
        $statement->execute([
            'identifier' => mb_substr($identifier, 0, 190),
            'ip'         => $ip,
            'succeeded'  => $succeeded ? 1 : 0,
        ]);
    }

    public function failedAttemptsSince(string $identifier, int $since): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM `login_attempts`'
            .' WHERE `identifier` = :identifier AND `succeeded` = 0 AND `attempted_at` >= :since',
        );
        $statement->execute([
            'identifier' => mb_substr($identifier, 0, 190),
            'since'      => date('Y-m-d H:i:s', $since),
        ]);

        return (int) $statement->fetchColumn();
    }

    public function purgeOlderThan(int $timestamp): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM `login_attempts` WHERE `attempted_at` < :since',
        );
        $statement->execute(['since' => date('Y-m-d H:i:s', $timestamp)]);
    }
}
