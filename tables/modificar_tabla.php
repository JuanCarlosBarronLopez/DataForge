<?php
/**
 * Modify Table Action Handler
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
    $tableName = trim($_POST['tableName'] ?? '');
    $newColumnDefinitions = $_POST['columns'] ?? [];

    requireCsrfToken('view_db.php?dbName=' . urlencode($dbName));

    if (empty($dbName) || empty($tableName)) {
        setFlash('error', 'Faltan datos para modificar la tabla.');
        header('Location: view_db.php?dbName=' . urlencode($dbName));
        exit();
    }

    $currentColumnsDefinition = getTableColumnsDefinition($dbName, $tableName);

    if (empty($currentColumnsDefinition)) {
        setFlash('error', 'La tabla no existe o no tiene columnas.');
        header('Location: view_db.php?dbName=' . urlencode($dbName));
        exit();
    }

    $result = alterTable($dbName, $tableName, $currentColumnsDefinition, $newColumnDefinitions);
    if ($result['success']) {
        logActivity('alter', 'table', $tableName, $dbName);
    }
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    header('Location: view_db.php?dbName=' . urlencode($dbName));
    exit();
}

setFlash('error', 'Acceso inválido.');
header('Location: ../database/databases.php');
exit();