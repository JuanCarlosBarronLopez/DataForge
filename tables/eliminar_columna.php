<?php
/**
 * Drop Column Action Handler
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
    $colName = trim($_POST['colName'] ?? '');

    requireCsrfToken('set_table_form.php?dbName=' . urlencode($dbName) . '&tableName=' . urlencode($tableName));

    if (empty($dbName) || empty($tableName) || empty($colName)) {
        setFlash('error', 'Faltan datos para eliminar la columna.');
        header('Location: set_table_form.php?dbName=' . urlencode($dbName) . '&tableName=' . urlencode($tableName));
        exit();
    }

    $result = dropColumn($dbName, $tableName, $colName);
    if ($result['success']) {
        logActivity('alter', 'table', $tableName . ' (Drop Col: ' . $colName . ')', $dbName);
    }
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    
    header('Location: set_table_form.php?dbName=' . urlencode($dbName) . '&tableName=' . urlencode($tableName));
    exit();
}

setFlash('error', 'Acceso inválido.');
header('Location: ../database/databases.php');
exit();
