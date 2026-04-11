<?php
/**
 * Dashboard — Main Panel
 *
 * @package DataForge
 * @module  Dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/auth_functions.php';
require_once __DIR__ . '/database/db_functions.php';

requireLogin();

$user     = getCurrentUser();
$activity = getRecentActivity((int) $user['id'], 8);

// ─── Compute Stats ─────────────────────────────────────────────────────────
$databases   = getDatabases();
$totalDbs    = count($databases);
$totalTables = 0;
$totalRows   = 0;

// Sample up to 10 databases for table/row count (avoid timeouts on large servers)
$dbSample = array_slice($databases, 0, 10);
foreach ($dbSample as $dbName) {
    try {
        $conn = getDbConnection($dbName);
        $result = $conn->query("SHOW TABLES");
        $tableCount = $result ? $result->num_rows : 0;
        $totalTables += $tableCount;

        // Exact rows counting using COUNT(*) (User request)
        $tablesRes = $conn->query("SHOW TABLES");
        if ($tablesRes) {
            while ($tRow = $tablesRes->fetch_row()) {
                $tName = $tRow[0];
                $countRes = $conn->query("SELECT COUNT(*) as exact_total FROM `$tName`");
                if ($countRes) {
                    $totalRows += (int) $countRes->fetch_assoc()['exact_total'];
                }
            }
        }
        $conn->close();
    } catch (Exception $e) {
        // Skip inaccessible databases silently
    }
}

// Activity labels and icons
function activityLabel(array $item): array {
    $icons  = ['create' => '✚', 'delete' => '✕', 'alter' => '✎', 'insert' => '⊕', 'update' => '↻'];
    $colors = ['create' => 'create', 'delete' => 'delete', 'alter' => 'alter'];
    $label  = "Acción en {$item['target_type']}: {$item['target_name']}";
    $action = strtolower($item['action']);
    $map    = [
        'create' => "Creó {$item['target_type']} \"{$item['target_name']}\"",
        'delete' => "Eliminó {$item['target_type']} \"{$item['target_name']}\"",
        'alter'  => "Modificó {$item['target_type']} \"{$item['target_name']}\"",
        'insert' => "Nuevo registro en \"{$item['target_name']}\"",
        'update' => "Actualizó registro en \"{$item['target_name']}\"",
    ];
    return [
        'label' => $map[$action] ?? $label,
        'icon'  => $icons[$action] ?? '•',
        'color' => $colors[$action] ?? 'default',
    ];
}

$pageTitle = 'Dashboard';
$baseUrl   = '';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner -->
<div class="welcome-banner glass-card" style="margin-bottom:2rem">
    <div class="welcome-banner-text">
        <h2>¡Hola, <span class="gradient-text"><?= htmlspecialchars($user['username']) ?></span>! 👋</h2>
        <p>Tu espacio de gestión de datos. Todo bajo control.</p>
    </div>
    <div class="welcome-banner-actions">
        <a href="database/databases.php" class="btn btn-primary">
            <span class="btn-icon">🗄️</span> Mis Bases de Datos
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-icon">🗄️</div>
        <div class="stat-card-value"><?= $totalDbs ?></div>
        <div class="stat-card-label">Bases de datos</div>
        <div class="stat-card-sub">En tu servidor MySQL</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">📊</div>
        <div class="stat-card-value"><?= $totalTables ?></div>
        <div class="stat-card-label">Tablas totales</div>
        <div class="stat-card-sub">Suma de todas tus BDs</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">📝</div>
        <div class="stat-card-value"><?= $totalRows > 1000 ? round($totalRows / 1000, 1) . 'K' : $totalRows ?></div>
        <div class="stat-card-label">Registros totales</div>
        <div class="stat-card-sub">Datos exactos en tus tablas</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">⚡</div>
        <div class="stat-card-value"><?= count($activity) ?></div>
        <div class="stat-card-label">Acciones recientes</div>
        <div class="stat-card-sub">Últimas operaciones</div>
    </div>
</div>

<!-- Main Dashboard Grid -->
<div class="dashboard-grid">

    <!-- Left: Databases + Activity -->
    <div>
        <!-- My Databases -->
        <section class="glass-card">
            <div class="section-header" style="margin-bottom:1rem">
                <h2><span class="gradient-text">Mis Bases de Datos</span></h2>
                <a href="database/databases.php" class="btn btn-sm btn-ghost">Ver todas →</a>
            </div>
            <?php if (empty($databases)): ?>
                <div class="empty-state">
                    No tienes bases de datos. <a href="database/databases.php">¡Crea una!</a>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($databases, 0, 6) as $db): ?>
                    <a href="tables/view_db.php?dbName=<?= urlencode($db) ?>"
                       class="db-list-item" style="text-decoration:none">
                        <div class="db-list-icon">🗄️</div>
                        <div class="db-list-info">
                            <div class="db-list-name"><?= htmlspecialchars($db) ?></div>
                            <div class="db-list-meta">MySQL · <?= htmlspecialchars(DB_HOST) ?></div>
                        </div>
                        <span class="badge badge-accent">Ver tablas</span>
                    </a>
                <?php endforeach; ?>
                <?php if ($totalDbs > 6): ?>
                    <div style="text-align:center;margin-top:1rem">
                        <a href="database/databases.php" class="btn btn-ghost btn-sm">
                            Ver las <?= $totalDbs - 6 ?> restantes →
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- Activity Log -->
        <section class="glass-card">
            <div class="section-header" style="margin-bottom:1rem">
                <h2><span class="gradient-text">Actividad Reciente</span></h2>
            </div>
            <?php if (empty($activity)): ?>
                <div class="activity-empty">
                    <p style="font-size:2rem;margin-bottom:.5rem">📋</p>
                    <p>Aún no hay actividad registrada.<br>¡Comienza creando tu primera base de datos!</p>
                </div>
            <?php else: ?>
                <div class="activity-feed">
                    <?php foreach ($activity as $item):
                        $info = activityLabel($item);
                        $time = date('d/m H:i', strtotime($item['created_at']));
                    ?>
                        <div class="activity-item">
                            <div class="activity-icon <?= $info['color'] ?>">
                                <?= $info['icon'] ?>
                            </div>
                            <div>
                                <div class="activity-label"><?= htmlspecialchars($info['label']) ?></div>
                                <?php if (!empty($item['db_context'])): ?>
                                    <div class="activity-meta">en <?= htmlspecialchars($item['db_context']) ?> · <?= $time ?></div>
                                <?php else: ?>
                                    <div class="activity-meta"><?= $time ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Right: Quick Actions + System Info -->
    <div>
        <!-- Quick Actions -->
        <section class="glass-card">
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem">
                <span class="gradient-text">Acciones Rápidas</span>
            </h2>
            <div class="quick-actions-grid">
                <a href="database/databases.php" class="quick-action-btn" id="qa-databases">
                    <span>🗄️</span><span>Nueva BD</span>
                </a>
                <a href="documentacion.php" class="quick-action-btn" id="qa-docs">
                    <span>📖</span><span>Documentación</span>
                </a>
                <a href="account/profile.php" class="quick-action-btn" id="qa-profile">
                    <span>👤</span><span>Mi Perfil</span>
                </a>
                <a href="sobre_nosotros.php" class="quick-action-btn" id="qa-about">
                    <span>🏢</span><span>Nosotros</span>
                </a>
                <a href="account/profile.php#themes" class="quick-action-btn" id="qa-theme">
                    <span>🎨</span><span>Cambiar tema</span>
                </a>
                <a href="auth/logout.php" class="quick-action-btn" id="qa-logout" style="color:var(--color-danger)">
                    <span>🚪</span><span>Salir</span>
                </a>
            </div>
        </section>

        <!-- System Info -->
        <section class="glass-card">
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem">
                <span class="gradient-text">Información del Sistema</span>
            </h2>
            <div style="display:flex;flex-direction:column;gap:.625rem">
                <?php
                $sysItems = [
                    ['label' => 'Servidor MySQL', 'value' => DB_HOST . ':' . DB_PORT],
                    ['label' => 'Usuario DB',     'value' => DB_USER],
                    ['label' => 'Versión App',    'value' => 'DataForge v' . APP_VERSION],
                    ['label' => 'Entorno',        'value' => strtoupper(APP_ENV)],
                    ['label' => 'Tu tema',        'value' => AVAILABLE_THEMES[$user['theme']]['name'] ?? 'Neutral'],
                    ['label' => 'Miembro desde',  'value' => date('d/m/Y', strtotime($user['created_at']))],
                ];
                foreach ($sysItems as $s): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;
                                padding:.5rem .75rem;border-radius:8px;background:rgba(255,255,255,.02);
                                border:1px solid var(--glass-border)">
                        <span style="font-size:.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em">
                            <?= htmlspecialchars($s['label']) ?>
                        </span>
                        <span style="font-size:.85rem;color:var(--text-primary);font-weight:600">
                            <?= htmlspecialchars($s['value']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
