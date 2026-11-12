<?php

declare(strict_types=1);

/**
 * Edicion de la estructura de una tabla.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var string $database
 * @var string $table
 * @var list<array<string, mixed>> $columns
 * @var list<\DataForge\Database\ColumnType> $types
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Column;
use DataForge\Support\Url;

$typeFromSql = static function (string $sqlType): string {
    $upper = strtoupper($sqlType);

    return match (true) {
        str_starts_with($upper, 'BIGINT')  => 'BIGINT',
        str_starts_with($upper, 'INT')     => 'INT',
        str_starts_with($upper, 'VARCHAR') => 'VARCHAR',
        str_starts_with($upper, 'TEXT')    => 'TEXT',
        str_starts_with($upper, 'DECIMAL') => 'DECIMAL',
        str_starts_with($upper, 'DOUBLE'),
        str_starts_with($upper, 'FLOAT') => 'FLOAT',
        str_starts_with($upper, 'TINYINT(1)'),
        str_starts_with($upper, 'BOOLEAN')   => 'BOOLEAN',
        str_starts_with($upper, 'DATETIME')  => 'DATETIME',
        str_starts_with($upper, 'TIMESTAMP') => 'TIMESTAMP',
        str_starts_with($upper, 'DATE')      => 'DATE',
        str_starts_with($upper, 'JSON')      => 'JSON',
        default                              => 'VARCHAR',
    };
};

$crumbs = [
    ['label' => 'Bases de datos', 'href' => Url::databases()],
    ['label' => $database, 'href' => Url::database($database)],
    ['label' => $table, 'href' => Url::table($database, $table)],
    ['label' => 'Estructura'],
];
echo $view->partial('breadcrumb', ['crumbs' => $crumbs]);
?>

<header class="page-head">
    <div>
        <h1 class="page-head__title">Estructura de <span class="mono"><?= e($table) ?></span></h1>
        <p class="page-head__lead">
            Cambia el tipo de una columna existente o anade una nueva. MySQL conserva los datos cuando el tipo es
            compatible.
        </p>
    </div>
</header>

<div class="split">
    <section class="panel">
        <div class="panel__head">
            <h2 class="section-title">Columnas existentes</h2>
        </div>
        <form method="post" action="<?= Url::tableStructureSave($database, $table) ?>">
            <?= $csrf->field('/databases/'.$database.'/tables/'.$table.'/structure') ?>
            <div class="panel__body panel__body--flush">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Longitud</th>
                                <th scope="col">Nulo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($columns as $index => $column): ?>
                                <?php $fieldName = (string) $column['Field'] ?>
                                <tr>
                                    <td>
                                        <span class="mono"><?= e($fieldName) ?></span>
                                        <input type="hidden" name="columns[<?= (int) $index ?>][name]" value="<?= e($fieldName) ?>">
                                    </td>
                                    <td>
                                        <?php $current = $typeFromSql((string) $column['Type']) ?>
                                        <select class="select" name="columns[<?= (int) $index ?>][type]"
                                                aria-label="Tipo de <?= e($fieldName) ?>">
                                            <?php foreach ($types as $type): ?>
                                                <option value="<?= e($type->value) ?>" <?= $type->value === $current ? 'selected' : '' ?>>
                                                    <?= e($type->value) ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input class="input" type="number" min="1" max="16383"
                                               value="<?= Column::length((string) $column['Type']) ?>"
                                               name="columns[<?= (int) $index ?>][length]"
                                               aria-label="Longitud de <?= e($fieldName) ?>">
                                    </td>
                                    <td>
                                        <input class="checkbox" type="checkbox" value="1"
                                               name="columns[<?= (int) $index ?>][nullable]"
                                               <?= ($column['Null'] ?? 'NO') === 'YES' ? 'checked' : '' ?>
                                               aria-label="<?= e($fieldName) ?> admite nulo">
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="panel__foot">
                <button type="submit" class="btn btn--primary">Guardar cambios</button>
            </div>
        </form>
    </section>

    <div class="stack">
        <section class="panel">
            <div class="panel__head"><h2 class="section-title">Anadir columna</h2></div>
            <div class="panel__body">
                <form class="form" method="post" action="<?= Url::tableStructureSave($database, $table) ?>">
                    <?= $csrf->field('/databases/'.$database.'/tables/'.$table.'/structure') ?>

                    <div class="field">
                        <label class="field__label" for="new-name">Nombre</label>
                        <input class="input" type="text" id="new-name" name="new[name]" required maxlength="64"
                               pattern="[A-Za-z0-9_]{1,64}">
                    </div>
                    <div class="field">
                        <label class="field__label" for="new-type">Tipo</label>
                        <select class="select" id="new-type" name="new[type]">
                            <?php foreach ($types as $type): ?>
                                <option value="<?= e($type->value) ?>"><?= e($type->value) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field__label" for="new-length">Longitud</label>
                        <input class="input" type="number" id="new-length" name="new[length]" min="1" max="16383" value="255">
                        <span class="field__hint">Solo aplica a VARCHAR.</span>
                    </div>
                    <div class="field field--inline">
                        <input class="checkbox" type="checkbox" id="new-nullable" name="new[nullable]" value="1">
                        <label class="field__label" for="new-nullable">Admite nulo</label>
                    </div>

                    <button type="submit" class="btn btn--primary btn--block">Anadir columna</button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2 class="section-title">Eliminar columna</h2></div>
            <div class="panel__body">
                <form class="form" method="post" action="<?= Url::columnDelete($database, $table) ?>"
                      data-confirm="Eliminar una columna borra tambien sus datos. Continuar?">
                    <?= $csrf->field('/databases/'.$database.'/tables/'.$table.'/columns/delete') ?>
                    <div class="field">
                        <label class="field__label" for="column">Columna</label>
                        <select class="select" id="column" name="column" required>
                            <?php foreach ($columns as $column): ?>
                                <option value="<?= e((string) $column['Field']) ?>"><?= e((string) $column['Field']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn--danger btn--block">Eliminar columna</button>
                </form>
            </div>
        </section>
    </div>
</div>
