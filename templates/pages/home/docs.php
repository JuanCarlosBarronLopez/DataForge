<?php
/**
 * Documentacion del proyecto.
 *
 * @var string $pageTitle
 */

?>
<header class="page-head">
    <div>
        <h1 class="page-head__title">Documentacion</h1>
        <p class="page-head__lead">
            Arquitectura real, modelo de seguridad, pruebas y limitaciones conocidas del proyecto.
        </p>
    </div>
</header>

<div class="stack">
    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Arquitectura</h2></div>
        <div class="panel__body stack">
            <p>
                DataForge es una aplicacion PHP nativa organizada en capas: un front controller, una cadena de
                middleware, controladores, servicios con las reglas de negocio, repositorios sobre PDO y un motor
                de plantillas propio. No usa un framework y no implementa MVC, ADR ni ningun otro patron de
                plantilla: es una aplicacion por capas.
            </p>

            <ol class="stack stack--tight" style="padding-left:1.25rem">
                <li>
                    <strong>HTTP</strong>: un unico punto de entrada (<code class="mono">public/index.php</code>) que
                    construye la peticion, la pasa por la cadena de middleware y la enruta hacia un controlador.
                </li>
                <li>
                    <strong>Dominio</strong>: servicios con las reglas de negocio (autenticacion, bases de datos,
                    tablas, registros, consultas, exportacion, alimentacion en vivo).
                </li>
                <li>
                    <strong>Persistencia</strong>: repositorios sobre PDO con consultas parametrizadas, mas una
                    fabrica de conexiones que distingue la base de datos de sistema de las bases del usuario.
                </li>
            </ol>

            <div>
                <h3 class="field__label" style="margin-bottom:.5rem">Peticion tipica</h3>
                <pre class="code-block">public/index.php
  -> Application::handle()
     -> cabeceras de seguridad (CSP con nonce)
     -> manejo de errores
     -> sesion
     -> rate limit
     -> Router::dispatch()
        -> middleware de la ruta (autenticacion, CSRF)
        -> Controlador
           -> Servicio -> Repositorio -> PDO
        -> View::render()</pre>
            </div>

            <p class="muted">
                El codigo fuente de estas capas esta en <code class="mono">src/</code> con espacio de nombres
                PSR-4 bajo <code class="mono">DataForge\</code>. Las plantillas viven en
                <code class="mono">templates/</code> y las rutas en <code class="mono">config/routes.php</code>.
            </p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Las siete capas de seguridad</h2></div>
        <div class="panel__body">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Capa</th>
                            <th scope="col">Implementacion</th>
                            <th scope="col">Archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CSRF por accion</td>
                            <td>Token por ruta, <code class="mono">hash_equals</code>, consumido tras cada intento</td>
                            <td class="mono">src/Security/Csrf.php</td>
                        </tr>
                        <tr>
                            <td>Consultas</td>
                            <td>PDO con <code class="mono">ATTR_EMULATE_PREPARES</code> en false</td>
                            <td class="mono">src/Database/ConnectionFactory.php</td>
                        </tr>
                        <tr>
                            <td>Identificadores</td>
                            <td>Lista blanca <code class="mono">[A-Za-z0-9_]{1,64}</code> antes de interpolar</td>
                            <td class="mono">src/Support/Identifier.php</td>
                        </tr>
                        <tr>
                            <td>Contrasenas</td>
                            <td>Argon2id con respaldo bcrypt cost 12 y recalculo en el proximo acceso</td>
                            <td class="mono">src/Security/PasswordHasher.php</td>
                        </tr>
                        <tr>
                            <td>Cabeceras</td>
                            <td>CSP con nonce por peticion, <code class="mono">X-XSS-Protection: 0</code></td>
                            <td class="mono">src/Security/SecurityHeaders.php</td>
                        </tr>
                        <tr>
                            <td>Rate limit</td>
                            <td>Limite general y de acceso, con Redis o archivos, y bloqueo por cuenta</td>
                            <td class="mono">src/Security/RateLimiter.php</td>
                        </tr>
                        <tr>
                            <td>Sesion</td>
                            <td>Regeneracion al entrar, tiempo muerto y duracion maxima</td>
                            <td class="mono">src/Security/SessionManager.php</td>
                        </tr>
                        <tr>
                            <td>Entrada y salida</td>
                            <td>Validacion en el servicio y escape obligatorio, comprobado en CI</td>
                            <td class="mono">src/Support/Validator.php</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Tiempo real</h2></div>
        <div class="panel__body stack">
            <p>
                El panel mantiene las metricas y la bitacora al dia con un canal de Server-Sent Events sobre
                HTTP nativo, sin librerias de cliente ni servidor de eventos aparte.
            </p>
            <p class="muted">
                La conexion se cierra a los cinco minutos y el navegador la reabre sola. La sesion se libera
                antes de entrar al bucle para que las demas peticiones del mismo usuario no esperen.
            </p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Pruebas y calidad</h2></div>
        <div class="panel__body stack">
            <pre class="code-block">composer install
composer check        # estilo + escape + analisis estatico + pruebas</pre>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Herramienta</th>
                            <th scope="col">Que comprueba</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>PHP-CS-Fixer</td>
                            <td>Norma PSR-12 y declaracion de <code class="mono">strict_types</code></td>
                        </tr>
                        <tr>
                            <td>check-escape</td>
                            <td>Que ninguna plantilla imprima datos sin pasar por <code class="mono">e()</code></td>
                        </tr>
                        <tr>
                            <td>PHPStan nivel 8</td>
                            <td>Tipos, ramas no cubiertas y errores de referencia</td>
                        </tr>
                        <tr>
                            <td>PHPUnit</td>
                            <td>Pruebas unitarias y de integracion contra MySQL</td>
                        </tr>
                        <tr>
                            <td>GitHub Actions</td>
                            <td>Ejecuta las cuatro herramientas en cada <code class="mono">push</code> y en cada PR</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="panel" id="limitaciones">
        <div class="panel__head"><h2 class="section-title">Limitaciones conocidas</h2></div>
        <div class="panel__body">
            <ul class="alert-list" style="gap:.75rem;font-size:.95rem">
                <li>
                    <strong>Los nombres de base de datos son globales en MySQL.</strong> Si dos usuarios quieren una
                    base llamada igual, el segundo alta falla: no hay nombres de nivel usuario.
                </li>
                <li>
                    <strong>La consola SQL es de solo lectura.</strong> No existe forma de ejecutar
                    <code class="mono">INSERT</code>, <code class="mono">UPDATE</code> ni
                    <code class="mono">DELETE</code> desde ahi.
                </li>
                <li>
                    <strong>No hay verificacion de correo ni restablecimiento de contrasenas.</strong> Requiere un
                    proveedor de correo que no forma parte de este proyecto.
                </li>
                <li>
                    <strong>Los avatares se guardan en disco.</strong> En un despliegue efimero se pierden al
                    redesplegar. Un almacenamiento externo es la solucion definitiva.
                </li>
                <li>
                    <strong>La base de datos de sistema debe existir.</strong> En proveedores que bloquean
                    <code class="mono">CREATE DATABASE</code> (TiDB Cloud Serverless, PlanetScale) hay que crearla a
                    mano.
                </li>
                <li>
                    <strong>Las sesiones viven en archivos.</strong> Con mas de una instancia de la aplicacion no se
                    comparten.
                </li>
                <li>
                    <strong>Cada canal en vivo ocupa un proceso de PHP.</strong> Con muchas conexiones simultaneas
                    habria que pasar a un servidor de eventos.
                </li>
                <li>
                    <strong>No hay API REST.</strong> El enrutador y los servicios estan preparados, pero no existe
                    superficie HTTP adicional.
                </li>
            </ul>
        </div>
    </section>
</div>
