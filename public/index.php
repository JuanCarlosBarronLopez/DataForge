<?php

declare(strict_types=1);

use DataForge\Core\Application;

$autoload = dirname(__DIR__).'/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>DataForge</title></head>'
        .'<body style="font-family:system-ui,sans-serif;max-width:40rem;margin:4rem auto;line-height:1.6">'
        .'<h1>Faltan las dependencias</h1>'
        .'<p>DataForge usa Composer para cargar sus dependencias. Ejecuta:</p>'
        .'<pre>composer install</pre>'
        .'</body></html>';

    exit;
}

require $autoload;

(new Application(dirname(__DIR__)))->run();
