<?php
/**
 * Registros de una tabla.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var string $database
 * @var string $table
 * @var list<string> $columns
 * @var list<array<string, mixed>> $rows
 * @var int $total
 * @var string $primaryKey
 * @var int $page
 * @var int $perPage
 * @var int $pages
 * @var string $search
 * @var string $sort
 * @var string $direction
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

$crumbs = [
    ['label' => 'Bases de datos', 'href' => Url::databases()],
    ['label' => $database, 'href' => Url::database($database)],
    ['label' => $table],
];
echo $view->partial('breadcrumb', ['crumbs' => $crumbs]);

$pageLink = static function (int $page) use ($database, $table, $search, $sort, $direction): string {
    $params = array_filter([
        'page' => $page > 1 ? $page : null,
        'q'    => $search    !== '' ? $search : null,
        'sort' => $sort      !== '' ? $sort : null,
        'dir'  => $direction !== 'asc' ? $direction : null,
    ], static fn($value) => $value !== null);

    $query = http_build_query($params);

    return Url::table($database, $table).($query === '' ? '' : '?'.$query);
};
?>

<header class="page-head">
    <div>
        <h1 class="page-head__title">Registros de <span class="mono"><?= e($table) ?></span></h1>
        <p class="page-head__lead">
            <?= e($total === 1 ? '1 registro' : $total.' registros') ?> en total.
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--primary" href="<?= Url::recordCreateForm($database, $table) ?>">Nuevo registro</a>
        <a class="btn btn--secondary" href="<?= Url::tableExport($database, $table) ?>">Exportar CSV</a>
    </div>
</header>

<div class="toolbar">
    <form class="search-form" method="get" action="<?= Url::table($database, $table) ?>" role="search">
        <label class="visually-hidden" for="q">Buscar en los registros</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Buscar en todas las columnas">
        <button type="submit" class="btn btn--secondary btn--sm">Buscar</button>
        <?php if ($search !== ''): ?>
            <a class="btn btn--ghost btn--sm" href="<?= Url::table($database, $table) ?>">Limpiar</a>
        <?php endif ?>
    </form>
    <a class="btn btn--ghost btn--sm" href="<?= Url::tableStructure($database, $table) ?>">Editar estructura</a>
</div>

<section class="panel">
    <div class="panel__body panel__body--flush">
        <?php if ($rows === []): ?>
            <div class="state" style="margin:var(--space-5)">
                <?php if ($search !== ''): ?>
                    <p class="state__title">Ningun registro coincide con la busqueda</p>
                    <p class="state__text">
                        La busqueda compara el texto contra todas las columnas. Prueba con una cadena mas corta.
                    </p>
                    <a class="btn btn--secondary btn--sm" href="<?= Url::table($database, $table) ?>">Ver todos los registros</a>
                <?php else: ?>
                    <p class="state__title">La tabla esta vacia</p>
                    <p class="state__text">
                        Anade el primer registro con el formulario generado a partir de la estructura.
                    </p>
                    <a class="btn btn--primary btn--sm" href="<?= Url::recordCreateForm($database, $table) ?>">Nuevo registro</a>
                <?php endif ?>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <?php $field = (string) $column['Field'] ?>
                                <th scope="col">
                                    <?php $nextDir = ($sort === $field && $direction === 'asc') ? 'desc' : 'asc' ?>
                                    <a class="sort-link"
                                       href="<?= e($pageLink(1).(str_contains($pageLink(1), '?') ? '&' : '?').'sort='.rawurlencode($field).'&dir='.$nextDir) ?>">
                                        <span class="mono"><?= e($field) ?></span>
                                        <?php if ($sort === $field): ?>
                                            <span class="sort-link__arrow" aria-hidden="true"><?= $direction === 'asc' ? 'asc' : 'desc' ?></span>
                                        <?php endif ?>
                                    </a>
                                </th>
                            <?php endforeach ?>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <?php $value = $row[(string) $column['Field']] ?? null ?>
                                    <td class="<?= is_numeric($value) ? 'td--num' : '' ?>">
                                        <?php if ($value === null): ?>
                                            <span class="muted">NULL</span>
                                        <?php else: ?>
                                            <?= e((string) $value) ?>
                                        <?php endif ?>
                                    </td>
                                <?php endforeach ?>
                                <td class="td--actions">
                                    <a class="btn btn--ghost btn--sm"
                                       href="<?= Url::record($database, $table, (int) ($row[$primaryKey] ?? 0)) ?>">Editar</a>
                                    <form class="inline-form" method="post"
                                          action="<?= Url::recordDelete($database, $table, (int) ($row[$primaryKey] ?? 0)) ?>"
                                          data-confirm="Eliminar este registro. Continuar?">
                                        <?= $csrf->field('/databases/'.$database.'/tables/'.$table.'/records/'.(int) ($row[$primaryKey] ?? 0).'/delete') ?>
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

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Paginacion de registros">
            <span>Pagina <?= (int) $page ?> de <?= (int) $pages ?></span>
            <div class="pagination__pages">
                <?php if ($page > 1): ?>
                    <a class="pagination__link" href="<?= e($pageLink($page - 1)) ?>" rel="prev">Anterior</a>
                <?php endif ?>
                <?php
                $start = max(1, $page - 2);
        $end           = min($pages, $page + 2);
        for ($index = $start; $index <= $end; ++$index): ?>
                    <a class="pagination__link" href="<?= e($pageLink($index)) ?>"
                       <?= $index === $page ? 'aria-current="page"' : '' ?>><?= (int) $index ?></a>
                <?php endfor ?>
                <?php if ($page < $pages): ?>
                    <a class="pagination__link" href="<?= e($pageLink($page + 1)) ?>" rel="next">Siguiente</a>
                <?php endif ?>
            </div>
        </nav>
    <?php endif ?>
</section>
