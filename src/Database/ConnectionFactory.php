<?php

declare(strict_types=1);

namespace DataForge\Database;

use DataForge\Support\Identifier;

/**
 * Fabrica de conexiones PDO a MySQL.
 *
 * Las conexiones se crean bajo demanda y no se comparten entre bases de datos
 * distintas, porque PDO fija la base seleccionada en el momento de conectar.
 */
final class ConnectionFactory
{
    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 3306,
        private readonly string $username = 'root',
        private readonly string $password = '',
        private readonly string $charset = 'utf8mb4',
        private readonly string $systemDatabase = 'dataforge_system',
        private readonly ?string $sslCa = null,
        private readonly bool $sslVerify = true,
    ) {}

    /**
     * Conexion a la base de datos de sistema (usuarios, auditoria, registro).
     */
    public function system(): \PDO
    {
        return $this->connect($this->systemDatabase);
    }

    /**
     * Conexion al servidor sin seleccionar base de datos.
     */
    public function server(): \PDO
    {
        return $this->connect(null);
    }

    /**
     * Conexion a una base de datos concreta.
     *
     * @throws \InvalidArgumentException si el nombre no es un identificador valido
     */
    public function forDatabase(string $database): \PDO
    {
        if (!Identifier::isValid($database)) {
            throw new \InvalidArgumentException('El nombre de la base de datos no es valido.');
        }

        return $this->connect($database);
    }

    private function connect(?string $database): \PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;', $this->host, $this->port);

        if ($database !== null) {
            $dsn .= 'dbname='.$database.';';
        }

        $dsn .= 'charset='.$this->charset;

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        if ($this->sslCa !== null && $this->sslCa !== '') {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $this->sslCa;

            if (!$this->sslVerify) {
                $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        return new \PDO($dsn, $this->username, $this->password, $options);
    }

    public function systemDatabase(): string
    {
        return $this->systemDatabase;
    }

    public function host(): string
    {
        return $this->host;
    }
}
