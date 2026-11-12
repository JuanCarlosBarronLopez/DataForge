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

    /**
     * Canal de eventos en vivo (Server-Sent Events).
     *
     * Mantiene al dia las metricas y la bitacora del panel sin recargar.
     * Los nodos se crean con textContent: ningun dato del servidor se
     * interpreta como HTML.
     */
    function initLiveFeed() {
        var root = document.querySelector('[data-live-feed]');
        if (!root || typeof EventSource === 'undefined') return;

        var status = root.querySelector('[data-live-status]');
        var activity = root.querySelector('[data-live-activity]');
        var empty = root.querySelector('[data-live-empty]');
        var failures = 0;

        function setStatus(text, tone) {
            if (!status) return;
            status.textContent = text;
            status.className = 'badge' + (tone ? ' badge--' + tone : '');
        }

        function formatDate(value) {
            if (!value) return 'sin registro';
            var parsed = new Date(String(value).replace(' ', 'T'));
            if (isNaN(parsed.getTime())) return 'sin registro';
            var day = String(parsed.getDate()).padStart(2, '0');
            var month = String(parsed.getMonth() + 1).padStart(2, '0');
            var hours = String(parsed.getHours()).padStart(2, '0');
            var minutes = String(parsed.getMinutes()).padStart(2, '0');
            return day + '/' + month + ' ' + hours + ':' + minutes;
        }

        function buildItem(entry) {
            var item = document.createElement('li');
            item.className = 'feed__item';

            var mark = document.createElement('span');
            mark.className = 'feed__mark feed__mark--' + entry.action;
            mark.setAttribute('aria-hidden', 'true');
            item.appendChild(mark);

            var body = document.createElement('span');

            var badge = document.createElement('span');
            badge.className = 'badge';
            badge.textContent = entry.action;
            body.appendChild(badge);

            var name = document.createElement('span');
            name.className = 'mono';
            name.textContent = entry.target_name || '';
            body.appendChild(name);

            if (entry.db_context) {
                var preposition = document.createElement('span');
                preposition.className = 'muted';
                preposition.textContent = 'en';
                body.appendChild(preposition);

                var context = document.createElement('span');
                context.className = 'mono';
                context.textContent = entry.db_context;
                body.appendChild(context);
            }

            var meta = document.createElement('span');
            meta.className = 'feed__meta';
            meta.textContent = formatDate(entry.created_at);
            body.appendChild(meta);

            item.appendChild(body);
            return item;
        }

        function renderMetrics(metrics) {
            if (!metrics) return;
            Object.keys(metrics).forEach(function (key) {
                var node = root.querySelector('[data-live-metric="' + key + '"]');
                if (node) node.textContent = String(metrics[key]);
            });
        }

        var source = new EventSource('/events');

        source.addEventListener('open', function () {
            failures = 0;
            setStatus('En vivo', 'ok');
        });

        source.addEventListener('snapshot', function (event) {
            var payload = JSON.parse(event.data);
            renderMetrics(payload.metrics);
            if (activity && payload.entries && payload.entries.length) {
                activity.textContent = '';
                payload.entries.forEach(function (entry) {
                    activity.appendChild(buildItem(entry));
                });
                activity.hidden = false;
                if (empty) empty.hidden = true;
            }
            setStatus('En vivo', 'ok');
        });

        source.addEventListener('activity', function (event) {
            var payload = JSON.parse(event.data);
            renderMetrics(payload.metrics);
            if (!activity || !payload.entries) return;
            payload.entries.slice().reverse().forEach(function (entry) {
                activity.insertBefore(buildItem(entry), activity.firstChild);
            });
            activity.hidden = false;
            if (empty) empty.hidden = true;
        });

        source.addEventListener('close', function () {
            setStatus('Reconectando', 'warn');
        });

        source.addEventListener('error', function () {
            failures += 1;
            setStatus('Sin conexion', 'danger');

            // Tras varios fallos la sesion ha caducado: se vuelve al acceso.
            if (failures > 5 && source.readyState === EventSource.CLOSED) {
                window.location.href = '/login';
            }
        });
    }

    initNavigation();
    initConfirmations();
    initColorMode();
    initForms();
    initColumnBuilder();
    initLiveFeed();
})();
