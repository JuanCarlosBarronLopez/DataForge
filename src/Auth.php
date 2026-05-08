<?php
/**
 * ============================================
 * DataForge — Auth Class
 * ============================================
 *
 * Object-oriented wrapper for authentication operations.
 * Encapsulates user registration, login, session management,
 * theme handling, and activity logging.
 *
 * @package  DataForge
 * @version  3.2.0
 */

namespace DataForge;

class Auth
{
    /**
     * Register a new user.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $confirmPass
     * @return array{success: bool, message: string, userId?: int}
     */
    public static function register(string $username, string $email, string $password, string $confirmPass): array
    {
        // Delegate to existing function for backward compatibility
        if (function_exists('registerUser')) {
            return registerUser($username, $email, $password, $confirmPass);
        }

        return self::performRegistration($username, $email, $password, $confirmPass);
    }

    /**
     * Login a user by email and password.
     *
     * @param string $email
     * @param string $password
     * @return array{success: bool, message: string, user?: array}
     */
    public static function login(string $email, string $password): array
    {
        if (function_exists('loginUser')) {
            return loginUser($email, $password);
        }

        return ['success' => false, 'message' => 'Auth functions not loaded.'];
    }

    /**
     * Logout the current user.
     *
     * @return void
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /**
     * Get the currently logged-in user from session.
     *
     * @return array|null
     */
    public static function getCurrentUser(): ?array
    {
        return $_SESSION['df_user'] ?? null;
    }

    /**
     * Check if a user is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['df_user']);
    }

    /**
     * Require authentication. Redirects to login if not authenticated.
     *
     * @param string $redirectAfter URL to redirect to after login
     * @return void
     */
    public static function requireLogin(string $redirectAfter = ''): void
    {
        if (function_exists('requireLogin')) {
            requireLogin($redirectAfter);
            return;
        }

        if (!self::isLoggedIn()) {
            header('Location: /auth/login.php');
            exit();
        }
    }

    /**
     * Update theme for a user.
     *
     * @param int    $userId
     * @param string $theme
     * @return bool
     */
    public static function updateTheme(int $userId, string $theme): bool
    {
        if (function_exists('updateUserTheme')) {
            return updateUserTheme($userId, $theme);
        }
        return false;
    }

    /**
     * Log an activity.
     *
     * @param string $action
     * @param string $targetType
     * @param string $targetName
     * @param string $dbContext
     * @return void
     */
    public static function logActivity(string $action, string $targetType, string $targetName = '', string $dbContext = ''): void
    {
        if (function_exists('logActivity')) {
            logActivity($action, $targetType, $targetName, $dbContext);
        }
    }

    /**
     * Internal registration logic (standalone).
     */
    private static function performRegistration(string $username, string $email, string $password, string $confirmPass): array
    {
        $username = trim($username);
        $email = trim($email);

        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            return ['success' => false, 'message' => 'El usuario debe tener 3-30 caracteres alfanuméricos.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El correo electrónico no es válido.'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        if ($password !== $confirmPass) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        return ['success' => false, 'message' => 'Database connection not available.'];
    }
}
