<?php
/**
 * Logout Handler
 *
 * @package DataForge
 * @module  Auth
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/flash.php';

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
