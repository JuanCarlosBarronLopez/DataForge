<?php
/**
 * Delete Record Action Handler (POST-based for security)
 *
 * @package  DataForge
 * @module   Records
 */

require_once __DIR__ . '/record_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../auth/auth_functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbName = $_POST['dbName'] ?? '';
    $tableName = $_POST['tableName'] ?? '';
    $recordId = (int) ($_POST['id'] ?? 0);
    $redirect = "records.php?dbName=" . urlencode($dbName) . "&tableName=" . urlencode($tableName);

    requireCsrfToken($redirect);

    if (empty($dbName) || empty($tableName) || $recordId <= 0) {
        setFlash('error', 'Faltan datos para eliminar el registro.');
        header("Location: {$redirect}");
        exit();
    }

    $result = deleteRecord($dbName, $tableName, $recordId);
    if ($result['success']) {
        logActivity('delete', 'record', $tableName . ' (ID: ' . $recordId . ')', $dbName);
    }
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    header("Location: {$redirect}");
    exit();
}

setFlash('error', 'Acceso inválido.');
header('Location: ../database/databases.php');
exit();