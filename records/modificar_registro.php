<?php
/**
 * Update Record Action Handler
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

    // Extract record data
    $dataToUpdate = $_POST;
    unset($dataToUpdate['dbName'], $dataToUpdate['tableName'], $dataToUpdate['id'], $dataToUpdate['csrf_token']);

    if (!empty($dbName) && !empty($tableName) && $recordId > 0 && !empty($dataToUpdate)) {
        $result = updateRecord($dbName, $tableName, $recordId, $dataToUpdate);
        if ($result['success']) {
            logActivity('update', 'record', $tableName . ' (ID: ' . $recordId . ')', $dbName);
        }
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
    } else {
        setFlash('error', 'Datos insuficientes para actualizar el registro.');
    }

    header("Location: {$redirect}");
    exit();
}

header("Location: ../database/databases.php");
exit();