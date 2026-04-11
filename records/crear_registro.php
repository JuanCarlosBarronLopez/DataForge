<?php
/**
 * Create Record Action Handler
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
    $redirect = "records.php?dbName=" . urlencode($dbName) . "&tableName=" . urlencode($tableName);

    requireCsrfToken($redirect);

    // Extract record data (exclude meta fields)
    $dataToInsert = [];
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['dbName', 'tableName', 'id', 'csrf_token'], true)) {
            $dataToInsert[$key] = trim($value);
        }
    }

    if (!empty($dbName) && !empty($tableName) && !empty($dataToInsert)) {
        $result = addRecord($dbName, $tableName, $dataToInsert);
        if ($result['success']) {
            logActivity('insert', 'record', $tableName, $dbName);
        }
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
    } else {
        setFlash('error', 'Datos insuficientes para añadir el registro.');
    }

    header("Location: {$redirect}");
    exit();
}

header("Location: ../database/databases.php");
exit();