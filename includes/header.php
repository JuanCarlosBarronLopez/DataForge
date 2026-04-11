<?php
/**
 * ============================================
 * Shared Header Template — v3.0
 * ============================================
 *
 * Renders the HTML <head>, <header>, and navigation bar.
 * Automatically applies the user's chosen theme class to <body>.
 *
 * @var string $pageTitle   Page title
 * @var string $baseUrl     Relative path to project root (e.g., '../' or '')
 *
 * @package  DataForge
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../auth/auth_functions.php';

$baseUrl   = $baseUrl ?? '';
$pageTitle = $pageTitle ?? 'Panel de Control';

// Determine theme & mode
$_currentUser  = getCurrentUser();
$_currentTheme = $_currentUser['theme'] ?? 'neutral';
$_colorMode    = $_currentUser['color_mode'] ?? 'dark';
$_validThemes  = array_keys(AVAILABLE_THEMES);
if (!in_array($_currentTheme, $_validThemes, true)) {
    $_currentTheme = 'neutral';
}

$_bodyClasses = 'theme-' . htmlspecialchars($_currentTheme);
if ($_colorMode === 'light') {
    $_bodyClasses .= ' mode-light';
}

// Determine which nav link is "active" (rough match)
$_currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
$_currentDir    = basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
          content="<?= htmlspecialchars(APP_NAME) ?> — Gestor profesional de bases de datos MySQL con operaciones CRUD completas.">
    <meta name="author" content="Raju Technology">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(APP_NAME) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="<?= $baseUrl ?>style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>themes.css">
    <link rel="icon" href="<?= $baseUrl ?>img/logo.png">
</head>

<body class="<?= $_bodyClasses ?>">

<header>
    <div class="header-inner">

        <!-- Logo & Brand -->
        <a href="<?= $baseUrl ?>dashboard.php" class="logo-link">
            <div class="logo_container">
                <img src="<?= $baseUrl ?>img/logo.png" alt="<?= htmlspecialchars(APP_NAME) ?> Logo">
            </div>
            <h1 class="app-title"><?= htmlspecialchars(APP_NAME) ?></h1>
        </a>

        <!-- Navigation -->
        <nav>
            <button class="nav-toggle" id="navToggle" aria-label="Abrir menú">
                <span></span><span></span><span></span>
            </button>

            <ul id="navMenu">
                <li>
                    <a href="<?= $baseUrl ?>dashboard.php"
                       <?= ($_currentScript === 'dashboard.php') ? 'class="nav-active"' : '' ?>>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>database/databases.php"
                       <?= ($_currentDir === 'database' || $_currentScript === 'databases.php') ? 'class="nav-active"' : '' ?>>
                        Bases de Datos
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>sobre_nosotros.php"
                       <?= ($_currentScript === 'sobre_nosotros.php') ? 'class="nav-active"' : '' ?>>
                        Nosotros
                    </a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>documentacion.php"
                       <?= ($_currentScript === 'documentacion.php') ? 'class="nav-active"' : '' ?>>
                        Documentación
                    </a>
                </li>

                <?php if ($_currentUser): ?>
                    <li class="nav-separator-item"><div class="nav-separator"></div></li>
                    <li>
                        <a href="<?= $baseUrl ?>account/profile.php" class="nav-user-badge">
                            <div class="nav-user-avatar" <?php if(!empty($_currentUser['profile_pic'])) echo 'style="background-image: url(\''.$baseUrl.'uploads/avatars/'.$_currentUser['profile_pic'].'\');"'; ?>>
                                <?php if(empty($_currentUser['profile_pic'])) echo strtoupper(substr($_currentUser['username'], 0, 1)); ?>
                            </div>
                            <?= htmlspecialchars($_currentUser['username']) ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $baseUrl ?>auth/logout.php" class="nav-logout-btn" title="Cerrar sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Salir
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?= $baseUrl ?>auth/login.php" class="btn btn-sm btn-primary" style="margin-left:.5rem">
                            Iniciar Sesión
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="main-content">
    <?php displayFlash(); ?>