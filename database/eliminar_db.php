<?php
/**
 * Delete Database Action Handler
 *
 * Processes POST request to drop a MySQL database.
 * Changed from GET to POST for security (destructive operation).
 *
 * @package  DataForge
 * @module   Database
 */

require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../auth/auth_functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('databases.php');

    $dbName = trim($_POST['dbName'] ?? '');

    if (empty($dbName)) {
        setFlash('error', 'No se especificó la base de datos a eliminar.');
        header('Location: databases.php');
        exit();
    }

    $result = dropDatabase($dbName);
    if ($result['success']) {
        logActivity('delete', 'database', $dbName);
    }
    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    header('Location: databases.php');
    exit();
}

// Direct access without POST — redirect
setFlash('error', 'Acceso inválido para eliminar base de datos.');
header('Location: databases.php');
exit();