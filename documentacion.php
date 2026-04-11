<?php
/**
 * ============================================
 * Printable Documentation Page
 * ============================================
 *
 * Aggregates project documentation (README, About, Architecture)
 * into a single printable page. Users can save as PDF via browser print.
 *
 * @package  DataForge
 */

require_once __DIR__ . '/config.php';

$pageTitle = 'Documentación';
$baseUrl = '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación |
        <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png">

    <style>
        /* Print-optimized styles */
        .doc-page {
            max-width: 900px;
            margin: 0 auto;
            padding: var(--space-xl) var(--space-lg);
        }

        .doc-section {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-xl);
            margin-bottom: var(--space-xl);
        }

        .doc-section h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: var(--space-lg);
        }

        .doc-section h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: var(--space-lg) 0 var(--space-md);
            color: var(--accent-primary);
        }

        .doc-section p,
        .doc-section li {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: var(--space-sm);
        }

        .doc-section ul {
            margin-left: var(--space-lg);
            margin-bottom: var(--space-md);
        }

        .doc-section li {
            margin-bottom: var(--space-xs);
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: var(--space-md) 0;
        }

        .doc-table th,
        .doc-table td {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            text-align: left;
            font-size: 0.9rem;
        }

        .doc-table th {
            background: rgba(6, 182, 212, 0.05);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .doc-header {
            text-align: center;
            margin-bottom: var(--space-2xl);
        }

        .doc-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: var(--space-sm);
        }

        .doc-header p {
            color: var(--text-muted);
        }

        .doc-actions {
            display: flex;
            justify-content: center;
            gap: var(--space-md);
            margin-bottom: var(--space-2xl);
        }

        .doc-code {
            background: var(--bg-input);
            padding: var(--space-md);
            border-radius: var(--radius-md);
            font-family: var(--font-mono);
            font-size: 0.85rem;
            color: var(--accent-primary);
            overflow-x: auto;
            margin: var(--space-md) 0;
        }

        .doc-tree {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            line-height: 1.8;
            color: var(--text-secondary);
        }

        /* Print-specific overrides */
        @media print {
            body {
                background: #fff !important;
                color: #1a1a1a !important;
            }

            header,
            footer,
            .doc-actions,
            .nav-toggle {
                display: none !important;
            }

            .doc-page {
                max-width: 100%;
                padding: 0;
            }

            .doc-section {
                background: #fff !important;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                -webkit-backdrop-filter: none !important;
                backdrop-filter: none !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .doc-section h2 {
                color: #0a0e1a !important;
                -webkit-text-fill-color: #0a0e1a !important;
            }

            .doc-section h3 {
                color: #0369a1 !important;
            }

            .doc-section p,
            .doc-section li {
                color: #333 !important;
            }

            .gradient-text {
                -webkit-text-fill-color: #0369a1 !important;
            }

            .doc-header h1 {
                color: #0a0e1a !important;
                -webkit-text-fill-color: #0a0e1a !important;
            }

            .doc-table th {
                background: #f5f5f5 !important;
                color: #333 !important;
            }

            .doc-table td {
                color: #333 !important;
            }

            .doc-code {
                background: #f5f5f5 !important;
                color: #0369a1 !important;
            }

            .badge,
            .tech-tag {
                border-color: #ddd !important;
                color: #333 !important;
                background: #f5f5f5 !important;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="header-inner">
            <a href="index.php" class="logo-link">
                <div class="logo_container">
                    <img src="img/logo.png" alt="DataForge Logo">
                </div>
                <h1 class="app-title">DataForge</h1>
            </a>
            <nav>
                <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                    <span></span><span></span><span></span>
                </button>
                <ul id="navMenu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="database/databases.php">Bases de Datos</a></li>
                    <li><a href="sobre_nosotros.php">Nosotros</a></li>
                    <li><a href="documentacion.php">Documentación</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="doc-page">

        <!-- Title & Download Button -->
        <div class="doc-header">
            <h1><span class="gradient-text">DataForge CRUD Manager</span></h1>
            <p>Documentación Técnica Completa — v
                <?= htmlspecialchars(APP_VERSION) ?>
            </p>
        </div>

        <div class="doc-actions">
            <button class="btn btn-primary" onclick="window.print()">
                📄 Descargar como PDF
            </button>
            <a href="index.php" class="btn btn-ghost">← Volver al Inicio</a>
        </div>

        <!-- Section 1: Overview -->
        <div class="doc-section">
            <h2>📋 Descripción del Proyecto</h2>
            <p>
                <strong>DataForge CRUD Manager</strong> es una aplicación web profesional para la gestión de bases de
                datos MySQL.
                Permite crear bases de datos, diseñar tablas con definiciones de columnas dinámicas y realizar
                operaciones
                CRUD completas (Crear, Leer, Actualizar, Eliminar) sobre registros — todo desde una interfaz moderna y
                segura.
            </p>

            <h3>Características Principales</h3>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Característica</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🗄️ Gestión de Bases de Datos</td>
                        <td>Crear, listar y eliminar bases de datos MySQL</td>
                    </tr>
                    <tr>
                        <td>📊 Diseñador de Tablas</td>
                        <td>Constructor visual con 8+ tipos de datos</td>
                    </tr>
                    <tr>
                        <td>📝 CRUD Completo</td>
                        <td>Formularios dinámicos auto-generados para registros</td>
                    </tr>
                    <tr>
                        <td>🔒 Seguridad</td>
                        <td>CSRF, prepared statements, sanitización, POST para destructivas</td>
                    </tr>
                    <tr>
                        <td>🌙 UI Premium</td>
                        <td>Tema oscuro con glassmorphism y micro-animaciones</td>
                    </tr>
                    <tr>
                        <td>📱 Responsive</td>
                        <td>Diseño mobile-first con navegación adaptable</td>
                    </tr>
                    <tr>
                        <td>⚡ Flash Messages</td>
                        <td>Notificaciones toast con auto-dismiss</td>
                    </tr>
                    <tr>
                        <td>🏗️ Templates</td>
                        <td>Sistema de includes PHP compartidos</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 2: Arquitectura y Decisiones de Diseño -->
        <div class="doc-section">
            <h2>🏛️ Arquitectura y Decisiones de Software</h2>
            <p>
                La arquitectura de DataForge representa un compromiso entre rendimiento nativo en PHP y estándares de
                seguridad corporativos. Se diseñó el sistema para evitar las vulnerabilidades clásicas de los sistemas
                monolíticos tradicionales.
            </p>

            <h3>Patrón Action-Domain-Responder (ADR)</h3>
            <p>
                Se implementó una variante del patrón <strong>ADR (Action-Domain-Responder)</strong>, una evolución
                táctica del MVC tradicional enfocado en flujos HTTP:
            </p>
            <ul>
                <li><strong>Action (Handlers):</strong> Archivos dedicados a recibir peticiones POST/GET
                    (`crear_db.php`, `eliminar_tabla.php`). Orquestan la seguridad y redirigen.</li>
                <li><strong>Domain (Functions):</strong> Capa que encapsula la lógica de negocio y las sentencias SQL
                    (`db_functions.php`). Garantiza un aislamiento total de las operaciones críticas.</li>
                <li><strong>Responder (Views):</strong> Interfaces generadas vía templates (`view_db.php`) combinadas
                    con layouts compartidos (`header.php`).</li>
            </ul>

            <h3>Estrategia "Defense in Depth" (Seguridad en Profundidad)</h3>
            <p>El sistema rechaza la seguridad de perímetro único e implementa control en múltiples capas:</p>
            <ul>
                <li><strong>Prevención CSRF:</strong> Un motor criptográfico propio inyecta tokens validados antes de
                    procesar cualquier mutación de estado.</li>
                <li><strong>Mutaciones bajo HTTP POST:</strong> Toda operación destructiva viola intencionalmente la
                    facilidad del GET hipertextual para forzar operaciones POST mediante formularios protegidos,
                    cumpliendo el RFC 7231.</li>
                <li><strong>Filtro Anti-Inyección SQL:</strong> Empleo exclusivo de <em>Prepared Statements</em>
                    paramétricos. Los identificadores estructurales (nombres de tablas) que no admiten bind nativo
                    atraviesan una rigurosa validación de tipo <em>Whitelist Regex</em> estricto
                    (<code>/^[a-zA-Z0-9_]+$/</code>).</li>
            </ul>

            <h3>Gestión Modular y Tolerancia al Cambio</h3>
            <div class="doc-code">
                Flujo Controlado de Identidades:<br>
                Config. Env → Singleton Lógico → Core DB → Handlers de Memoria → Vista Segura<br>
                &nbsp;&nbsp;&nbsp;&nbsp;↑&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓<br>
                Validación Constante&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Capa de Prevención
                XSS (htmlspecialchars)
            </div>
            <p>
                La externalización de la configuración (vía <code>.env</code>) y el sistema de mensajería asíncrona
                (Flash Notifications)
                garantizan que la interfaz de usuario no sufra paradas duras, permitiendo un "Flow State" continuo para
                el analista de datos.
            </p>
        </div>

        <!-- Section 3: Structure -->
        <div class="doc-section">
            <h2>📁 Estructura del Proyecto</h2>
            <div class="doc-code doc-tree">
                dataforge/<br>
                ├──
                config.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Configuración centralizada<br>
                ├── .env.example&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Template
                de variables de entorno<br>
                ├──
                ├──
                index.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Landing page<br>
                Landing page<br>
                ├── sobre_nosotros.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Página Nosotros<br>
                ├── documentacion.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Esta documentación<br>
                ├──
                style.css&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Design system (700+ líneas)<br>
                │<br>
                ├──
                includes/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Componentes compartidos<br>
                │&nbsp;&nbsp;&nbsp;├── header.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Head HTML + navegación<br>
                │&nbsp;&nbsp;&nbsp;├── footer.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Footer + script móvil<br>
                │&nbsp;&nbsp;&nbsp;├──
                csrf.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Protección
                CSRF<br>
                │&nbsp;&nbsp;&nbsp;└──
                flash.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Mensajes toast<br>
                │<br>
                ├──
                database/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Módulo de bases de datos<br>
                │&nbsp;&nbsp;&nbsp;├── db_functions.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Conexión + funciones CRUD<br>
                │&nbsp;&nbsp;&nbsp;├── databases.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Vista de listado<br>
                │&nbsp;&nbsp;&nbsp;├── crear_db.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Handler
                crear<br>
                │&nbsp;&nbsp;&nbsp;└── eliminar_db.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Handler eliminar (POST)<br>
                │<br>
                ├──
                tables/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Módulo de tablas<br>
                │&nbsp;&nbsp;&nbsp;├── table_functions.php&nbsp;&nbsp;# Funciones CRUD<br>
                │&nbsp;&nbsp;&nbsp;├── view_db.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Vista de
                listado<br>
                │&nbsp;&nbsp;&nbsp;├── crear_tabla.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Handler crear<br>
                │&nbsp;&nbsp;&nbsp;├── crear_tabla_from_db.php # Form de creación<br>
                │&nbsp;&nbsp;&nbsp;├── eliminar_tabla.php&nbsp;&nbsp;&nbsp;# Handler eliminar (POST)<br>
                │&nbsp;&nbsp;&nbsp;├── modificar_tabla.php&nbsp;&nbsp;# Handler modificar<br>
                │&nbsp;&nbsp;&nbsp;└── set_table_form.php&nbsp;&nbsp;&nbsp;# Form de edición<br>
                │<br>
                └──
                records/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Módulo de registros<br>
                &nbsp;&nbsp;&nbsp;&nbsp;├── record_functions.php&nbsp;# Funciones CRUD<br>
                &nbsp;&nbsp;&nbsp;&nbsp;├── records.php&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;#
                Vista de listado<br>
                &nbsp;&nbsp;&nbsp;&nbsp;├── crear_registro.php&nbsp;&nbsp;&nbsp;# Handler crear<br>
                &nbsp;&nbsp;&nbsp;&nbsp;├── eliminar_registro.php # Handler eliminar (POST)<br>
                &nbsp;&nbsp;&nbsp;&nbsp;├── modificar_registro.php # Handler actualizar<br>
                &nbsp;&nbsp;&nbsp;&nbsp;└── modificar_registro_form.php # Form de edición
            </div>
        </div>

        <!-- Section 4: Installation -->
        <div class="doc-section">
            <h2>🚀 Instalación y Configuración</h2>

            <h3>Requisitos</h3>
            <ul>
                <li><strong>PHP</strong> 8.0 o superior</li>
                <li><strong>MySQL</strong> 5.7 o superior</li>
                <li><strong>Apache</strong> con mod_rewrite (XAMPP/WAMP/LAMP recomendado)</li>
            </ul>

            <h3>Pasos de Instalación</h3>
            <div class="doc-code">
                # 1. Clonar el repositorio<br>
                git clone https://github.com/tu-usuario/dataforge-crud-manager.git<br><br>
                # 2. Copiar a tu servidor web<br>
                cp -r dataforge /opt/lampp/htdocs/dataforge<br><br>
                # 3. Configurar credenciales<br>
                cd /opt/lampp/htdocs/dataforge<br>
                cp .env.example .env<br>
                nano .env&nbsp;&nbsp;&nbsp;&nbsp;# Editar DB_PASS con tu contraseña
            </div>

            <h3>Configuración (.env)</h3>
            <div class="doc-code">
                DB_HOST=localhost<br>
                DB_PORT=3306<br>
                DB_USER=root<br>
                DB_PASS=tu_contraseña<br>
                DB_CHARSET=utf8mb4<br><br>
                APP_ENV=development<br>
                APP_DEBUG=true
            </div>
        </div>

        <!-- Section 5: Security -->
        <div class="doc-section">
            <h2>🔒 Modelo de Seguridad</h2>

            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Capa</th>
                        <th>Protección</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Transporte</td>
                        <td>Headers de seguridad (X-Frame-Options, X-XSS-Protection, etc.)</td>
                    </tr>
                    <tr>
                        <td>Sesión</td>
                        <td>Tokens CSRF, configuración segura de sesiones</td>
                    </tr>
                    <tr>
                        <td>Entrada</td>
                        <td>Validación regex, trim(), type casting</td>
                    </tr>
                    <tr>
                        <td>Base de Datos</td>
                        <td>Prepared statements, real_escape_string para identificadores</td>
                    </tr>
                    <tr>
                        <td>Salida</td>
                        <td>htmlspecialchars() en todos los datos renderizados</td>
                    </tr>
                    <tr>
                        <td>Operaciones</td>
                        <td>Acciones destructivas requieren POST + confirmación</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 6: About -->
        <div class="doc-section">
            <h2>👥 Sobre Nosotros — Raju Technology</h2>
            <p>
                Somos un equipo apasionado de desarrolladores de software con una visión clara: crear soluciones
                tecnológicas que marquen la diferencia. Nacimos de la ambición por aplicar nuestros conocimientos
                en desarrollo web y bases de datos para resolver problemas reales y simplificar la vida de las personas.
            </p>
            <p>
                Cada proyecto que abordamos es un reflejo de nuestra dedicación y del riguroso proceso de aprendizaje
                que hemos cultivado. Estamos comprometidos con la innovación, la calidad y las mejores prácticas de la
                industria.
            </p>

            <h3>Stack Tecnológico</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
                <span class="tech-tag">PHP 8+</span>
                <span class="tech-tag">MySQL</span>
                <span class="tech-tag">HTML5</span>
                <span class="tech-tag">CSS3</span>
                <span class="tech-tag">JavaScript ES6+</span>
                <span class="tech-tag">CRUD Architecture</span>
                <span class="tech-tag">Security Best Practices</span>
                <span class="tech-tag">Responsive Design</span>
            </div>
        </div>

        <!-- Section 7: License -->
        <div class="doc-section">
            <h2>📄 Licencia</h2>
            <p>Este proyecto está licenciado bajo la <strong>Licencia MIT</strong>.</p>
            <p>Copyright ©
                <?= date('Y') ?> Raju Technology. Todos los derechos reservados.
            </p>
        </div>

    </main>

    <footer>
        <div class="footer-inner">
            <div class="footer-brand">
                <p class="footer-title">DataForge CRUD Manager</p>
                <p class="footer-subtitle">by Raju Technology</p>
            </div>
            <div class="footer-links">
                <a href="index.php">Inicio</a>
                <a href="database/databases.php">Bases de Datos</a>
                <a href="sobre_nosotros.php">Nosotros</a>
            </div>
            <p class="footer-copy">&copy;
                <?= date('Y') ?> DataForge CRUD Manager. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('navToggle');
            const menu = document.getElementById('navMenu');
            if (toggle && menu) {
                toggle.addEventListener('click', () => {
                    menu.classList.toggle('active');
                    toggle.classList.toggle('active');
                });
            }
        });
    </script>
</body>

</html>