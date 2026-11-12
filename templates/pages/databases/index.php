<?php
/**
 * Listado de bases de datos del usuario.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var list<string> $databases
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

?>
<header class="page-head">
    <div>
        <h1 class="page-head__title">Bases de datos</h1>
        <p class="page-head__lead">
            Cada base de datos queda registrada a tu nombre. No puedes ver las de otros usuarios.
        </p>
    </div>
</header>

<div class="split">
    <section class="panel">
        <div class="panel__head">
            <h2 class="section-title">Tus bases de datos</h2>
        </div>
        <div class="panel__body panel__body--flush">
            <?php if ($databases === []): ?>
                <div class="state" style="margin:var(--space-5)">
                    <p class="state__title">Sin bases de datos</p>
                    <p class="state__text">
                        Crea la primera con el formulario de la derecha. El nombre se valida antes de enviarlo al
                        servidor MySQL.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col"><span class="visually-hidden">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($databases as $database): ?>
                                <tr>
                                    <td class="mono"><?= e($database) ?></td>
                                    <td class="td--actions">
                                        <a class="btn btn--ghost btn--sm" href="<?= Url::database($database) ?>">Abrir</a>
                                        <form class="inline-form" method="post" action="<?= Url::databaseDelete($database) ?>"
                                              data-confirm="Eliminar la base de datos <?= e($database) ?> y todo su contenido. Esta accion no se puede deshacer.">
                                            <?= $csrf->field('/databases/'.$database.'/delete') ?>
                                            <button type="submit" class="btn btn--danger btn--sm">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head">
            <h2 class="section-title">Nueva base de datos</h2>
        </div>
        <div class="panel__body">
            <form class="form" method="post" action="<?= Url::databaseCreate() ?>">
                <?= $csrf->field('/databases') ?>

                <div class="field">
                    <label class="field__label" for="name">Nombre</label>
                    <input class="input" type="text" id="name" name="name" required maxlength="64"
                           pattern="[A-Za-z0-9_]{1,64}" placeholder="inventario_2026"
                           aria-describedby="name-hint">
                    <span class="field__hint" id="name-hint">
                        Letras, digitos y guion bajo. Hasta 64 caracteres.
                    </span>
                </div>

                <button type="submit" class="btn btn--primary btn--block">Crear</button>
            </form>

            <p class="small muted" style="margin-top:1rem">
                Los nombres de base de datos son globales en el servidor MySQL: si alguien mas ya creo una con el
                mismo nombre, el alta falla.
            </p>
        </div>
    </section>
</div>
