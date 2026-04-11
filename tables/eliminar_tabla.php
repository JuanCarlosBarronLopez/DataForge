<?php
/**
 * Delete Table Action Handler (POST-based for security)
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

    requireCsrfToken('view_db.php?dbName=' . urlencode($dbName));

    if (empty($dbName) || empty($tableName)) {
        setFlash('error', 'No se especificó la tabla a eliminar.');
        header('Location: ../database/databases.php');
        exit();
    }

    $result = dropTable($dbName, $tableName);
    if ($result['success']) {
        logActivity('delete', 'table', $tableName, $dbName);
    }
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    header('Location: view_db.php?dbName=' . urlencode($dbName));
    exit();
}

setFlash('error', 'Acceso inválido.');
header('Location: ../database/databases.php');
exit();