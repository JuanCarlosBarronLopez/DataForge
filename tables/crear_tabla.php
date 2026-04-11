<?php
/**
 * Create Table Action Handler
 *
 * @package  DataForge
 * @module   Tables
 */

require_once __DIR__ . '/table_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../auth/auth_functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbName = trim($_POST['dbName'] ?? '');
    requireCsrfToken('view_db.php?dbName=' . urlencode($dbName));

    $tableName = trim($_POST['tableName'] ?? '');
    $columnDefinitions = $_POST['columns'] ?? [];

    if (empty($dbName) || empty($tableName)) {
        setFlash('error', 'Faltan el nombre de la base de datos o de la tabla.');
        header('Location: view_db.php?dbName=' . urlencode($dbName));
        exit();
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        setFlash('error', 'Nombre de tabla inválido. Solo letras, números y guiones bajos.');
        header('Location: view_db.php?dbName=' . urlencode($dbName));
        exit();
    }

    if (empty($columnDefinitions)) {
        setFlash('error', 'Debes definir al menos una columna.');
        header('Location: view_db.php?dbName=' . urlencode($dbName));
        exit();
    }

    // Validate and clean column definitions
    $validatedColumns = [];
    foreach ($columnDefinitions as $col) {
        $colName = trim($col['name'] ?? '');
        $colType = strtoupper(trim($col['type'] ?? ''));
        $colLength = (int) ($col['length'] ?? 0);

        if (empty($colName) || empty($colType) || !preg_match('/^[a-zA-Z0-9_]+$/', $colName)) {
            setFlash('error', 'Definición de columna inválida.');
            header('Location: view_db.php?dbName=' . urlencode($dbName));
            exit();
        }

        if ($colType === 'VARCHAR' && ($colLength <= 0 || $colLength > 65535)) {
            setFlash('error', 'La longitud para VARCHAR debe ser entre 1 y 65535.');
            header('Location: view_db.php?dbName=' . urlencode($dbName));
            exit();
        }

        $validatedColumns[] = [
            'name' => $colName,
            'type' => $colType,
            'length' => ($colType === 'VARCHAR') ? $colLength : null,
        ];
    }

    $result = createTable($dbName, $tableName, $validatedColumns);
    if ($result['success']) {
        logActivity('create', 'table', $tableName, $dbName);
    }
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    header('Location: view_db.php?dbName=' . urlencode($dbName));
    exit();
}

setFlash('error', 'Acceso inválido.');
header('Location: ../database/databases.php');
exit();