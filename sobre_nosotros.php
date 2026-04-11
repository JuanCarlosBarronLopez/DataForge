<?php
/**
 * About Us Page — Raju Technology
 *
 * @package DataForge
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Sobre Nosotros';
$baseUrl = '';
require_once __DIR__ . '/includes/header.php';
?>

<section class="about-section">
    <span class="hero-badge">Nuestro Equipo</span>
    <h2><span class="gradient-text">Raju Technology</span></h2>
    <p>
        Somos un equipo apasionado de desarrolladores de software con una visión clara: crear soluciones tecnológicas
        que marquen la diferencia. Nacimos de la ambición por aplicar nuestros conocimientos en desarrollo web y bases
        de datos para resolver problemas reales y simplificar la vida de las personas.
    </p>
    <p style="margin-top: 1rem;">
        Cada proyecto que abordamos es un reflejo de nuestra dedicación y del riguroso proceso de aprendizaje que hemos
        cultivado. Estamos comprometidos con la innovación, la calidad y las mejores prácticas de la industria.
    </p>

    <div class="tech-stack">
        <span class="tech-tag">PHP 8+</span>
        <span class="tech-tag">MySQL</span>
        <span class="tech-tag">HTML5</span>
        <span class="tech-tag">CSS3</span>
        <span class="tech-tag">JavaScript ES6+</span>
        <span class="tech-tag">CRUD Architecture</span>
        <span class="tech-tag">Security Best Practices</span>
        <span class="tech-tag">Responsive Design</span>
    </div>
</section>

<section class="features-grid" style="padding-bottom: 3rem;">
    <div class="feature-card">
        <span class="feature-icon">🎯</span>
        <h3>Nuestra Misión</h3>
        <p>Crear herramientas de gestión de datos accesibles, seguras y profesionales que simplifiquen el trabajo con
            bases de datos MySQL.</p>
    </div>
    <div class="feature-card">
        <span class="feature-icon">🚀</span>
        <h3>Nuestra Visión</h3>
        <p>Convertirnos en referencia en soluciones de administración de datos para entornos educativos y empresariales.
        </p>
    </div>
    <div class="feature-card">
        <span class="feature-icon">💡</span>
        <h3>Nuestros Valores</h3>
        <p>Calidad de código, seguridad por defecto, diseño centrado en el usuario y mejora continua son nuestros
            pilares.</p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>