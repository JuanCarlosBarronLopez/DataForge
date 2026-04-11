<?php
/**
 * Change Theme Handler (POST only)
 *
 * @package DataForge
 * @module  Auth
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin('../auth/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../account/profile.php');
    exit();
}

requireCsrfToken('../account/profile.php');

$user  = getCurrentUser();
$theme = trim($_POST['theme'] ?? '');

if (updateUserTheme((int) $user['id'], $theme)) {
    setFlash('success', 'Tema actualizado correctamente.');
} else {
    setFlash('error', 'Tema inválido o error al actualizar.');
}

header('Location: ../account/profile.php');
exit();
