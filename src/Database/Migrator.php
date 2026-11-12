<?php

declare(strict_types=1);

namespace DataForge\Database;

use DataForge\Support\Identifier;

/**
 * Ejecutor de migraciones versionadas.
 *
 * Cada archivo de database/migrations devuelve un array con las sentencias
 * `up` y, opcionalmente, `down`. Las migraciones aplicadas se registran en la
 * tabla `schema_migrations` de la base de datos de sistema.
 */
final class Migrator
{
    public function __construct(
        private readonly ConnectionFactory $connections,
        private readonly string $migrationsPath,
        private readonly \PDO $systemConnection,
    ) {}

    /**
     * Aplica las migraciones pendientes.
     *
     * @return list<string> Nombres de las migraciones aplicadas
     */
    public function migrate(): array
    {
        $this->ensureRegistry();
        $applied = $this->appliedMigrations();

        $executed = [];

        foreach ($this->availableMigrations() as $name => $statements) {
            if (in_array($name, $applied, true)) {
                continue;
            }

            foreach ($statements as $statement) {
                $this->systemConnection->exec($statement);
            }

            $insert = $this->systemConnection->prepare(
                'INSERT INTO `schema_migrations` (`name`, `applied_at`) VALUES (?, NOW())',
            );
            $insert->execute([$name]);

            $executed[] = $name;
        }

        return $executed;
    }

    /**
     * @return list<string>
     */
    public function pending(): array
    {
        if (!$this->registryExists()) {
            return array_keys($this->availableMigrations());
        }

        $applied = $this->appliedMigrations();

        return array_values(array_filter(
            array_keys($this->availableMigrations()),
            static fn(string $name): bool => !in_array($name, $applied, true),
        ));
    }

    /**
     * @return array<string, list<string>>
     */
    private function availableMigrations(): array
    {
        $migrations = [];

        foreach (glob(rtrim($this->migrationsPath, '/').'/*.php') ?: [] as $file) {
            $name = basename($file, '.php');

            /** @var array{up?: list<string>, down?: list<string>} $definition */
            $definition = require $file;

            $migrations[$name] = $definition['up'] ?? [];
        }

        ksort($migrations);

        return $migrations;
    }

    private function ensureRegistry(): void
    {
        $this->systemConnection->exec(
            'CREATE TABLE IF NOT EXISTS `schema_migrations` ('
            .'`id` INT AUTO_INCREMENT PRIMARY KEY, '
            .'`name` VARCHAR(190) NOT NULL UNIQUE, '
            .'`applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            .') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        );
    }

    private function registryExists(): bool
    {
        try {
            $statement = $this->systemConnection->query(
                "SHOW TABLES LIKE 'schema_migrations'",
            );

            return $statement !== false && $statement->fetchColumn() !== false;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function appliedMigrations(): array
    {
        if (!$this->registryExists()) {
            return [];
        }

        $statement = $this->systemConnection->query('SELECT `name` FROM `schema_migrations`');
        if ($statement === false) {
            return [];
        }

        $names = [];
        foreach ($statement->fetchAll(\PDO::FETCH_COLUMN) as $name) {
            $names[] = (string) $name;
        }

        return $names;
    }

    /**
     * Crea la base de datos de sistema si el motor lo permite.
     *
     * Los servicios MySQL gestionados (TiDB Cloud Serverless, PlanetScale)
     * bloquean CREATE DATABASE, por lo que la base debe existir de antemano.
     */
    public function ensureSystemDatabase(): bool
    {
        if (!Identifier::isValid($this->connections->systemDatabase())) {
            return false;
        }

        try {
            $this->connections->system();

            return true;
        } catch (\PDOException) {
            try {
                $server = $this->connections->server();
                $server->exec(sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    $this->connections->systemDatabase(),
                ));

                return true;
            } catch (\PDOException) {
                return false;
            }
        }
    }
}
