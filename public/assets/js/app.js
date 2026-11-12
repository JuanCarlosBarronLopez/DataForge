/**
 * DataForge - comportamiento de la interfaz.
 *
 * Sin dependencias externas: toda la interaccion es local y no hay peticiones
 * a terceros (la CSP no necesita excepciones de origen).
 */

(function () {
    'use strict';

    /**
     * Menu de navegacion movil: abre, cierra con Escape y devuelve el foco.
     */
    function initNavigation() {
        var toggle = document.getElementById('navToggle');
        var menu = document.getElementById('appNav');
        if (!toggle || !menu) return;

        function close(returnFocus) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Abrir menu de navegacion');
            menu.classList.remove('is-open');
            if (returnFocus) toggle.focus();
        }

        function open() {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Cerrar menu de navegacion');
            menu.classList.add('is-open');
        }

        toggle.addEventListener('click', function () {
            if (toggle.getAttribute('aria-expanded') === 'true') close(false);
            else open();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                close(true);
            }
        });

        document.addEventListener('click', function (event) {
            if (toggle.getAttribute('aria-expanded') !== 'true') return;
            if (menu.contains(event.target) || toggle.contains(event.target)) return;
            close(false);
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) close(false);
        });
    }

    /**
     * Confirmacion en formulario para acciones destructivas.
     */
    function initConfirmations() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var message = form.getAttribute('data-confirm');
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    /**
     * Cambio de modo de color: aplica el nuevo modo antes de enviar, para que
     * el cambio se vea sin parpadeo en la siguiente carga.
     */
    function initColorMode() {
        document.querySelectorAll('[data-mode-toggle]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var current = document.documentElement.getAttribute('data-mode');
                var next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-mode', next);
            });
        });
    }

    /**
     * Los controles de opcion se activan con teclado: Enter sobre un radio
     * enviado por formulario navega; en la consola usamos submit explicito.
     */
    function initForms() {
        document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
            form.addEventListener('change', function () {
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            });
        });
    }

    /**
     * Constructor de columnas: clona la plantilla y renumera los indices.
     */
    function initColumnBuilder() {
        var table = document.getElementById('columnasTabla');
        var template = document.getElementById('plantillaColumna');
        var add = document.querySelector('[data-add-column]');
        var warning = document.getElementById('columnasAviso');

        if (!table || !template || !add) return;

        var body = table.tBodies[0];

        function renumber() {
            Array.prototype.forEach.call(body.rows, function (row, index) {
                Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (control) {
                    control.name = control.name.replace(/columns\[\d+\]/, 'columns[' + index + ']');
                });
            });

            if (warning) warning.hidden = body.rows.length > 0;
        }

        function addRow() {
            var fragment = template.content.cloneNode(true);
            body.appendChild(fragment);
            renumber();
            var last = body.rows[body.rows.length - 1];
            var first = last.querySelector('input');
            if (first) first.focus();
        }

        body.addEventListener('click', function (event) {
            var button = event.target.closest('[data-remove-column]');
            if (!button) return;
            var row = button.closest('tr');
            if (row) row.remove();
            renumber();
        });

        add.addEventListener('click', addRow);

        if (body.rows.length === 0) addRow();
    }

    initNavigation();
    initConfirmations();
    initColorMode();
    initForms();
    initColumnBuilder();
})();
