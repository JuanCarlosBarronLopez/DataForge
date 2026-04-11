<?php
/**
 * Edit Record Form Page
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
$id = (int) ($_GET['id'] ?? 0);

$recordToModify = getRecordById($dbName, $tableName, $id);

if (!$recordToModify) {
    require_once __DIR__ . '/../includes/flash.php';
    setFlash('error', 'Registro no encontrado.');
    header("Location: records.php?dbName=" . urlencode($dbName) . "&tableName=" . urlencode($tableName));
    exit();
}

$pageTitle = 'Editar Registro #' . $id;
$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="crud-section glass-card">
    <div class="section-header">
        <div>
            <h2>Editar Registro <span class="gradient-text">#<?= $id ?></span></h2>
            <p class="hint-text">Tabla: <strong><?= htmlspecialchars($tableName) ?></strong> — Base de datos:
                <strong><?= htmlspecialchars($dbName) ?></strong></p>
            <a href="records.php?dbName=<?= urlencode($dbName) ?>&tableName=<?= urlencode($tableName) ?>"
                class="breadcrumb-link">← Volver a Registros</a>
        </div>
    </div>

    <form class="crud-form" action="modificar_registro.php" method="post">
        <?php csrfField(); ?>
        <input type="hidden" name="dbName" value="<?= htmlspecialchars($dbName) ?>">
        <input type="hidden" name="tableName" value="<?= htmlspecialchars($tableName) ?>">
        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

        <?php foreach ($recordToModify as $columnName => $value): ?>
            <div class="form-group">
                <label for="<?= htmlspecialchars($columnName) ?>">
                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $columnName))) ?>
                </label>
                <?php if ($columnName === 'id'): ?>
                    <input type="text" id="<?= htmlspecialchars($columnName) ?>" value="<?= htmlspecialchars($value) ?>"
                        readonly disabled class="input-readonly">
                <?php else: ?>
                    <input type="text" id="<?= htmlspecialchars($columnName) ?>" name="<?= htmlspecialchars($columnName) ?>"
                        value="<?= htmlspecialchars($value) ?>" required>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="records.php?dbName=<?= urlencode($dbName) ?>&tableName=<?= urlencode($tableName) ?>"
                class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>