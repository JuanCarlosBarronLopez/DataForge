</main>

<footer>
    <div class="footer-inner">
        <div class="footer-brand">
            <p class="footer-title">
                <?= htmlspecialchars(APP_NAME) ?>
            </p>
            <p class="footer-subtitle">by Raju Technology</p>
        </div>
        <div class="footer-links">
            <a href="<?= $baseUrl ?>index.php">Inicio</a>
            <a href="<?= $baseUrl ?>database/databases.php">Bases de Datos</a>
            <a href="<?= $baseUrl ?>sobre_nosotros.php">Nosotros</a>
            <a href="<?= $baseUrl ?>documentacion.php">Documentación</a>
        </div>
        <p class="footer-copy">&copy;
            <?= date('Y') ?>
            <?= htmlspecialchars(APP_NAME) ?>. Todos los derechos reservados.
        </p>
    </div>
</footer>

<script>
    // Mobile Navigation Toggle
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