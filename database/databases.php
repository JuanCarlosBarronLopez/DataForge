<?php
/**
 * Database Listing & Creation Page
 *
 * Displays all user databases with options to create, view tables, or delete.
 *
 * @package  DataForge
 * @module   Database
 */

require_once __DIR__ . '/db_functions.php';
require_once __DIR__ . '/../auth/auth_functions.php';
requireLogin();


$pageTitle = 'Bases de Datos';
$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="crud-section glass-card">
    <div class="section-header">
        <h2><span class="gradient-text">Gestión de Bases de Datos</span></h2>
        <button class="btn btn-primary" id="createDbBtn">
            <span class="btn-icon">+</span> Nueva Base de Datos
        </button>
    </div>

    <form class="crud-form hidden" id="createDbForm" action="crear_db.php" method="post">
        <?php csrfField(); ?>
        <h3>Crear Base de Datos</h3>
        <div class="form-group">
            <label for="dbName">Nombre de la Base de Datos</label>
            <input type="text" id="dbName" name="dbName" placeholder="mi_base_datos" pattern="[a-zA-Z0-9_]+"
                title="Solo letras, números y guiones bajos" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Crear</button>
            <button type="button" class="btn btn-ghost cancel">Cancelar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Motor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php displayDatabases(); ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const createBtn = document.getElementById('createDbBtn');
        const form = document.getElementById('createDbForm');
        const cancelBtn = form.querySelector('.cancel');

        createBtn.addEventListener('click', () => {
            form.classList.remove('hidden');
            form.querySelector('input[type="text"]').focus();
            createBtn.classList.add('hidden');
        });

        cancelBtn.addEventListener('click', () => {
            form.classList.add('hidden');
            form.reset();
            createBtn.classList.remove('hidden');
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>