<?php
/**
 * Sobre el proyecto.
 *
 * @var string $pageTitle
 */

use DataForge\Support\Url;

?>
<header class="page-head">
    <div>
        <h1 class="page-head__title">Sobre el proyecto</h1>
        <p class="page-head__lead">
            DataForge nació como un proyecto académico de la UTSJR y hoy lo mantengo yo en solitario.
        </p>
    </div>
</header>

<div class="stack">
    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Origen</h2></div>
        <div class="panel__body stack">
            <p>
                Se planteo como una plataforma multiusuario para trabajar con bases de datos MySQL desde el
                navegador: crear esquemas, disenar tablas, operar con registros y revisar el resultado sin
                instalar un cliente de escritorio.
            </p>
            <p>
                La version actual es una reescritura completa del nucleo. La anterior era un conjunto de scripts
                PHP con logica duplicada; esta es una aplicacion por capas con front controller, cadena de
                middleware y repositorios sobre PDO.
            </p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Autoria</h2></div>
        <div class="panel__body stack">
            <p>
                <strong>OMNARA</strong> es la organizacion que fundo y que mantiene este proyecto.
            </p>
            <p>
                Yo soy el creador y el desarrollador: la arquitectura, los flujos de autenticacion y de tiempo
                real, la planeacion y el reparto de tareas del equipo original, y despues la reescritura
            </p>
            <p class="muted">
                <!-- TODO: numero de integrantes -->
            </p>
            <p class="muted">
                El equipo de mantenimiento actual es una persona, con el papel de lider tecnico.
            </p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Tecnologias usadas</h2></div>
        <div class="panel__body">
            <div class="feature-list">
                <article class="feature">
                    <h3 class="feature__title">PHP 8.4 y MySQL 8.4</h3>
                    <p class="feature__text">
                        PHP nativo, sin framework, con PDO y consultas parametrizadas. MySQL 8.4 es la version de
                        soporte prolongado.
                    </p>
                </article>
                <article class="feature">
                    <h3 class="feature__title">Contenedores</h3>
                    <p class="feature__text">
                        Docker Compose levanta la aplicacion, MySQL, phpMyAdmin y Redis en un comando.
                    </p>
                </article>
                <article class="feature">
                    <h3 class="feature__title">Calidad automatica</h3>
                    <p class="feature__text">
                        PHPUnit para las pruebas, PHPStan para el analisis estatico, PHP-CS-Fixer para la norma de
                        estilo y una comprobacion propia del escape de la salida.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Codigo y licencia</h2></div>
        <div class="panel__body stack">
            <p>
                El codigo esta bajo licencia MIT. La arquitectura, las decisiones de seguridad y las limitaciones
                estan documentadas en la <a href="<?= Url::docs() ?>">documentacion</a>.
            </p>
            <p>
                Repositorio: <a href="https://github.com/JuanCarlosBarronLopez/DataForge" rel="noopener">JuanCarlosBarronLopez/DataForge</a>
            </p>
        </div>
    </section>
</div>
