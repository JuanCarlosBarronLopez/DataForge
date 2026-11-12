<?php
/**
 * Tablas de una base de datos.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var string $database
 * @var list<string> $tables
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

?>
<?php
$crumbs = [
    ['label' => 'Bases de datos', 'href' => Url::databases()],
    ['label' => $database],
];
echo $view->partial('breadcrumb', ['crumbs' => $crumbs]);
?>

<header class="page-head">
    <div>
        <h1 class="page-head__title"><span class="mono"><?= e($database) ?></span></h1>
        <p class="page-head__lead">
            <?= count($tables) === 1 ? '1 tabla' : count($tables).' tablas' ?> en esta base de datos.
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--primary" href="<?= Url::tableCreateForm($database) ?>">Nueva tabla</a>
        <a class="btn btn--secondary" href="<?= Url::databaseExport($database) ?>">Volcado .sql</a>
    </div>
</header>

<section class="panel">
    <div class="panel__body panel__body--flush">
        <?php if ($tables === []): ?>
            <div class="state" style="margin:var(--space-5)">
                <p class="state__title">Esta base de datos esta vacia</p>
                <p class="state__text">
                    Crea una tabla para empezar a guardar registros. Podras definir el nombre, el tipo y si admite
                    nulos de cada columna.
                </p>
                <a class="btn btn--primary btn--sm" href="<?= Url::tableCreateForm($database) ?>">Crear tabla</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Tabla</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td class="mono"><?= e($table) ?></td>
                                <td class="td--actions">
                                    <a class="btn btn--ghost btn--sm" href="<?= Url::table($database, $table) ?>">Registros</a>
                                    <a class="btn btn--ghost btn--sm" href="<?= Url::tableStructure($database, $table) ?>">Estructura</a>
                                    <form class="inline-form" method="post" action="<?= Url::tableDelete($database, $table) ?>"
                                          data-confirm="Eliminar la tabla <?= e($table) ?> y todos sus registros.">
                                        <?= $csrf->field('/databases/'.$database.'/tables/'.$table.'/delete') ?>
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
