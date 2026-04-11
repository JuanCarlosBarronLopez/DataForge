<?php
/**
 * Login Page
 *
 * @package DataForge
 * @module  Auth
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['df_user'])) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('login.php');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = loginUser($email, $password);
    if ($result['success']) {
        $user = $_SESSION['df_user'];
        if (!$user['onboarding_done']) {
            header('Location: onboarding.php');
        } else {
            header('Location: ../dashboard.php');
        }
        exit();
    } else {
        $error = $result['message'];
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../auth/auth.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body class="auth-body theme-neutral">

<div class="auth-container">
    <div class="auth-card">

        <!-- Logo & Brand -->
        <div class="auth-brand">
            <div class="auth-logo">
                <img src="../img/logo.png" alt="DataForge Logo">
            </div>
            <h1 class="auth-title gradient-text"><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="auth-subtitle">Tu gestor de bases de datos inteligente</p>
        </div>

        <h2 class="auth-heading">Bienvenido de nuevo</h2>
        <p class="auth-desc">Ingresa tus credenciales para continuar.</p>

        <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">
                <span>✕</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <?php csrfField(); ?>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email"
                       placeholder="tu@correo.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-password-wrapper">
                    <input type="password" id="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', this)" aria-label="Mostrar contraseña">
                        <svg id="eye-icon-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-auth">
                <span>Iniciar Sesión</span>
                <span class="btn-icon">→</span>
            </button>
        </form>

        <div class="auth-footer-links">
            <p>¿No tienes cuenta? <a href="register.php">Regístrate gratis</a></p>
        </div>

    </div>

    <!-- Decorative background -->
    <div class="auth-bg-orb auth-bg-orb-1"></div>
    <div class="auth-bg-orb auth-bg-orb-2"></div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.classList.toggle('active');
}
</script>
</body>
</html>
