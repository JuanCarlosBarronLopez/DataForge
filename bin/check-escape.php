<?php

declare(strict_types=1);

/**
 * ============================================
 * DataForge - Comprobacion de escape en plantillas
 * ============================================
 * Exige que todo dato que se imprime pase por e() o attr().
 *
 * El escape puede quedar en manos de quien escribe la vista y basta con un
 * `<?= $dato ?>` para introducir una XSS. Esta comprobacion lo convierte en
 * un fallo de integracion continua en lugar de una revision manual.
 *
 * Salidas permitidas:
 *   - e(...) y attr(...)          el escape
 *   - $csrf->field(...)           ya escapa internamente
 *   - $view->partial(...)        el parcial escapa por su cuenta
 *   - Url::...(...)               el generador de rutas, con identificadores validados
 *   - format_datetime(...)        devuelve una fecha con formato fijo
 *   - format_bytes(...)           devuelve un numero con unidad
 *   - theme_label(...)            devuelve el nombre de un tema de la lista blanca
 *   - Column::...(...)            devuelve el tipo de control o la longitud de una columna
 *   - count(...)                  devuelve un entero
 *   - n(...) y e_url(...)         escapes especializados
 *   - una conversion a numero     no puede introducir marcado
 *   - un condicional de literales 'checked', 'required', ...
 *
 * Uso:  php bin/check-escape.php
 */

$root = dirname(__DIR__);
$path = $root . '/templates';

if (!is_dir($path)) {
    fwrite(STDERR, "No se encontro el directorio de plantillas.\n");
    exit(1);
}

/** Funciones cuyo resultado ya es seguro o no contiene datos externos. */
const SAFE_CALLS = [
    'e',
    'attr',
    'e_url',
    'n',
    'count',
    'format_datetime',
    'format_bytes',
    'theme_label',
    'number_format',
];

/** Metodos cuyo resultado ya viene escapado o no contiene datos externos. */
const SAFE_METHODS = [
    'field',
    'partial',
    'inputType',
    'length',
    'isBoolean',
    'url',
    'home',
    'docs',
    'about',
    'dashboard',
    'login',
    'register',
    'logout',
    'onboarding',
    'profile',
    'profileDelete',
    'profilePassword',
    'profileTheme',
    'profileMode',
    'databases',
    'databaseCreate',
    'database',
    'databaseDelete',
    'databaseExport',
    'tableCreate',
    'tableCreateForm',
    'table',
    'tableStructure',
    'tableStructureSave',
    'tableDelete',
    'columnDelete',
    'recordCreate',
    'recordCreateForm',
    'record',
    'recordUpdate',
    'recordDelete',
    'tableExport',
    'query',
    'activity',
    'avatar',
];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

$problems = [];
$checked  = 0;

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $contents = (string) file_get_contents($file->getPathname());
    $lines    = explode("\n", $contents);

    foreach ($lines as $number => $line) {
        if (!str_contains($line, '<?=')) {
            continue;
        }

        $checked++;

        if (!preg_match('/<\?=(.*?)\?>/s', $line, $matches)) {
            $problems[] = sprintf(
                '%s:%d expresion abierta que hay que revisar a mano',
                str_replace($root . '/', '', $file->getPathname()),
                $number + 1,
            );

            continue;
        }

        $expression = trim($matches[1]);

        if (is_safe($expression)) {
            continue;
        }

        $problems[] = sprintf(
            '%s:%d salida sin escapar: %s',
            str_replace($root . '/', '', $file->getPathname()),
            $number + 1,
            $expression,
        );
    }
}

/**
 * Decide si una expresion solo puede producir una salida segura.
 */
function is_safe(string $expression): bool
{
    $expression = trim($expression);

    if ($expression === '') {
        return false;
    }

    // Una conversion a numero no puede introducir marcado.
    if (preg_match('/^\((?:int|float|double)\)\s*.+$/', $expression) === 1) {
        return true;
    }

    // Un condicional cuyas dos ramas son literales: 'checked', 'required', ...
    if (preg_match('/^.+?\?\s*\'[^\']*\'\s*:\s*\'[^\']*\'$/', $expression) === 1) {
        return true;
    }

    // Llamadas a funciones permitidas.
    if (preg_match('/^([A-Za-z_][A-Za-z0-9_\\\\]*)\(/', $expression, $matches) === 1) {
        $position = strrpos($matches[1], '\\');
        $name     = $position === false ? $matches[1] : substr($matches[1], $position + 1);

        return in_array($name, SAFE_CALLS, true);
    }

    // Metodos permitidos: Url::algo(), $csrf->field(), $view->partial().
    if (preg_match('/^(?:[A-Za-z_][A-Za-z0-9_\\\\]*::|\$[A-Za-z_][A-Za-z0-9_]*->)([A-Za-z_][A-Za-z0-9_]*)\(/', $expression, $matches) === 1) {
        return in_array($matches[1], SAFE_METHODS, true);
    }

    // Un literal de cadena.
    if (preg_match('/^\'(?:[^\'\\\\]|\\\\.)*\'$/', $expression) === 1) {
        return true;
    }

    // El contenido ya renderizado de la pagina: lo produce el motor de
    // plantillas, que ya escapa lo que necesita.
    if ($expression === '$content') {
        return true;
    }

    return false;
}

printf("Plantillas revisadas: %d expresiones\n", $checked);

if ($problems === []) {
    echo "OK: todas las salidas pasan por e() o por una funcion segura.\n";
    exit(0);
}

echo "Salidas que hay que revisar:\n";

foreach ($problems as $problem) {
    echo '  - ' . $problem . "\n";
}

exit(1);
