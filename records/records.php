<?php
/**
 * Records View Page — Lists all records for a table.
 *
 * @package  DataForge
 * @module   Records
 */

require_once __DIR__ . '/../tables/table_functions.php';
require_once __DIR__ . '/record_functions.php';
require_once __DIR__ . '/../auth/auth_functions.php';
requireLogin();

$dbName = $_GET['dbName'] ?? '';
$tableName = $_GET['tableName'] ?? '';

// Pagination variables
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 50; // Records per page
$offset = ($page - 1) * $limit;

// Total records estimate
$totalRecords = 0;
if (!empty($dbName) && !empty($tableName)) {
    try {
        require_once __DIR__ . '/../database/db_functions.php';
        $conn = getDbConnection($dbName);
        $stmt = $conn->prepare("SELECT COUNT(*) as ct FROM `" . $conn->real_escape_string($tableName) . "`");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            $totalRecords = $res->fetch_assoc()['ct'] ?? 0;
            $stmt->close();
        }
        $conn->close();
    } catch (Exception $e) {}
}

$totalPages = ceil($totalRecords / $limit);

$records = getRecords($dbName, $tableName, $limit, $offset);
$tableColumns = getTableColumns($dbName, $tableName);

$pageTitle = 'Registros — ' . ($tableName ?: 'Sin tabla');
$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="crud-section glass-card">
    <div class="section-header">
        <div>
            <h2>Registros en <span class="gradient-text"><?= htmlspecialchars($tableName) ?></span></h2>
            <p class="hint-text">Base de datos: <strong><?= htmlspecialchars($dbName) ?></strong></p>
            <a href="../tables/view_db.php?dbName=<?= urlencode($dbName) ?>" class="breadcrumb-link">← Volver a
                Tablas</a>
        </div>
        <button class="btn btn-primary" id="addRecordBtn">
            <span class="btn-icon">+</span> Nuevo Registro
        </button>
    </div>

    <form class="crud-form hidden" id="addRecordForm" action="crear_registro.php" method="post">
        <?php csrfField(); ?>
        <h3>Nuevo Registro</h3>
        <input type="hidden" name="dbName" value="<?= htmlspecialchars($dbName) ?>">
        <input type="hidden" name="tableName" value="<?= htmlspecialchars($tableName) ?>">

        <?php if (!empty($tableColumns)): ?>
            <?php foreach ($tableColumns as $col): ?>
                <?php if ($col === 'id')
                    continue; ?>
                <div class="form-group">
                    <label
                        for="<?= htmlspecialchars($col) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $col))) ?></label>
                    <input type="text" id="<?= htmlspecialchars($col) ?>" name="<?= htmlspecialchars($col) ?>" required>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="hint-text">No se pudieron cargar las columnas de la tabla.</p>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <button type="button" class="btn btn-ghost cancel">Cancelar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <?php if (!empty($records)): ?>
                        <?php foreach (array_keys($records[0]) as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    <?php elseif (!empty($tableColumns)): ?>
                        <?php foreach ($tableColumns as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="<?= max(count($tableColumns) + 1, 2) ?>">
                            <div class="empty-state">No hay registros. ¡Añade uno!</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <?php foreach ($record as $value): ?>
                                <td><?= htmlspecialchars($value ?? '') ?></td>
                            <?php endforeach; ?>
                            <td class="actions-cell">
                                <a class="btn btn-sm btn-warning"
                                    href="modificar_registro_form.php?dbName=<?= urlencode($dbName) ?>&tableName=<?= urlencode($tableName) ?>&id=<?= urlencode($record['id'] ?? '') ?>">Editar</a>
                                <form method="POST" action="eliminar_registro.php" class="inline-form"
                                    onsubmit="return confirm('¿Eliminar este registro? Esta acción es irreversible.')">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="dbName" value="<?= htmlspecialchars($dbName) ?>">
                                    <input type="hidden" name="tableName" value="<?= htmlspecialchars($tableName) ?>">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($record['id'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalRecords > 0): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem">
            <p class="hint-text" style="margin:0;">
                Mostrando <?= count($records) ?> de <?= $totalRecords ?> registro<?= $totalRecords !== 1 ? 's' : '' ?>
            </p>
            
            <?php if ($totalPages > 1): ?>
                <div style="display:flex;gap:0.5rem">
                    <?php if ($page > 1): ?>
                        <a href="?dbName=<?= urlencode($dbName) ?>&tableName=<?= urlencode($tableName) ?>&page=<?= $page - 1 ?>" class="btn btn-sm btn-ghost">← Anterior</a>
                    <?php endif; ?>
                    
                    <span class="btn btn-sm" style="background:var(--bg-input);padding:4px 12px;border:1px solid var(--border-color);cursor:default">
                        Página <?= $page ?> de <?= $totalPages ?>
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?dbName=<?= urlencode($dbName) ?>&tableName=<?= urlencode($tableName) ?>&page=<?= $page + 1 ?>" class="btn btn-sm btn-ghost">Siguiente →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addBtn = document.getElementById('addRecordBtn');
        const form = document.getElementById('addRecordForm');
        const cancelBtn = form.querySelector('.cancel');

        addBtn.addEventListener('click', () => {
            form.classList.remove('hidden');
            addBtn.classList.add('hidden');
            const firstInput = form.querySelector('input[type="text"]');
            if (firstInput) firstInput.focus();
        });

        cancelBtn.addEventListener('click', () => {
            form.classList.add('hidden');
            form.reset();
            addBtn.classList.remove('hidden');
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>