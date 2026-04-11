<?php
/**
 * Delete Account Handler
 *
 * @package DataForge
 * @module  Account
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/auth_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('profile.php');

    $user = getCurrentUser();
    if (!$user) {
        header('Location: ../auth/login.php');
        exit();
    }

    $userId = (int) $user['id'];
    
    // Eliminar avatar si existe
    $currentUserDb = getUserById($userId);
    if (!empty($currentUserDb['profile_pic'])) {
        $uploadFileDir = __DIR__ . '/../uploads/avatars/';
        if (file_exists($uploadFileDir . $currentUserDb['profile_pic'])) {
            @unlink($uploadFileDir . $currentUserDb['profile_pic']);
        }
    }

    // Call DB deletion (cascades activity_log automatically based on FK)
    deleteUserAccount($userId);

    // Destroy session
    session_unset();
    session_destroy();
    
    // Start new session for flash delivery
    session_start();
    setFlash('error', 'Tu cuenta ha sido eliminada permanentemente.');
    header('Location: ../auth/login.php');
    exit();
} else {
    header('Location: profile.php');
    exit();
}
