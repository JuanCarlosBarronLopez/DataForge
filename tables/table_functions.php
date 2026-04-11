<?php
/**
 * ============================================
 * Table Operations Module
 * ============================================
 *
 * Provides functions for MySQL table management:
 * - List, create, alter, and drop tables
 * - Get column definitions
 * - Display tables in HTML format
 *
 * @package  DataForge
 * @module   Tables
 */

require_once __DIR__ . '/../database/db_functions.php';

/**
 * Retrieve all tables for a specific database.
 *
 * @param  string $dbName Name of the database.
 * @return array<string>  Array of table names.
 */
function getTables(string $dbName): array
{
    $tables = [];
    if (empty($dbName)) {
        return $tables;
    }

    try {
        $conn = getDbConnection($dbName);
        $result = $conn->query("SHOW TABLES");

        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        $conn->close();
    } catch (Exception $e) {
        error_log("Error listing tables for '{$dbName}': " . $e->getMessage());
    }

    return $tables;
}

/**
 * Render HTML table rows for the tables listing page.
 *
 * @param  string $dbName Database name to list tables for.
 * @return void
 */
function displayTablesForDb(string $dbName): void
{
    if (empty($dbName)) {
        echo '<tr><td colspan="3"><div class="empty-state">Error: No se especificó la base de datos.</div></td></tr>';
        return;
    }

    $tables = getTables($dbName);

    if (empty($tables)) {
        echo '<tr><td colspan="3"><div class="empty-state">No hay tablas en "' . htmlspecialchars($dbName) . '". ¡Crea una!</div></td></tr>';
        return;
    }

    foreach ($tables as $tableName) {
        $safeName = htmlspecialchars($tableName);
        $safeDb = htmlspecialchars($dbName);
        $encodedT = urlencode($tableName);
        $encodedDb = urlencode($dbName);

        echo "<tr>";
        echo "<td><span class='table-name'>{$safeName}</span></td>";
        echo "<td><span class='badge badge-accent'>{$safeDb}</span></td>";
        echo "<td class='actions-cell'>";
        echo "<a class='btn btn-sm btn-accent' href='../records/records.php?dbName={$encodedDb}&tableName={$encodedT}'>Ver Registros</a>";
        echo "<a class='btn btn-sm btn-warning' href='set_table_form.php?dbName={$encodedDb}&tableName={$encodedT}'>Editar</a>";
        echo "<form method='POST' action='eliminar_tabla.php' class='inline-form' onsubmit=\"return confirm('¿Eliminar la tabla \\'{$safeName}\\' de \\'{$safeDb}\\'? Esta acción es irreversible.')\">";
        csrfField();
        echo "<input type='hidden' name='dbName' value='{$safeDb}'>";
        echo "<input type='hidden' name='tableName' value='{$safeName}'>";
        echo "<button type='submit' class='btn btn-sm btn-danger'>Eliminar</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
}

/**
 * Create a new table with dynamically defined columns.
 *
 * @param  string $dbName            Database name.
 * @param  string $tableName         Table name.
 * @param  array  $columnDefinitions Array of column definitions.
 * @return array{success: bool, message: string}
 */
function createTable(string $dbName, string $tableName, array $columnDefinitions): array
{
    if (empty($dbName) || empty($tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return ['success' => false, 'message' => 'Nombre de base de datos o tabla inválido.'];
    }

    if (empty($columnDefinitions)) {
        return ['success' => false, 'message' => 'No se han definido columnas para la tabla.'];
    }

    try {
        $conn = getDbConnection($dbName);
        $escapedTableName = $conn->real_escape_string($tableName);
        $columnsSql = ["id INT AUTO_INCREMENT PRIMARY KEY"];

        foreach ($columnDefinitions as $col) {
            $colName = $conn->real_escape_string($col['name']);
            $colType = strtoupper($col['type']);
            $columnClause = "`{$colName}`";

            switch ($colType) {
                case 'INT':
                    $columnClause .= " INT";
                    break;
                case 'FLOAT':
                    $columnClause .= " FLOAT";
                    break;
                case 'DECIMAL':
                    $columnClause .= " DECIMAL(10, 2)";
                    break;
                case 'VARCHAR':
                    $length = (int) ($col['length'] ?? 255);
                    if ($length <= 0 || $length > 65535) {
                        return ['success' => false, 'message' => "Longitud inválida para VARCHAR en columna '{$colName}'."];
                    }
                    $columnClause .= " VARCHAR({$length})";
                    break;
                case 'TEXT':
                    $columnClause .= " TEXT";
                    break;
                case 'DATE':
                    $columnClause .= " DATE";
                    break;
                case 'DATETIME':
                    $columnClause .= " DATETIME";
                    break;
                case 'BOOLEAN':
                    $columnClause .= " BOOLEAN";
                    break;
                default:
                    return ['success' => false, 'message' => "Tipo de dato no soportado: {$colType}"];
            }

            $columnClause .= " NOT NULL";
            $columnsSql[] = $columnClause;
        }

        $sql = "CREATE TABLE `{$escapedTableName}` (" . implode(", ", $columnsSql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        $conn->close();

        return ['success' => true, 'message' => "Tabla \"{$tableName}\" creada exitosamente en \"{$dbName}\"."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al crear la tabla: ' . $e->getMessage()];
    }
}

/**
 * Drop a table from a database.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @return array{success: bool, message: string}
 */
function dropTable(string $dbName, string $tableName): array
{
    if (empty($dbName) || empty($tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return ['success' => false, 'message' => 'Nombre de base de datos o tabla inválido.'];
    }

    try {
        $conn = getDbConnection($dbName);
        $sql = "DROP TABLE `" . $conn->real_escape_string($tableName) . "`";
        $conn->query($sql);
        $conn->close();

        return ['success' => true, 'message' => "Tabla \"{$tableName}\" eliminada de \"{$dbName}\"."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al eliminar la tabla: ' . $e->getMessage()];
    }
}

/**
 * Get the column definitions of a table.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @return array  Array of column definition arrays.
 */
function getTableColumnsDefinition(string $dbName, string $tableName): array
{
    $columnDefinitions = [];
    if (empty($dbName) || empty($tableName)) {
        return $columnDefinitions;
    }

    try {
        $conn = getDbConnection($dbName);
        $sql = "DESCRIBE `" . $conn->real_escape_string($tableName) . "`";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columnDefinitions[] = $row;
            }
        }
        $conn->close();
    } catch (Exception $e) {
        error_log("Error describing '{$dbName}'.'{$tableName}': " . $e->getMessage());
    }

    return $columnDefinitions;
}

/**
 * Alter table structure (add/modify columns).
 *
 * @param  string $dbName                  Database name.
 * @param  string $tableName               Table name.
 * @param  array  $currentColumnsDefinition Current column definitions.
 * @param  array  $newColumnDefinitions     New/modified column definitions.
 * @return array{success: bool, message: string}
 */
function alterTable(string $dbName, string $tableName, array $currentColumnsDefinition, array $newColumnDefinitions): array
{
    if (empty($dbName) || empty($tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return ['success' => false, 'message' => 'Nombre de base de datos o tabla inválido.'];
    }

    try {
        $conn = getDbConnection($dbName);
        $escapedTableName = $conn->real_escape_string($tableName);
        $alterStatements = [];

        // Map current columns for comparison
        $currentColumnsMap = [];
        foreach ($currentColumnsDefinition as $col) {
            $currentColumnsMap[$col['Field']] = $col;
        }

        foreach ($newColumnDefinitions as $newCol) {
            $colName = $conn->real_escape_string(trim($newCol['name'] ?? ''));
            $colType = strtoupper(trim($newCol['type'] ?? ''));
            $colLength = (int) ($newCol['length'] ?? 0);
            $isNew = filter_var($newCol['is_new'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (empty($colName) || empty($colType) || !preg_match('/^[a-zA-Z0-9_]+$/', $colName)) {
                $conn->close();
                return ['success' => false, 'message' => "Definición de columna inválida para '{$colName}'."];
            }

            $columnDefinitionSql = "`{$colName}` ";
            switch ($colType) {
                case 'INT':
                    $columnDefinitionSql .= "INT";
                    break;
                case 'FLOAT':
                    $columnDefinitionSql .= "FLOAT";
                    break;
                case 'DECIMAL':
                    $columnDefinitionSql .= "DECIMAL(10, 2)";
                    break;
                case 'VARCHAR':
                    if ($colLength <= 0 || $colLength > 65535) {
                        $conn->close();
                        return ['success' => false, 'message' => "Longitud inválida para VARCHAR en '{$colName}'."];
                    }
                    $columnDefinitionSql .= "VARCHAR({$colLength})";
                    break;
                case 'TEXT':
                    $columnDefinitionSql .= "TEXT";
                    break;
                case 'DATE':
                    $columnDefinitionSql .= "DATE";
                    break;
                case 'DATETIME':
                    $columnDefinitionSql .= "DATETIME";
                    break;
                case 'BOOLEAN':
                    $columnDefinitionSql .= "BOOLEAN";
                    break;
                default:
                    $conn->close();
                    return ['success' => false, 'message' => "Tipo no soportado: {$colType}"];
            }
            $columnDefinitionSql .= " NOT NULL";

            if ($isNew) {
                $alterStatements[] = "ADD COLUMN {$columnDefinitionSql}";
            } elseif (isset($currentColumnsMap[$colName])) {
                $currentCol = $currentColumnsMap[$colName];
                $currentType = strtoupper(explode('(', $currentCol['Type'])[0]);
                $typeChanged = ($currentType !== $colType);

                $currentLength = 0;
                if (preg_match('/\((\d+)\)/', $currentCol['Type'], $m)) {
                    $currentLength = (int) $m[1];
                }
                $lengthChanged = ($colType === 'VARCHAR' && $currentLength !== $colLength);

                if ($typeChanged || $lengthChanged) {
                    $alterStatements[] = "MODIFY COLUMN {$columnDefinitionSql}";
                }
            }
        }

        if (empty($alterStatements)) {
            $conn->close();
            return ['success' => true, 'message' => 'No se detectaron cambios en la estructura.'];
        }

        $sql = "ALTER TABLE `{$escapedTableName}` " . implode(", ", $alterStatements);
        $conn->query($sql);
        $conn->close();

        return ['success' => true, 'message' => "Tabla \"{$tableName}\" modificada exitosamente."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al modificar la tabla: ' . $e->getMessage()];
    }
}

/**
 * Drop a column from a table.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @param  string $colName   Column name to drop.
 * @return array{success: bool, message: string}
 */
function dropColumn(string $dbName, string $tableName, string $colName): array
{
    if (empty($dbName) || empty($tableName) || empty($colName)) {
        return ['success' => false, 'message' => 'Faltan datos para eliminar la columna.'];
    }

    try {
        $conn = getDbConnection($dbName);
        $escapedTableName = $conn->real_escape_string($tableName);
        $escapedColName   = $conn->real_escape_string($colName);

        $sql = "ALTER TABLE `{$escapedTableName}` DROP COLUMN `{$escapedColName}`";
        $conn->query($sql);
        $conn->close();

        return ['success' => true, 'message' => "Columna \"{$colName}\" eliminada de \"{$tableName}\"."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al eliminar la columna: ' . $e->getMessage()];
    }
}