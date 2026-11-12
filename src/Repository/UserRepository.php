<?php

declare(strict_types=1);

namespace DataForge\Repository;

use DataForge\Exception\ValidationException;

/**
 * Acceso a la tabla de usuarios de la base de datos de sistema.
 */
final class UserRepository
{
    private const COLUMNS = 'id, username, email, password_hash, theme, color_mode, profile_pic, onboarding_done, created_at, last_login';

    public function __construct(private readonly \PDO $connection) {}

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT '.self::COLUMNS.' FROM `users` WHERE `id` = :id LIMIT 1',
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT '.self::COLUMNS.' FROM `users` WHERE `email` = :email LIMIT 1',
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);

        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT '.self::COLUMNS.' FROM `users` WHERE `username` = :username LIMIT 1',
        );
        $statement->execute(['username' => trim($username)]);

        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO `users` (`username`, `email`, `password_hash`) VALUES (:username, :email, :password)',
            );
            $statement->execute([
                'username' => trim($username),
                'email'    => mb_strtolower(trim($email)),
                'password' => $passwordHash,
            ]);
        } catch (\PDOException $exception) {
            if ($this->isDuplicate($exception)) {
                throw new ValidationException([
                    'El correo electronico o el nombre de usuario ya estan registrados.',
                ]);
            }

            throw $exception;
        }

        return (int) $this->connection->lastInsertId();
    }

    public function updateProfile(int $id, string $username, string $email, string $colorMode, ?string $profilePic): void
    {
        try {
            if ($profilePic !== null) {
                $statement = $this->connection->prepare(
                    'UPDATE `users` SET `username` = :username, `email` = :email, `color_mode` = :mode, `profile_pic` = :pic'
                    .' WHERE `id` = :id',
                );
                $statement->execute([
                    'username' => trim($username),
                    'email'    => mb_strtolower(trim($email)),
                    'mode'     => $colorMode,
                    'pic'      => $profilePic,
                    'id'       => $id,
                ]);

                return;
            }

            $statement = $this->connection->prepare(
                'UPDATE `users` SET `username` = :username, `email` = :email, `color_mode` = :mode WHERE `id` = :id',
            );
            $statement->execute([
                'username' => trim($username),
                'email'    => mb_strtolower(trim($email)),
                'mode'     => $colorMode,
                'id'       => $id,
            ]);
        } catch (\PDOException $exception) {
            if ($this->isDuplicate($exception)) {
                throw new ValidationException([
                    'El correo electronico o el nombre de usuario ya estan en uso.',
                ]);
            }

            throw $exception;
        }
    }

    public function updateTheme(int $id, string $theme): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `users` SET `theme` = :theme WHERE `id` = :id',
        );
        $statement->execute(['theme' => $theme, 'id' => $id]);
    }

    public function updateColorMode(int $id, string $mode): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `users` SET `color_mode` = :mode WHERE `id` = :id',
        );
        $statement->execute(['mode' => $mode, 'id' => $id]);
    }

    public function markOnboardingComplete(int $id): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `users` SET `onboarding_done` = 1 WHERE `id` = :id',
        );
        $statement->execute(['id' => $id]);
    }

    public function touchLastLogin(int $id): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `users` SET `last_login` = NOW() WHERE `id` = :id',
        );
        $statement->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $statement = $this->connection->prepare(
            'UPDATE `users` SET `password_hash` = :hash WHERE `id` = :id',
        );
        $statement->execute(['hash' => $hash, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->connection->prepare('DELETE FROM `users` WHERE `id` = :id');
        $statement->execute(['id' => $id]);
    }

    public function count(): int
    {
        $statement = $this->connection->query('SELECT COUNT(*) FROM `users`');
        if ($statement === false) {
            return 0;
        }

        return (int) $statement->fetchColumn();
    }

    private function isDuplicate(\PDOException $exception): bool
    {
        return $exception->getCode() === '23000'
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
