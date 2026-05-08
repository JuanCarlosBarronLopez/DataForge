<?php
/**
 * ============================================
 * Database Operations Module
 * ============================================
 *
 * Provides functions for MySQL database management:
 * - Connection handling
 * - List, create, and drop databases
 * - Display databases in HTML table format
 *
 * @package  DataForge
 * @module   Database
 */

require_once __DIR__ . '/../config.php';

/** @var array System databases excluded from user-facing lists */
const SYSTEM_DATABASES = [
    'information_schema',
    'mysql',
    'performance_schema',
    'sys',
    'phpmyadmin',
    'test',
];

/**
 * Establish a new MySQLi connection.
 *
 * @param  string|null $dbName Optional database name to select.
 * @return mysqli      The MySQLi connection object.
 * @throws Exception   If the connection fails.
 */
function getDbConnection(?string $dbName = null): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $db = empty($dbName) ? "" : $dbName;
        $conn = mysqli_init();
        
        $flags = 0;
        // Si estamos conectándonos a un host externo, forzamos SSL (necesario para PlanetScale/TiDB)
        if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1' && DB_HOST !== 'db') {
            $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $flags = MYSQLI_CLIENT_SSL;
        }

        $conn->real_connect(DB_HOST, DB_USER, DB_PASS, $db, (int) DB_PORT, NULL, $flags);
        $conn->set_charset(DB_CHARSET);
        return $conn;
    } catch (mysqli_sql_exception $e) {
        throw new Exception("Error MySQL: " . $e->getMessage());
    }
}

/**
 * Retrieve a list of all user-created databases.
 *
 * @return array<string> Array of database names.
 */
function getDatabases(): array
{
    $databases = [];

    try {
        $conn = getDbConnection();
        $result = $conn->query("SHOW DATABASES");

        while ($row = $result->fetch_assoc()) {
            $dbName = $row['Database'];
            if (!in_array($dbName, SYSTEM_DATABASES, true)) {
                $databases[] = $dbName;
            }
        }
        $conn->close();
    } catch (Exception $e) {
        error_log("Error listing databases: " . $e->getMessage());
    }

    return $databases;
}

/**
 * Render HTML table rows for the database listing page.
 *
 * @return void
 */
function displayDatabases(): void
{
    $databases = getDatabases();

    if (empty($databases)) {
        echo '<tr><td colspan="3"><div class="empty-state">No se encontraron bases de datos. ¡Crea una para comenzar!</div></td></tr>';
        return;
    }

    foreach ($databases as $dbName) {
        $safe = htmlspecialchars($dbName);
        $encoded = urlencode($dbName);

        echo "<tr>";
        echo "<td><span class='db-name'>{$safe}</span></td>";
        echo "<td><span class='badge badge-info'>MySQL</span></td>";
        echo "<td class='actions-cell'>";
        echo "<a class='btn btn-sm btn-accent' href='../tables/view_db.php?dbName={$encoded}'>Ver Tablas</a>";
        echo "<a class='btn btn-sm btn-warning' href='../tables/crear_tabla_from_db.php?dbName={$encoded}'>+ Tabla</a>";
        echo "<form method='POST' action='eliminar_db.php' class='inline-form' onsubmit=\"return confirm('¿Eliminar la base de datos \\'{$safe}\\'? Esta acción es irreversible.')\">";
        csrfField();
        echo "<input type='hidden' name='dbName' value='{$safe}'>";
        echo "<button type='submit' class='btn btn-sm btn-danger'>Eliminar</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
}

/**
 * Create a new database.
 *
 * @param  string $dbName Name of the database to create.
 * @return array{success: bool, message: string} Operation result.
 */
function createDatabase(string $dbName): array
{
    if (empty($dbName) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        return [
            'success' => false,
            'message' => 'Nombre de base de datos inválido. Solo se permiten letras, números y guiones bajos.',
        ];
    }

    try {
        $conn = getDbConnection();
        $sql = "CREATE DATABASE `" . $conn->real_escape_string($dbName) . "`";

        $conn->query($sql);
        $conn->close();

        return [
            'success' => true,
            'message' => 'Base de datos "' . htmlspecialchars($dbName) . '" creada exitosamente.',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al crear la base de datos: ' . $e->getMessage(),
        ];
    }
}

/**
 * Drop (delete) a database permanently.
 *
 * @param  string $dbName Name of the database to drop.
 * @return array{success: bool, message: string} Operation result.
 */
function dropDatabase(string $dbName): array
{
    if (empty($dbName) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        return ['success' => false, 'message' => 'Nombre de base de datos inválido.'];
    }

    if (in_array($dbName, SYSTEM_DATABASES, true)) {
        return ['success' => false, 'message' => 'No puedes eliminar bases de datos del sistema.'];
    }

    try {
        $conn = getDbConnection();
        $sql = "DROP DATABASE `" . $conn->real_escape_string($dbName) . "`";

        $conn->query($sql);
        $conn->close();

        return [
            'success' => true,
            'message' => 'Base de datos "' . htmlspecialchars($dbName) . '" eliminada exitosamente.',
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al eliminar la base de datos: ' . $e->getMessage(),
        ];
    }
}