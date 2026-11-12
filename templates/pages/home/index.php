<?php
/**
 * Portada.
 *
 * @var string $pageTitle
 */

use DataForge\Support\Theme;
use DataForge\Support\Url;

?>
<section class="hero">
    <div>
        <h1 class="hero__title">
            Gestion de bases de datos <em>MySQL</em> desde el navegador.
        </h1>
        <p class="hero__lead">
            DataForge permite crear bases de datos, disenar tablas, trabajar con registros y ejecutar consultas de
            lectura en una sola interfaz. Cada usuario solo ve las bases de datos que ha creado.
        </p>
        <div class="hero__actions">
            <a class="btn btn--primary" href="<?= Url::register() ?>">Crear cuenta</a>
            <a class="btn btn--secondary" href="<?= Url::login() ?>">Ya tengo una cuenta</a>
        </div>
        <p class="small muted" style="margin-top:1rem">
            Proyecto academico en equipo de la UTSJR. PHP 8.5, MySQL 8.4, sin frameworks.
        </p>
    </div>

    <div class="hero__aside">
        <div class="console-preview">
            <div class="console-preview__bar">
                <span class="mono">tabla: clientes</span>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Columna</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Nulo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="mono">id</td>
                            <td class="mono">INT</td>
                            <td><span class="badge">no</span></td>
                        </tr>
                        <tr>
                            <td class="mono">nombre</td>
                            <td class="mono">VARCHAR(80)</td>
                            <td><span class="badge">no</span></td>
                        </tr>
                        <tr>
                            <td class="mono">saldo</td>
                            <td class="mono">DECIMAL(12,4)</td>
                            <td><span class="badge">si</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="small muted">
            Vista de estructura: la misma tabla que genera el constructor de tablas, con orden, busqueda y paginacion
            en la vista de registros.
        </p>
    </div>
</section>

<section class="stack" style="margin-top:3rem">
    <div>
        <h2 class="section-title">Que resuelve</h2>
        <p class="muted" style="margin-top:.75rem;max-width:60ch">
            Es una herramienta para trabajar con datos de prueba y prototipos cuando no conviene instalar un cliente
            de escritorio.
        </p>
    </div>

    <div class="feature-list">
        <article class="feature">
            <h3 class="feature__title">Diseno de tablas sin escribir DDL</h3>
            <p class="feature__text">
                Un constructor visual genera la sentencia de creacion con tipos INT, BIGINT, VARCHAR, TEXT, DECIMAL,
                FLOAT, BOOLEAN, DATE, DATETIME, TIMESTAMP y JSON.
            </p>
        </article>
        <article class="feature">
            <h3 class="feature__title">CRUD con formularios generados</h3>
            <p class="feature__text">
                Los formularios de alta y edicion se construyen a partir de la estructura real de la tabla, con
                paginacion, orden y busqueda por cualquier columna.
            </p>
        </article>
        <article class="feature">
            <h3 class="feature__title">Consola SQL de solo lectura</h3>
            <p class="feature__text">
                Acepta un unico enunciado SELECT, SHOW, DESCRIBE o EXPLAIN, le aplica limite de filas y tiempo de
                ejecucion. No hay forma de ejecutar escrituras desde ahi.
            </p>
        </article>
        <article class="feature">
            <h3 class="feature__title">Aislamiento entre cuentas</h3>
            <p class="feature__text">
                Las bases de datos se registran a nombre del usuario que las creo. Quien no es propietario no puede
                listarla, abrirla ni eliminarla.
            </p>
        </article>
        <article class="feature">
            <h3 class="feature__title">Exportacion CSV y volcado SQL</h3>
            <p class="feature__text">
                Cada tabla se descarga en CSV y cada base de datos completa en un archivo .sql con estructura y
                datos.
            </p>
        </article>
        <article class="feature">
            <h3 class="feature__title">Bitacora de auditoria</h3>
            <p class="feature__text">
                Las operaciones que modifican datos quedan registradas con la accion, el objetivo y la fecha.
            </p>
        </article>
    </div>
</section>

<section class="stack" style="margin-top:3rem" aria-labelledby="temas">
    <div>
        <h2 class="section-title" id="temas">Siete paletas y dos modos de color</h2>
        <p class="muted" style="margin-top:.75rem;max-width:60ch">
            Cada paleta define un color de marca que se aplica a los botones, el foco y la navegacion activa. Los
            modos claro y oscuro comparten la misma estructura y estan contrastados por separado.
        </p>
    </div>

    <div class="swatch-grid">
        <?php foreach (Theme::all() as $theme): ?>
            <div class="swatch" data-theme="<?= e($theme->value) ?>">
                <span class="swatch__chips" aria-hidden="true">
                    <span class="swatch__chip swatch__chip--accent" style="background:var(--accent-dark)"></span>
                    <span class="swatch__chip swatch__chip--accent" style="background:var(--accent-light)"></span>
                </span>
                <span class="swatch__name"><?= e($theme->label()) ?></span>
                <span class="swatch__industry"><?= e($theme->industry()) ?></span>
            </div>
        <?php endforeach ?>
    </div>
</section>

<section class="stack" style="margin-top:3rem" aria-labelledby="seguridad">
    <div>
        <h2 class="section-title" id="seguridad">Seguridad</h2>
        <p class="muted" style="margin-top:.75rem;max-width:60ch">
            Cada afirmacion de esta lista corresponde a una pieza concreta del codigo. Las decisiones que no se han
            tomado estan listadas en
            <a href="<?= Url::docs() ?>#limitaciones">Limitaciones conocidas</a>.
        </p>
    </div>

    <ul class="feature-list" style="padding-left:0;list-style:none">
        <li class="feature">
            <h3 class="feature__title">Tokens CSRF por accion</h3>
            <p class="feature__text">
                Cada formulario lleva su propio token ligado a la ruta de destino, verificado con
                <code class="mono">hash_equals</code> y consumido tras cada intento.
            </p>
        </li>
        <li class="feature">
            <h3 class="feature__title">Prepared statements y listas blancas</h3>
            <p class="feature__text">
                Los valores se envian como parametros. Los identificadores no admiten parametros en PDO, asi que
                pasan por una lista blanca de nombres antes de interpolarlos.
            </p>
        </li>
        <li class="feature">
            <h3 class="feature__title">Hash de contrasenas con Argon2id</h3>
            <p class="feature__text">
                Se usa Argon2id cuando existe la extension y bcrypt con cost 12 como respaldo. Un hash antiguo se
                recalcula en el proximo inicio de sesion.
            </p>
        </li>
        <li class="feature">
            <h3 class="feature__title">Cabeceras con CSP por nonce</h3>
            <p class="feature__text">
                <code class="mono">script-src</code> no admite scripts en linea: cada pagina recibe un nonce por
                peticion. <code class="mono">X-XSS-Protection</code> se elimino porque esta obsoleto.
            </p>
        </li>
        <li class="feature">
            <h3 class="feature__title">Rate limiting con dos frentes</h3>
            <p class="feature__text">
                Limite general por IP y limite mas estricto en login y registro. El backend es Redis o archivos,
                y solo se confia en <code class="mono">X-Forwarded-For</code> si el remitente esta en la lista de
                proxies confiables.
            </p>
        </li>
        <li class="feature">
            <h3 class="feature__title">Sesion endurecida</h3>
            <p class="feature__text">
                Cookies <code class="mono">HttpOnly</code>, <code class="mono">Secure</code> y
                <code class="mono">SameSite=Strict</code>, regeneracion de identificador al entrar, tiempo muerto de
                30 minutos y duracion maxima de 8 horas.
            </p>
        </li>
    </ul>
</section>
