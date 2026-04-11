<?php
/**
 * Tables View Page — Lists all tables for a selected database.
 *
 * @package  DataForge
 * @module   Tables
 */

require_once __DIR__ . '/table_functions.php';
require_once __DIR__ . '/../auth/auth_functions.php';
requireLogin();

$currentDbName = $_GET['dbName'] ?? '';
$pageTitle = 'Tablas — ' . ($currentDbName ?: 'Sin DB');
$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="crud-section glass-card">
    <div class="section-header">
        <div>
            <h2>Tablas en <span class="gradient-text"><?= htmlspecialchars($currentDbName) ?></span></h2>
            <a href="../database/databases.php" class="breadcrumb-link">← Volver a Bases de Datos</a>
        </div>
        <button class="btn btn-primary" id="createTableBtn">
            <span class="btn-icon">+</span> Nueva Tabla
        </button>
    </div>

    <form class="crud-form hidden" id="createTableForm" action="crear_tabla.php" method="post">
        <?php csrfField(); ?>
        <h3>Crear Nueva Tabla</h3>
        <input type="hidden" name="dbName" value="<?= htmlspecialchars($currentDbName) ?>">

        <div class="form-group">
            <label for="tableName">Nombre de la Tabla</label>
            <input type="text" id="tableName" name="tableName" placeholder="mi_tabla" pattern="[a-zA-Z0-9_]+" required>
        </div>

        <h4>Definición de Columnas</h4>
        <p class="hint-text">La columna <code>id INT AUTO_INCREMENT PRIMARY KEY</code> se añade automáticamente.</p>
        <div id="columnsContainer"></div>

        <button type="button" class="btn btn-ghost" id="addColumnBtn">+ Añadir Columna</button>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Crear Tabla</button>
            <button type="button" class="btn btn-ghost cancel">Cancelar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Base de Datos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php displayTablesForDb($currentDbName); ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const createTableBtn = document.getElementById('createTableBtn');
        const form = document.getElementById('createTableForm');
        const cancelBtn = form.querySelector('.cancel');
        const addColumnBtn = document.getElementById('addColumnBtn');
        const columnsContainer = document.getElementById('columnsContainer');
        let columnIndex = 0;

        function addColumnField() {
            const div = document.createElement('div');
            div.classList.add('column-field');
            div.innerHTML = `
            <div class="form-group">
                <label>Nombre de Columna</label>
                <input type="text" name="columns[${columnIndex}][name]" placeholder="nombre_columna" pattern="[a-zA-Z0-9_]+" required>
            </div>
            <div class="form-group">
                <label>Tipo de Dato</label>
                <select name="columns[${columnIndex}][type]" class="column-type-select" required>
                    <option value="">Selecciona tipo</option>
                    <option value="INT">INT</option>
                    <option value="FLOAT">FLOAT</option>
                    <option value="DECIMAL">DECIMAL</option>
                    <option value="VARCHAR">VARCHAR</option>
                    <option value="TEXT">TEXT</option>
                    <option value="DATE">DATE</option>
                    <option value="DATETIME">DATETIME</option>
                    <option value="BOOLEAN">BOOLEAN</option>
                </select>
            </div>
            <div class="form-group varchar-length-group hidden">
                <label>Longitud</label>
                <input type="number" name="columns[${columnIndex}][length]" placeholder="255" min="1" max="65535">
            </div>
            <button type="button" class="btn btn-sm btn-danger remove-column-btn">✕</button>
        `;

            columnsContainer.appendChild(div);

            const typeSelect = div.querySelector('.column-type-select');
            const lengthGroup = div.querySelector('.varchar-length-group');
            const lengthInput = lengthGroup.querySelector('input');

            typeSelect.addEventListener('change', () => {
                if (typeSelect.value === 'VARCHAR') {
                    lengthGroup.classList.remove('hidden');
                    lengthInput.setAttribute('required', 'required');
                } else {
                    lengthGroup.classList.add('hidden');
                    lengthInput.removeAttribute('required');
                    lengthInput.value = '';
                }
            });

            div.querySelector('.remove-column-btn').addEventListener('click', () => div.remove());
            columnIndex++;
        }

        createTableBtn.addEventListener('click', () => {
            form.classList.remove('hidden');
            createTableBtn.classList.add('hidden');
        });

        cancelBtn.addEventListener('click', () => {
            form.classList.add('hidden');
            form.reset();
            columnsContainer.innerHTML = '';
            columnIndex = 0;
            createTableBtn.classList.remove('hidden');
        });

        addColumnBtn.addEventListener('click', addColumnField);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>