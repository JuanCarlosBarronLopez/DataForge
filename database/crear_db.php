<?php
/**
 * Create Database Action Handler
 *
 * Processes POST request to create a new MySQL database.
 * Validates CSRF token and input before executing.
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
    $result = createDatabase($dbName);

    if ($result['success']) {
        logActivity('create', 'database', $dbName);
    }

    setFlash($result['success'] ? 'success' : 'error', $result['message']);
    header('Location: databases.php');
    exit();
}

// Direct access without POST — redirect
setFlash('error', 'Acceso inválido.');
header('Location: databases.php');
exit();