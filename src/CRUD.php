<?php
/**
 * ============================================
 * DataForge — CRUD Class
 * ============================================
 *
 * Object-oriented wrapper for table and record operations.
 * Unifies table management and record CRUD into a single class.
 *
 * @package  DataForge
 * @version  3.2.0
 */

namespace DataForge;

class CRUD
{
    // ─── Table Operations ─────────────────────────────────────────────────

    /**
     * Get all tables in a database.
     *
     * @param string $dbName Database name
     * @return array<string>
     */
    public static function getTables(string $dbName): array
    {
        if (function_exists('getTables')) {
            return getTables($dbName);
        }
        return [];
    }

    /**
     * Create a new table.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @param array  $columns Column definitions
     * @return array{success: bool, message: string}
     */
    public static function createTable(string $dbName, string $tableName, array $columns): array
    {
        if (function_exists('createTable')) {
            return createTable($dbName, $tableName, $columns);
        }
        return ['success' => false, 'message' => 'Table functions not loaded.'];
    }

    /**
     * Drop a table.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @return array{success: bool, message: string}
     */
    public static function dropTable(string $dbName, string $tableName): array
    {
        if (function_exists('dropTable')) {
            return dropTable($dbName, $tableName);
        }
        return ['success' => false, 'message' => 'Table functions not loaded.'];
    }

    /**
     * Alter table structure.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @param array  $currentColumns Current column definitions
     * @param array  $newColumns New column definitions
     * @return array{success: bool, message: string}
     */
    public static function alterTable(string $dbName, string $tableName, array $currentColumns, array $newColumns): array
    {
        if (function_exists('alterTable')) {
            return alterTable($dbName, $tableName, $currentColumns, $newColumns);
        }
        return ['success' => false, 'message' => 'Table functions not loaded.'];
    }

    /**
     * Get column definitions for a table.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @return array
     */
    public static function getColumns(string $dbName, string $tableName): array
    {
        if (function_exists('getTableColumnsDefinition')) {
            return getTableColumnsDefinition($dbName, $tableName);
        }
        return [];
    }

    // ─── Record Operations ────────────────────────────────────────────────

    /**
     * Get records from a table with pagination.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @param int    $limit Records per page
     * @param int    $offset Offset
     * @return array
     */
    public static function getRecords(string $dbName, string $tableName, int $limit = 50, int $offset = 0): array
    {
        if (function_exists('getRecords')) {
            return getRecords($dbName, $tableName, $limit, $offset);
        }
        return [];
    }

    /**
     * Get a single record by ID.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @param int    $recordId Record ID
     * @return array|null
     */
    public static function getRecord(string $dbName, string $tableName, int $recordId): ?array
    {
        if (function_exists('getRecordById')) {
            return getRecordById($dbName, $tableName, $recordId);
        }
        return null;
    }

    /**
     * Insert a new record.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @param array  $data Column => value pairs
     * @return array{success: bool, message: string}
     */
    public static function addRecord(string $dbName, string $tableName, array $data): array
    {
        if (function_exists('addRecord')) {
            return addRecord($dbName, $tableName, $data);
        }
        return ['success' => false, 'message' => 'Record functions not loaded.'];
    }

    /**
     * Update a record by ID.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @param int    $recordId Record ID
     * @param array  $data Column => value pairs
     * @return array{success: bool, message: string}
     */
    public static function updateRecord(string $dbName, string $tableName, int $recordId, array $data): array
    {
        if (function_exists('updateRecord')) {
            return updateRecord($dbName, $tableName, $recordId, $data);
        }
        return ['success' => false, 'message' => 'Record functions not loaded.'];
    }

    /**
     * Delete a record by ID.
     *
     * @param string $dbName Database name
     * @param string $tableName Table name
     * @param int    $recordId Record ID
     * @return array{success: bool, message: string}
     */
    public static function deleteRecord(string $dbName, string $tableName, int $recordId): array
    {
        if (function_exists('deleteRecord')) {
            return deleteRecord($dbName, $tableName, $recordId);
        }
        return ['success' => false, 'message' => 'Record functions not loaded.'];
    }
}
