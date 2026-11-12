<?php
/**
 * Formulario de creacion de tabla.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var string $database
 * @var list<\DataForge\Database\ColumnType> $types
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

?>
<?php
$crumbs = [
    ['label' => 'Bases de datos', 'href' => Url::databases()],
    ['label' => $database, 'href' => Url::database($database)],
    ['label' => 'Nueva tabla'],
];
echo $view->partial('breadcrumb', ['crumbs' => $crumbs]);
?>

<header class="page-head">
    <div>
        <h1 class="page-head__title">Nueva tabla</h1>
        <p class="page-head__lead">
            La tabla se crea en <span class="mono"><?= e($database) ?></span> con una columna
            <span class="mono">id</span> autoincrementable como clave primaria.
        </p>
    </div>
</header>

<form class="stack" method="post" action="<?= Url::tableCreate($database) ?>" id="formTabla">
    <?= $csrf->field('/databases/'.$database.'/tables') ?>

    <section class="panel">
        <div class="panel__body">
            <div class="field">
                <label class="field__label" for="table">Nombre de la tabla</label>
                <input class="input" type="text" id="table" name="table" required maxlength="64"
                       pattern="[A-Za-z0-9_]{1,64}" placeholder="clientes">
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head">
            <h2 class="section-title">Columnas</h2>
            <button type="button" class="btn btn--secondary btn--sm" data-add-column>Agregar columna</button>
        </div>
        <div class="panel__body">
            <p class="small muted" style="margin-bottom:1rem">
                La longitud solo aplica a VARCHAR. Un maximo de 16383 caracteres.
            </p>
            <div class="table-wrap">
                <table class="data-table" id="columnasTabla">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Longitud</th>
                            <th scope="col">Admite nulo</th>
                            <th scope="col"><span class="visually-hidden">Quitar</span></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <p class="field__error" id="columnasAviso" hidden>
                Define al menos una columna con nombre y tipo.
            </p>
        </div>
        <div class="panel__foot">
            <button type="submit" class="btn btn--primary">Crear tabla</button>
        </div>
    </section>
</form>

<template id="plantillaColumna">
    <tr>
        <td>
            <input class="input" type="text" name="columns[0][name]" required maxlength="64"
                   pattern="[A-Za-z0-9_]{1,64}" aria-label="Nombre de columna">
        </td>
        <td>
            <select class="select" name="columns[0][type]" aria-label="Tipo de columna">
                <?php foreach ($types as $type): ?>
                    <option value="<?= e($type->value) ?>"><?= e($type->value) ?></option>
                <?php endforeach ?>
            </select>
        </td>
        <td>
            <input class="input" type="number" name="columns[0][length]" min="1" max="16383" value="255"
                   aria-label="Longitud de columna">
        </td>
        <td>
            <input class="checkbox" type="checkbox" name="columns[0][nullable]" value="1"
                   aria-label="Admite nulo">
        </td>
        <td>
            <button type="button" class="btn btn--danger btn--sm" data-remove-column aria-label="Quitar columna">Quitar</button>
        </td>
    </tr>
</template>
