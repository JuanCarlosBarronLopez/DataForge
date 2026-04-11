<?php
/**
 * Edit Table Structure Page — Modify columns of an existing table.
 *
 * @package  DataForge
 * @module   Tables
 */

require_once __DIR__ . '/table_functions.php';
require_once __DIR__ . '/../auth/auth_functions.php';

requireLogin();

$dbName = $_GET['dbName'] ?? '';
$tableName = $_GET['tableName'] ?? '';

$currentColumns = getTableColumnsDefinition($dbName, $tableName);

if (empty($currentColumns)) {
    require_once __DIR__ . '/../includes/flash.php';
    setFlash('error', 'No se pudo cargar la estructura de la tabla.');
    header("Location: view_db.php?dbName=" . urlencode($dbName));
    exit();
}

$pageTitle = 'Editar ' . $tableName;
$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="crud-section glass-card">
    <div class="section-header">
        <div>
            <h2>Editar Tabla: <span class="gradient-text"><?= htmlspecialchars($tableName) ?></span></h2>
            <p class="hint-text">Base de datos: <strong><?= htmlspecialchars($dbName) ?></strong></p>
            <a href="view_db.php?dbName=<?= urlencode($dbName) ?>" class="breadcrumb-link">← Volver a Tablas</a>
        </div>
    </div>

    <form class="crud-form" action="modificar_tabla.php" method="post">
        <?php csrfField(); ?>
        <input type="hidden" name="dbName" value="<?= htmlspecialchars($dbName) ?>">
        <input type="hidden" name="tableName" value="<?= htmlspecialchars($tableName) ?>">

        <h4>Columnas Existentes y Nuevas</h4>
        <div id="columnsContainer"></div>

        <button type="button" class="btn btn-ghost" id="addColumnBtn">+ Añadir Nueva Columna</button>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="view_db.php?dbName=<?= urlencode($dbName) ?>" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const columnsContainer = document.getElementById('columnsContainer');
        const addColumnBtn = document.getElementById('addColumnBtn');
        let columnIndex = 0;

        const currentColumns = <?= json_encode($currentColumns) ?>;
        const dataTypes = ['INT', 'FLOAT', 'DECIMAL', 'VARCHAR', 'TEXT', 'DATE', 'DATETIME', 'BOOLEAN'];

        function renderTypeOptions(selectedType = '') {
            return dataTypes.map(type => {
                const sel = selectedType.toUpperCase().includes(type) ? 'selected' : '';
                return `<option value="${type}" ${sel}>${type}</option>`;
            }).join('');
        }

        function addColumnField(colData = null, isNew = false) {
            const div = document.createElement('div');
            div.classList.add('column-field');
            if (isNew) div.classList.add('new-column');

            const name = colData ? colData.Field : '';
            const type = colData ? (colData.Type.split('(')[0] || '').toUpperCase() : '';
            const length = (colData && type === 'VARCHAR' && colData.Type.includes('('))
                ? parseInt(colData.Type.split('(')[1]) : '';
            const isPK = colData && colData.Key === 'PRI';
            const isAI = colData && colData.Extra.includes('auto_increment');
            const ro = isPK || isAI;

            div.innerHTML = `
            <div class="form-group">
                <label>Columna</label>
                <input type="text" name="columns[${columnIndex}][name]" value="${name}" ${ro ? 'readonly' : ''} required>
                ${ro ? '<span class="hint-text">(PK / Auto Incremento)</span>' : ''}
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="columns[${columnIndex}][type]" class="column-type-select" required>
                    <option value="">Tipo</option>
                    ${renderTypeOptions(type)}
                </select>
            </div>
            <div class="form-group varchar-length-group ${type === 'VARCHAR' ? '' : 'hidden'}">
                <label>Longitud</label>
                <input type="number" name="columns[${columnIndex}][length]" value="${length}" min="1" max="65535" ${type === 'VARCHAR' ? 'required' : ''}>
            </div>
            <input type="hidden" name="columns[${columnIndex}][is_new]" value="${isNew ? 'true' : 'false'}">
            ${!isPK 
                ? (isNew 
                    ? '<button type="button" class="btn btn-sm btn-danger remove-column-btn" title="Quitar columna">✕</button>' 
                    : `<button type="button" class="btn btn-sm btn-danger drop-column-btn" data-col="${name}" title="Eliminar columna de la DB">🗑️</button>`)
                : '<span class="hint-text">(No eliminable)</span>'}
        `;

            columnsContainer.appendChild(div);

            const sel = div.querySelector('.column-type-select');
            const lGrp = div.querySelector('.varchar-length-group');
            const lInp = lGrp.querySelector('input');

            sel.addEventListener('change', () => {
                if (sel.value === 'VARCHAR') {
                    lGrp.classList.remove('hidden');
                    lInp.required = true;
                } else {
                    lGrp.classList.add('hidden');
                    lInp.required = false;
                    lInp.value = '';
                }
            });

            const rmBtn = div.querySelector('.remove-column-btn');
            if (rmBtn) rmBtn.addEventListener('click', () => div.remove());

            const dropBtn = div.querySelector('.drop-column-btn');
            if (dropBtn) {
                dropBtn.addEventListener('click', () => {
                    const colToDelete = dropBtn.getAttribute('data-col');
                    if (confirm('¿Estás seguro de que deseas ELIMINAR la columna "' + colToDelete + '" y todos sus datos? Esta acción no se puede deshacer.')) {
                        // Create dynamic form to drop column
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'eliminar_columna.php';
                        
                        const iDb = document.createElement('input'); iDb.type = 'hidden'; iDb.name = 'dbName'; iDb.value = currentColumns[0] ? document.querySelector('input[name=dbName]').value : '';
                        const iTb = document.createElement('input'); iTb.type = 'hidden'; iTb.name = 'tableName'; iTb.value = currentColumns[0] ? document.querySelector('input[name=tableName]').value : '';
                        const iCol = document.createElement('input'); iCol.type = 'hidden'; iCol.name = 'colName'; iCol.value = colToDelete;
                        
                        // Grab CSRF from current page
                        const csrf = document.querySelector('input[name=csrf_token]').cloneNode();
                        
                        form.appendChild(iDb); form.appendChild(iTb); form.appendChild(iCol); form.appendChild(csrf);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }

            columnIndex++;
        }

        currentColumns.forEach(col => addColumnField(col, false));
        addColumnBtn.addEventListener('click', () => addColumnField(null, true));
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>