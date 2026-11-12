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
            DataForge es un ejercicio academico de la carrera de Ingenieria en TI e Innovacion Digital en la UTSJR.
        </p>
    </div>
</header>

<div class="stack">
    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Contexto</h2></div>
        <div class="panel__body stack">
            <p>
                Se planteo como una plataforma multiusuario para trabajar con bases de datos MySQL desde el navegador:
                crear esquemas, disenar tablas, operar con registros y revisar el resultado sin instalar un cliente de
                escritorio.
            </p>
            <p class="muted">
                La universidad pidio inventar una empresa como parte de la dinamica del curso. Esa empresa se llamo
                <strong>Raju Technology</strong> y es ficticia: no existe y no tiene relacion con ninguna empresa real.
            </p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Rol del equipo</h2></div>
        <div class="panel__body stack">
            <p>
                En los proyectos de equipo, mi funcion fue <strong>arquitecto de software y lider del equipo</strong>:
                disene la arquitectura, los flujos de seguridad (autenticacion, sesiones, tokens y limites de
                peticion), la planeacion general y el reparto de tareas.
            </p>
            <p class="muted">
                Tambien se implementar, pero el aporte principal de este proyecto es el diseno y la division del
                trabajo.
            </p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Equipo</h2></div>
        <div class="panel__body">
            <!-- TODO: anadir el numero y los nombres de integrantes cuando se confirmen. -->
            <p class="muted">Numero de integrantes del equipo: TODO</p>
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
                    <h3 class="feature__title">Pruebas y estaticos</h3>
                    <p class="feature__text">
                        PHPUnit para las pruebas, PHPStan para el analisis estatico y PHP-CS-Fixer para la norma de
                        estilo PSR-12.
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
