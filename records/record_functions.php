<?php
/**
 * ============================================
 * Record Operations Module
 * ============================================
 *
 * Provides functions for CRUD operations on table records:
 * - Get records (all or by ID)
 * - Get table columns
 * - Add, update, and delete records
 *
 * @package  DataForge
 * @module   Records
 */

require_once __DIR__ . '/../database/db_functions.php';

/**
 * Retrieve records from a table with pagination.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @param  int    $limit     Number of records to fetch.
 * @param  int    $offset    Records to skip.
 * @return array  Array of associative arrays.
 */
function getRecords(string $dbName, string $tableName, int $limit = 50, int $offset = 0): array
{
    $records = [];
    if (empty($dbName) || empty($tableName)) {
        return $records;
    }

    try {
        $conn = getDbConnection($dbName);
        $sql = "SELECT * FROM `" . $conn->real_escape_string($tableName) . "` LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error fetching records from '{$dbName}'.'{$tableName}': " . $e->getMessage());
    }

    return $records;
}

/**
 * Retrieve a single record by its ID.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @param  int    $recordId  Record ID.
 * @return array|null  Record data or null if not found.
 */
function getRecordById(string $dbName, string $tableName, int $recordId): ?array
{
    if (empty($dbName) || empty($tableName) || $recordId <= 0) {
        return null;
    }

    try {
        $conn = getDbConnection($dbName);
        $sql = "SELECT * FROM `" . $conn->real_escape_string($tableName) . "` WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $record;
    } catch (Exception $e) {
        error_log("Error fetching record #{$recordId}: " . $e->getMessage());
        return null;
    }
}

/**
 * Get column names of a table.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @return array<string>     Column names.
 */
function getTableColumns(string $dbName, string $tableName): array
{
    $columns = [];
    if (empty($dbName) || empty($tableName)) {
        return $columns;
    }

    try {
        $conn = getDbConnection($dbName);
        $sql = "DESCRIBE `" . $conn->real_escape_string($tableName) . "`";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
        }
        $conn->close();
    } catch (Exception $e) {
        error_log("Error getting columns for '{$dbName}'.'{$tableName}': " . $e->getMessage());
    }

    return $columns;
}

/**
 * Insert a new record into a table.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @param  array  $data      Associative array of column => value.
 * @return array{success: bool, message: string}
 */
function addRecord(string $dbName, string $tableName, array $data): array
{
    if (empty($dbName) || empty($tableName) || empty($data)) {
        return ['success' => false, 'message' => 'Faltan datos para añadir el registro.'];
    }

    try {
        $conn = getDbConnection($dbName);
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnNames = '`' . implode('`, `', array_map([$conn, 'real_escape_string'], $columns)) . '`';

        $sql = "INSERT INTO `" . $conn->real_escape_string($tableName) . "` ({$columnNames}) VALUES ({$placeholders})";
        $stmt = $conn->prepare($sql);

        // Auto-detect types: integer -> i, double -> d, everything else -> s
        $types = '';
        foreach ($values as $val) {
            if (is_int($val) || ctype_digit(strval($val))) {
                $types .= 'i';
            } elseif (is_float($val) || is_numeric($val)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $stmt->bind_param($types, ...$values);

        $stmt->execute();
        $stmt->close();
        $conn->close();

        return ['success' => true, 'message' => 'Registro añadido exitosamente.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al añadir registro: ' . $e->getMessage()];
    }
}

/**
 * Update an existing record by ID.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @param  int    $recordId  Record ID.
 * @param  array  $data      Associative array of column => value.
 * @return array{success: bool, message: string}
 */
function updateRecord(string $dbName, string $tableName, int $recordId, array $data): array
{
    if (empty($dbName) || empty($tableName) || $recordId <= 0 || empty($data)) {
        return ['success' => false, 'message' => 'Faltan datos para actualizar el registro.'];
    }

    try {
        $conn = getDbConnection($dbName);
        $setClauses = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "`" . $conn->real_escape_string($column) . "` = ?";
            $values[] = $value;
        }

        $sql = "UPDATE `" . $conn->real_escape_string($tableName) . "` SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);

        $types = str_repeat('s', count($values)) . 'i';
        $values[] = $recordId;
        $stmt->bind_param($types, ...$values);

        $stmt->execute();
        $stmt->close();
        $conn->close();

        return ['success' => true, 'message' => 'Registro actualizado exitosamente.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()];
    }
}

/**
 * Delete a record by ID.
 *
 * @param  string $dbName    Database name.
 * @param  string $tableName Table name.
 * @param  int    $recordId  Record ID.
 * @return array{success: bool, message: string}
 */
function deleteRecord(string $dbName, string $tableName, int $recordId): array
{
    if (empty($dbName) || empty($tableName) || $recordId <= 0) {
        return ['success' => false, 'message' => 'Faltan datos para eliminar el registro.'];
    }

    try {
        $conn = getDbConnection($dbName);
        $sql = "DELETE FROM `" . $conn->real_escape_string($tableName) . "` WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $recordId);

        $stmt->execute();
        $stmt->close();
        $conn->close();

        return ['success' => true, 'message' => 'Registro eliminado exitosamente.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()];
    }
}