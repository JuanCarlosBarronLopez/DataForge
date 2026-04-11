<?php
/**
 * Register Page
 *
 * @package DataForge
 * @module  Auth
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

// Already logged in → dashboard
if (!empty($_SESSION['df_user'])) {
    header('Location: ../dashboard.php');
    exit();
}

// Ensure system DB is installed (idempotent)
if (!installSystemDb()) {
    $dbError = "Error Crítico: No se pudo conectar a MySQL o crear la base 'dataforge_system'. Verifica tu usuario/contraseña en `.env.example` o tu configuración de XAMPP.";
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('register.php');
    $username    = trim($_POST['username']     ?? '');
    $email       = trim($_POST['email']        ?? '');
    $password    = $_POST['password']          ?? '';
    $confirmPass = $_POST['confirm_password']  ?? '';

    $result = registerUser($username, $email, $password, $confirmPass);
    if ($result['success']) {
        // Auto-login after registration
        loginUser($email, $password);
        header('Location: onboarding.php');
        exit();
    } else {
        $errors[] = $result['message'];
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | <?= htmlspecialchars(APP_NAME) ?></title>
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

        <div class="auth-brand">
            <div class="auth-logo">
                <img src="../img/logo.png" alt="DataForge Logo">
            </div>
            <h1 class="auth-title gradient-text"><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="auth-subtitle">Crea tu cuenta y gestiona tus datos</p>
        </div>

        <h2 class="auth-heading">Crear Cuenta</h2>
        <p class="auth-desc">Únete en segundos. Empieza a gestionar tus bases de datos.</p>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert auth-alert-error">
                <span>✕</span> <?= htmlspecialchars($errors[0]) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($dbError)): ?>
            <div class="auth-alert auth-alert-error">
                <span>✕</span> <?= htmlspecialchars($dbError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form" id="registerForm">
            <?php csrfField(); ?>

            <div class="form-group">
                <label for="username">Nombre de usuario</label>
                <input type="text" id="username" name="username"
                       placeholder="mi_usuario"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       pattern="[a-zA-Z0-9_]{3,30}"
                       title="3-30 caracteres: letras, números o guión bajo"
                       required autocomplete="username">
                <span class="form-hint">3–30 caracteres: letras, números y guión bajo (_)</span>
            </div>

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
                           placeholder="Mínimo 8 caracteres"
                           required minlength="8" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <div class="password-strength-bar" id="strengthBar"><div class="strength-fill"></div></div>
                <span class="form-hint" id="strengthLabel">Fuerza de contraseña</span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar contraseña</label>
                <div class="input-password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Repite tu contraseña"
                           required autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <span class="form-hint" id="matchLabel"></span>
            </div>

            <button type="submit" class="btn btn-primary btn-auth" id="submitBtn">
                <span>Crear Cuenta</span>
                <span class="btn-icon">→</span>
            </button>
        </form>

        <div class="auth-footer-links">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>
    </div>

    <div class="auth-bg-orb auth-bg-orb-1"></div>
    <div class="auth-bg-orb auth-bg-orb-2"></div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.classList.toggle('active');
}

// Password strength meter
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const fill = document.querySelector('.strength-fill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = ['', 'Débil', 'Regular', 'Buena', 'Fuerte'];
    const colors = ['', '#ef4444', '#f59e0b', '#10b981', '#06b6d4'];
    fill.style.width = (score * 25) + '%';
    fill.style.background = colors[score] || '#ef4444';
    label.textContent = levels[score] || 'Fuerza de contraseña';
    label.style.color = colors[score] || '';
});

// Password match check
document.getElementById('confirm_password').addEventListener('input', function () {
    const pass = document.getElementById('password').value;
    const lbl  = document.getElementById('matchLabel');
    if (this.value === '') { lbl.textContent = ''; return; }
    if (this.value === pass) {
        lbl.textContent = '✓ Las contraseñas coinciden';
        lbl.style.color = '#10b981';
    } else {
        lbl.textContent = '✕ Las contraseñas no coinciden';
        lbl.style.color = '#ef4444';
    }
});
</script>
</body>
</html>
