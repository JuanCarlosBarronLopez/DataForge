<?php
/**
 * Landing Page — DataForge CRUD Manager v3.0
 *
 * Redirects authenticated users to dashboard.
 * Shows landing page for guests.
 *
 * @package DataForge
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/auth_functions.php';

$pageTitle = 'Inicio';
$baseUrl   = '';

// Redirect authenticated users straight to dashboard
if (!empty($_SESSION['df_user'])) {
    header('Location: dashboard.php');
    exit();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-badge">⚡ Gestor MySQL Profesional</div>
    <h1>
        Gestiona tus datos con
        <span class="gradient-text">DataForge</span>
    </h1>
    <p>
        Un gestor CRUD profesional para MySQL. Crea bases de datos, diseña tablas con columnas dinámicas y administra
        registros — todo desde una interfaz moderna e intuitiva.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
        <a class="btn btn-primary" href="auth/register.php">
            <span class="btn-icon">🚀</span> Crear Cuenta Gratis
        </a>
        <a class="btn btn-ghost" href="auth/login.php">
            Iniciar Sesión →
        </a>
    </div>
</section>

<!-- Features -->
<section class="features-grid">
    <div class="feature-card">
        <span class="feature-icon">🗄️</span>
        <h3>Gestión de Bases de Datos</h3>
        <p>Crea, visualiza y elimina bases de datos MySQL directamente desde el navegador con validación en tiempo real.
        </p>
    </div>
    <div class="feature-card">
        <span class="feature-icon">📊</span>
        <h3>Diseñador de Tablas</h3>
        <p>Constructor visual de tablas con soporte para múltiples tipos de datos: INT, VARCHAR, DECIMAL, DATE, TEXT y
            más.</p>
    </div>
    <div class="feature-card">
        <span class="feature-icon">📝</span>
        <h3>CRUD Completo</h3>
        <p>Operaciones Create, Read, Update y Delete sobre registros con formularios dinámicos generados
            automáticamente.</p>
    </div>
    <div class="feature-card">
        <span class="feature-icon">🔒</span>
        <h3>Seguridad Integrada</h3>
        <p>Autenticación segura, tokens CSRF, consultas preparadas, sanitización de inputs y operaciones vía POST.</p>
    </div>
    <div class="feature-card">
        <span class="feature-icon">🎨</span>
        <h3>7 Temas por Industria</h3>
        <p>Elige el estilo visual que más se adapte a tu negocio: médico, alimentos, ferretería, legal y más.</p>
    </div>
    <div class="feature-card">
        <span class="feature-icon">📈</span>
        <h3>Dashboard con Métricas</h3>
        <p>Panel de control con estadísticas en tiempo real de tus bases de datos, tablas y actividad reciente.</p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>