<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Database\ConnectionFactory;
use DataForge\Exception\NotFoundException;
use DataForge\Exception\ValidationException;
use DataForge\Repository\ActivityRepository;
use DataForge\Repository\ManagedDatabaseRepository;
use DataForge\Support\Identifier;
use DataForge\Support\Logger;

/**
 * Gestion de las bases de datos administradas por cada usuario.
 *
 * El aislamiento entre cuentas se apoya en el repositorio de bases
 * administradas: una base solo es accesible si su propietario la registro.
 */
final class DatabaseService
{
    public function __construct(
        private readonly ConnectionFactory $connections,
        private readonly ManagedDatabaseRepository $managed,
        private readonly ActivityRepository $activity,
        private readonly Logger $logger,
    ) {}

    /**
     * @return list<string>
     */
    public function listFor(int $ownerId): array
    {
        return $this->managed->namesFor($ownerId);
    }

    /**
     * Verifica que la base pertenece al usuario y devuelve su nombre validado.
     *
     * @throws NotFoundException
     */
    public function assertOwnership(int $ownerId, string $database): string
    {
        if (!Identifier::isValid($database)) {
            throw new NotFoundException('La base de datos solicitada no existe.');
        }

        if (!$this->managed->owns($ownerId, $database)) {
            throw new NotFoundException('La base de datos solicitada no existe.');
        }

        return $database;
    }

    /**
     * Crea una base de datos y la registra a nombre del usuario.
     *
     * @throws ValidationException
     */
    public function create(int $ownerId, string $name): void
    {
        $name = trim($name);

        if (!Identifier::isValid($name)) {
            throw new ValidationException([
                'El nombre de la base de datos solo admite letras, digitos y guion bajo (hasta 64 caracteres).',
            ]);
        }

        if ($this->managed->owns($ownerId, $name)) {
            throw new ValidationException(['Ya tienes una base de datos con ese nombre.']);
        }

        try {
            $server = $this->connections->server();
            $server->exec(sprintf(
                'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $name,
            ));
        } catch (\PDOException $exception) {
            $this->logger->warning('No se pudo crear la base de datos', [
                'database' => $name,
                'reason'   => $exception->getMessage(),
            ]);

            if (str_contains($exception->getMessage(), '1007') || str_contains($exception->getMessage(), 'already exists')) {
                throw new ValidationException([
                    'Ese nombre ya existe en el servidor MySQL. Los nombres de base de datos son globales.',
                ]);
            }

            throw new ValidationException([
                'El servidor MySQL no permitio crear la base de datos. Revisa los permisos del usuario.',
            ]);
        }

        $this->managed->register($ownerId, $name);
        $this->activity->record($ownerId, 'create', 'base de datos', $name);
    }

    /**
     * Elimina la base de datos y su registro de propiedad.
     *
     * @throws ValidationException
     */
    public function drop(int $ownerId, string $name): void
    {
        $this->assertOwnership($ownerId, $name);

        try {
            $server = $this->connections->server();
            $server->exec(sprintf('DROP DATABASE `%s`', $name));
        } catch (\PDOException $exception) {
            $this->logger->warning('No se pudo eliminar la base de datos', [
                'database' => $name,
                'reason'   => $exception->getMessage(),
            ]);

            throw new ValidationException(['El servidor MySQL no permitio eliminar la base de datos.']);
        }

        $this->managed->unregister($ownerId, $name);
        $this->activity->record($ownerId, 'delete', 'base de datos', $name);
    }

    /**
     * @return list<string>
     */
    public function tables(string $database): array
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

    /**
     * Numero de tablas de una base de datos.
     */
    public function tableCount(string $database): int
    {
        return count($this->tables($database));
    }

    /**
     * Numero de filas de una base de datos. Recorre todas las tablas, por lo que
     * el coste crece con el tamano de la base.
     */
    public function totalRows(string $database): int
    {
        $connection = $this->connections->forDatabase($database);
        $total      = 0;

        foreach ($this->tables($database) as $table) {
            try {
                $statement = $connection->query(sprintf('SELECT COUNT(*) FROM `%s`', $table));
                if ($statement !== false) {
                    $total += (int) $statement->fetchColumn();
                }
            } catch (\PDOException $exception) {
                $this->logger->debug('No se pudo contar una tabla', [
                    'database' => $database,
                    'table'    => $table,
                    'reason'   => $exception->getMessage(),
                ]);
            }
        }

        return $total;
    }
}
