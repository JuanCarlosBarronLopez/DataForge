<?php
/**
 * Onboarding — Theme Selector
 * Shown once after registration.
 *
 * @package DataForge
 * @module  Auth
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin();

$user = getCurrentUser();

// If onboarding already done, go to dashboard
if ($user['onboarding_done']) {
    header('Location: ../dashboard.php');
    exit();
}

// Handle theme selection POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('onboarding.php');
    $theme = trim($_POST['theme'] ?? 'neutral');
    updateUserTheme((int) $user['id'], $theme);
    completeOnboarding((int) $user['id']);

    setFlash('success', '¡Bienvenido! Tu espacio de trabajo está listo.');
    header('Location: ../dashboard.php');
    exit();
}

$themes = AVAILABLE_THEMES;
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elige tu estilo | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="icon" href="../img/logo.png">
</head>
<body class="onboarding-body theme-neutral">

<div class="onboarding-wrapper">

    <div class="onboarding-header">
        <div class="onboarding-step-badge">
            <span class="step-dot"></span>
            Paso 1 de 1 — Personalización
        </div>
        <h1 class="onboarding-title">
            ¡Hola, <span class="gradient-text"><?= htmlspecialchars($user['username']) ?>!</span><br>
            ¿Qué tipo de negocio tienes?
        </h1>
        <p class="onboarding-desc">
            Selecciona el perfil que mejor describe tu giro comercial.<br>
            DataForge adaptará su interfaz visual para ti. <strong>Puedes cambiarlo después.</strong>
        </p>
    </div>

    <form method="POST" action="onboarding.php" id="onboardingForm">
        <?php csrfField(); ?>
        <input type="hidden" name="theme" id="selectedTheme" value="neutral">

        <div class="theme-grid">
            <?php foreach ($themes as $themeId => $theme): ?>
                <div class="theme-card preview-<?= $themeId ?>"
                     data-theme="<?= $themeId ?>"
                     id="card-<?= $themeId ?>"
                     onclick="selectTheme('<?= $themeId ?>')"
                     role="button"
                     tabindex="0"
                     aria-pressed="<?= $themeId === 'neutral' ? 'true' : 'false' ?>">

                    <div class="theme-check">✓</div>

                    <!-- Mini UI Preview -->
                    <div class="theme-preview">
                        <div class="theme-preview-bar">
                            <div class="theme-preview-dot"></div>
                            <div class="theme-preview-dot" style="opacity:0.4"></div>
                            <div class="theme-preview-dot" style="opacity:0.25"></div>
                        </div>
                        <div class="theme-preview-content">
                            <div class="theme-preview-line"></div>
                            <div class="theme-preview-line"></div>
                        </div>
                    </div>

                    <span class="theme-icon"><?= $theme['icon'] ?></span>
                    <div class="theme-name"><?= htmlspecialchars($theme['name']) ?></div>
                    <span class="theme-industry"><?= htmlspecialchars($theme['industry']) ?></span>
                    <p class="theme-desc"><?= htmlspecialchars($theme['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="onboarding-actions">
            <button type="submit" class="btn btn-primary btn-onboard-confirm" id="confirmBtn">
                <span class="btn-icon">🚀</span>
                Comenzar con este estilo
            </button>
        </div>
    </form>

</div>

<div class="auth-bg-orb auth-bg-orb-1"></div>
<div class="auth-bg-orb auth-bg-orb-2"></div>

<script>
let currentTheme = 'neutral';

function selectTheme(themeId) {
    // Deselect previous
    const prev = document.getElementById('card-' + currentTheme);
    if (prev) {
        prev.classList.remove('selected');
        prev.setAttribute('aria-pressed', 'false');
    }

    // Select new
    currentTheme = themeId;
    const card = document.getElementById('card-' + themeId);
    if (card) {
        card.classList.add('selected');
        card.setAttribute('aria-pressed', 'true');
    }

    document.getElementById('selectedTheme').value = themeId;

    // Apply real-time preview on body
    document.body.className = 'onboarding-body theme-' + themeId;
}

// Keyboard support
document.querySelectorAll('.theme-card').forEach(card => {
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            selectTheme(card.dataset.theme);
        }
    });
});

// Pre-select neutral
selectTheme('neutral');
</script>
</body>
</html>
