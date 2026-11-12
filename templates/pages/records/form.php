<?php
/**
 * Formulario de alta y edicion de registro.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var string $database
 * @var string $table
 * @var list<array<string, mixed>> $columns
 * @var array<string, mixed>|null $record
 * @var string $action
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

$inputType = static function (string $sqlType): string {
    $upper = strtoupper($sqlType);

    return match (true) {
        str_contains($upper, 'INT'),
        str_contains($upper, 'DECIMAL'),
        str_contains($upper, 'DOUBLE'),
        str_contains($upper, 'FLOAT') => 'number',
        str_contains($upper, 'DATE')  => 'date',
        str_contains($upper, 'DATETIME'),
        str_contains($upper, 'TIMESTAMP') => 'datetime-local',
        default                           => 'text',
    };
};

$isBoolean = static fn(string $sqlType): bool => str_starts_with(strtoupper($sqlType), 'TINYINT(1)') || str_starts_with(strtoupper($sqlType), 'BOOLEAN');

$isLong = static fn(string $sqlType): bool => str_starts_with(strtoupper($sqlType), 'TEXT');

$crumbs = [
    ['label' => 'Bases de datos', 'href' => Url::databases()],
    ['label' => $database, 'href' => Url::database($database)],
    ['label' => $table, 'href' => Url::table($database, $table)],
    ['label' => $record === null ? 'Nuevo registro' : 'Editar registro'],
];
echo $view->partial('breadcrumb', ['crumbs' => $crumbs]);
?>

<header class="page-head">
    <div>
        <h1 class="page-head__title">
            <?= $record === null ? 'Nuevo registro' : 'Editar registro' ?>
            <?php if ($record !== null && isset($record['id'])): ?>
                <span class="mono muted">#<?= e((string) $record['id']) ?></span>
            <?php endif ?>
        </h1>
        <p class="page-head__lead">
            Los campos salen de la estructura de <span class="mono"><?= e($table) ?></span>. Deja vacio un campo para
            guardar <span class="mono">NULL</span>.
        </p>
    </div>
</header>

<section class="panel" style="max-width:44rem">
    <div class="panel__body">
        <form class="form" method="post" action="<?= e($action) ?>">
            <?= $csrf->field(parse_url($action, PHP_URL_PATH) ?: $action) ?>

            <?php foreach ($columns as $column): ?>
                <?php
                $name  = (string) $column['Field'];
                $type  = (string) $column['Type'];
                $value = $record[$name] ?? '';
                $id    = 'campo_'.preg_replace('/[^A-Za-z0-9_]/', '_', $name);
                ?>
                <div class="field">
                    <label class="field__label" for="<?= e($id) ?>">
                        <span class="mono"><?= e($name) ?></span>
                        <?php if (($column['Null'] ?? 'NO') === 'NO'): ?>
                            <span class="badge" aria-hidden="true" title="Campo obligatorio">requerido</span>
                        <?php endif ?>
                    </label>

                    <?php if ($isBoolean($type)): ?>
                        <input class="checkbox" type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1"
                            <?= ((string) $value === '1' || $value === true) ? 'checked' : '' ?>>
                    <?php elseif ($isLong($type)): ?>
                        <textarea class="textarea" id="<?= e($id) ?>" name="<?= e($name) ?>"
                                  rows="4"><?= e((string) $value) ?></textarea>
                    <?php else: ?>
                        <input class="input" type="<?= $inputType($type) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>"
                               value="<?= e((string) $value) ?>"
                               <?= ($column['Null'] ?? 'NO') === 'NO' ? 'required' : '' ?>>
                    <?php endif ?>

                    <span class="field__hint"><span class="mono"><?= e($type) ?></span></span>
                </div>
            <?php endforeach ?>

            <div class="btn-group">
                <button type="submit" class="btn btn--primary">Guardar</button>
                <a class="btn btn--secondary" href="<?= Url::table($database, $table) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</section>
