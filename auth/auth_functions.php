<?php
/**
 * ============================================
 * DataForge — Authentication & User Functions
 * ============================================
 *
 * Handles all user-related operations:
 * - User registration & login
 * - Session management
 * - Theme management
 * - Activity logging
 *
 * @package  DataForge
 * @module   Auth
 * @version  3.0.0
 */

require_once __DIR__ . '/../config.php';

// ─── Theme Definitions ────────────────────────────────────────────────────
const AVAILABLE_THEMES = [
    'neutral'    => ['name' => 'Tech Dark',   'icon' => '💻', 'desc' => 'Tecnología & Desarrollo',    'industry' => 'General'],
    'medico'     => ['name' => 'MediCare',    'icon' => '🏥', 'desc' => 'Salud & Medicina',            'industry' => 'Médico'],
    'alimentos'  => ['name' => 'FoodPro',     'icon' => '🍽️', 'desc' => 'Restaurantes & Alimentos',    'industry' => 'Alimentos'],
    'ferreteria' => ['name' => 'IronForge',   'icon' => '🔧', 'desc' => 'Ferretería & Construcción',  'industry' => 'Construcción'],
    'legal'      => ['name' => 'LexDesk',     'icon' => '⚖️', 'desc' => 'Servicios Legales',           'industry' => 'Legal'],
    'educacion'  => ['name' => 'EduBase',     'icon' => '🎓', 'desc' => 'Educación & Escuelas',        'industry' => 'Educación'],
    'retail'     => ['name' => 'ShopFlow',    'icon' => '🛍️', 'desc' => 'Retail & Comercio',           'industry' => 'Retail'],
];

/**
 * Get a connection to the DataForge system database.
 *
 * @return mysqli
 * @throws Exception
 */
function getSystemDbConnection(): mysqli
{
    require_once __DIR__ . '/../database/db_functions.php';
    return getDbConnection(SYSTEM_DB);
}

/**
 * Install the system database and tables if they don't exist.
 *
 * @return bool
 */
function installSystemDb(): bool
{
    try {
        require_once __DIR__ . '/../database/db_functions.php';
        $conn = getDbConnection(); // connect without specifying DB

        // Create system database
        $conn->query("CREATE DATABASE IF NOT EXISTS `" . SYSTEM_DB . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db(SYSTEM_DB);

        // Create users table
        $conn->query("CREATE TABLE IF NOT EXISTS `users` (
            `id`               INT AUTO_INCREMENT PRIMARY KEY,
            `username`         VARCHAR(50)  NOT NULL,
            `email`            VARCHAR(100) NOT NULL UNIQUE,
            `password_hash`    VARCHAR(255) NOT NULL,
            `theme`            VARCHAR(30)  NOT NULL DEFAULT 'neutral',
            `onboarding_done`  TINYINT(1)   NOT NULL DEFAULT 0,
            `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `last_login`       TIMESTAMP NULL,
            UNIQUE KEY `uk_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // --- UPGRADE SCRIPT PARA V3.1 ---
        $checkPic = $conn->query("SHOW COLUMNS FROM `users` LIKE 'profile_pic'");
        if ($checkPic->num_rows === 0) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `profile_pic` VARCHAR(255) NULL AFTER `email`");
        }
        $checkMode = $conn->query("SHOW COLUMNS FROM `users` LIKE 'color_mode'");
        if ($checkMode->num_rows === 0) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `color_mode` VARCHAR(10) DEFAULT 'dark' AFTER `theme`");
        }
        // --------------------------------

        // Create activity log table
        $conn->query("CREATE TABLE IF NOT EXISTS `activity_log` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`      INT          NOT NULL,
            `action`       VARCHAR(60)  NOT NULL,
            `target_type`  VARCHAR(40)  NOT NULL,
            `target_name`  VARCHAR(255) DEFAULT NULL,
            `db_context`   VARCHAR(255) DEFAULT NULL,
            `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("installSystemDb failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Register a new user.
 *
 * @param string $username
 * @param string $email
 * @param string $password      Plain-text password
 * @param string $confirmPass   Confirmation password
 * @return array{success: bool, message: string, userId?: int}
 */
function registerUser(string $username, string $email, string $password, string $confirmPass): array
{
    $username = trim($username);
    $email    = trim($email);

    // Validations
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        return ['success' => false, 'message' => 'El usuario debe tener 3-30 caracteres alfanuméricos o guión bajo.'];
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

    try {
        $conn = getSystemDbConnection();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $conn->prepare(
            "INSERT INTO `users` (`username`, `email`, `password_hash`) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('sss', $username, $email, $hash);
        $stmt->execute();
        $newId = $conn->insert_id;
        $stmt->close();
        $conn->close();

        return ['success' => true, 'message' => '¡Cuenta creada exitosamente!', 'userId' => $newId];
    } catch (\mysqli_sql_exception $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            return ['success' => false, 'message' => 'El correo o nombre de usuario ya está registrado.'];
        }
        error_log("registerUser error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al crear la cuenta. Inténtalo de nuevo.'];
    }
}

/**
 * Login a user by email and password.
 *
 * @param string $email
 * @param string $password
 * @return array{success: bool, message: string, user?: array}
 */
function loginUser(string $email, string $password): array
{
    $email = trim($email);

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Correo y contraseña son requeridos.'];
    }

    try {
        $conn = getSystemDbConnection();
        $stmt = $conn->prepare("SELECT * FROM `users` WHERE `email` = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $conn->close();
            return ['success' => false, 'message' => 'Correo o contraseña incorrectos.'];
        }

        // Update last_login
        $upd = $conn->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = ?");
        $upd->bind_param('i', $user['id']);
        $upd->execute();
        $upd->close();
        $conn->close();

        // Store in session (never store password_hash in session)
        unset($user['password_hash']);
        $_SESSION['df_user'] = $user;

        return ['success' => true, 'message' => 'Bienvenido, ' . $user['username'] . '!', 'user' => $user];
    } catch (Exception $e) {
        error_log("loginUser error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error del servidor. Inténtalo de nuevo.'];
    }
}

/**
 * Get user data by ID (fresh from DB).
 *
 * @param int $userId
 * @return array|null
 */
function getUserById(int $userId): ?array
{
    try {
        $conn = getSystemDbConnection();
        $stmt = $conn->prepare("SELECT `id`,`username`,`email`,`profile_pic`,`theme`,`color_mode`,`onboarding_done`,`created_at`,`last_login` FROM `users` WHERE `id` = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user ?: null;
    } catch (Exception $e) {
        error_log("getUserById error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get the currently logged-in user from session.
 *
 * @return array|null
 */
function getCurrentUser(): ?array
{
    return $_SESSION['df_user'] ?? null;
}

/**
 * Update user identity (profile).
 *
 * @param int $userId
 * @param string $username
 * @param string $email
 * @param string $colorMode
 * @param string|null $profilePic
 * @return array{success: bool, message: string}
 */
function updateUserProfile(int $userId, string $username, string $email, string $colorMode, ?string $profilePic): array
{
    $username = trim($username);
    $email    = trim($email);

    if (empty($username) || empty($email)) {
        return ['success' => false, 'message' => 'Nombre de usuario y correo son obligatorios.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'El correo electrónico no es válido.'];
    }

    try {
        $conn = getSystemDbConnection();
        
        if ($profilePic !== null) {
            $stmt = $conn->prepare("UPDATE `users` SET `username` = ?, `email` = ?, `color_mode` = ?, `profile_pic` = ? WHERE `id` = ?");
            $stmt->bind_param('ssssi', $username, $email, $colorMode, $profilePic, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE `users` SET `username` = ?, `email` = ?, `color_mode` = ? WHERE `id` = ?");
            $stmt->bind_param('sssi', $username, $email, $colorMode, $userId);
        }
        
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Update session
        if (isset($_SESSION['df_user'])) {
            $_SESSION['df_user']['username'] = $username;
            $_SESSION['df_user']['email'] = $email;
            $_SESSION['df_user']['color_mode'] = $colorMode;
            if ($profilePic !== null) {
                $_SESSION['df_user']['profile_pic'] = $profilePic;
            }
        }
        return ['success' => true, 'message' => 'Perfil actualizado exitosamente.'];
    } catch (\mysqli_sql_exception $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            return ['success' => false, 'message' => 'El correo o nombre de usuario ya está en uso.'];
        }
        return ['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()];
    }
}

/**
 * Delete a user account completely.
 *
 * @param int $userId
 * @return void
 */
function deleteUserAccount(int $userId): void
{
    try {
        $conn = getSystemDbConnection();
        $stmt = $conn->prepare("DELETE FROM `users` WHERE `id` = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("deleteUserAccount error: " . $e->getMessage());
    }
}

/**
 * Require the user to be logged in.
 * Redirects to login if not authenticated.
 *
 * @param string $redirectAfter  URL to return to after login (relative path from root)
 * @return void
 */
function requireLogin(string $redirectAfter = ''): void
{
    if (empty($_SESSION['df_user'])) {
        $redirect = !empty($redirectAfter) ? '?redirect=' . urlencode($redirectAfter) : '';
        // Determine depth to find auth/login.php
        $script   = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $root     = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
        $depth    = substr_count(str_replace($root, '', $script), DIRECTORY_SEPARATOR) - 1;
        $prefix   = str_repeat('../', max(0, $depth));
        header('Location: ' . $prefix . 'auth/login.php' . $redirect);
        exit();
    }
}

/**
 * Update the theme preference for the current user.
 *
 * @param int    $userId
 * @param string $theme
 * @return bool
 */
function updateUserTheme(int $userId, string $theme): bool
{
    if (!array_key_exists($theme, AVAILABLE_THEMES)) {
        return false;
    }

    try {
        $conn = getSystemDbConnection();
        $stmt = $conn->prepare("UPDATE `users` SET `theme` = ? WHERE `id` = ?");
        $stmt->bind_param('si', $theme, $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Update session
        if (isset($_SESSION['df_user'])) {
            $_SESSION['df_user']['theme'] = $theme;
        }
        return true;
    } catch (Exception $e) {
        error_log("updateUserTheme error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark onboarding as complete for a user.
 *
 * @param int $userId
 * @return void
 */
function completeOnboarding(int $userId): void
{
    try {
        $conn = getSystemDbConnection();
        $stmt = $conn->prepare("UPDATE `users` SET `onboarding_done` = 1 WHERE `id` = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        if (isset($_SESSION['df_user'])) {
            $_SESSION['df_user']['onboarding_done'] = 1;
        }
    } catch (Exception $e) {
        error_log("completeOnboarding error: " . $e->getMessage());
    }
}

/**
 * Log a user action to the activity log.
 *
 * @param string $action       e.g. 'create', 'delete', 'alter'
 * @param string $targetType   e.g. 'database', 'table', 'record'
 * @param string $targetName   e.g. 'my_database'
 * @param string $dbContext    context database name
 * @return void
 */
function logActivity(string $action, string $targetType, string $targetName = '', string $dbContext = ''): void
{
    $user = getCurrentUser();
    if (!$user) return;
    $userId = (int) $user['id'];

    try {
        $conn = getSystemDbConnection();
        $stmt = $conn->prepare(
            "INSERT INTO `activity_log` (`user_id`,`action`,`target_type`,`target_name`,`db_context`) VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param('issss', $userId, $action, $targetType, $targetName, $dbContext);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Log silently — activity logging failure should not break the app
        error_log("logActivity error: " . $e->getMessage());
    }
}

/**
 * Get recent activity for a user.
 *
 * @param int $userId
 * @param int $limit
 * @return array
 */
function getRecentActivity(int $userId, int $limit = 10): array
{
    try {
        $conn = getSystemDbConnection();
        $stmt = $conn->prepare(
            "SELECT * FROM `activity_log` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT ?"
        );
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        $conn->close();
        return $rows;
    } catch (Exception $e) {
        error_log("getRecentActivity error: " . $e->getMessage());
        return [];
    }
}
