<?php
/**
 * Consola SQL de solo lectura.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var list<string> $databases
 * @var string $selected
 * @var string $sql
 * @var list<array<string, mixed>> $history
 * @var array{columns: list<string>, rows: list<array<string, mixed>>, durationMs: int, truncated: bool, statement: string}|null $result
 * @var string|null $error
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

?>
<header class="page-head">
    <div>
        <h1 class="page-head__title">Consola SQL</h1>
        <p class="page-head__lead">
            Ejecuta una consulta de lectura sobre una de tus bases de datos. No admite escrituras.
        </p>
    </div>
</header>

<div class="split">
    <div class="stack">
        <section class="panel">
            <div class="panel__body">
                <form class="form" method="post" action="<?= Url::query() ?>">
                    <?= $csrf->field('/query') ?>

                    <div class="field">
                        <label class="field__label" for="database">Base de datos</label>
                        <select class="select" id="database" name="database" required>
                            <option value="">Elige una base</option>
                            <?php foreach ($databases as $database): ?>
                                <option value="<?= e($database) ?>" <?= $selected === $database ? 'selected' : '' ?>>
                                    <?= e($database) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="field">
                        <label class="field__label" for="sql">Consulta</label>
                        <textarea class="textarea" id="sql" name="sql" rows="6" spellcheck="false"
                                  placeholder="SELECT * FROM clientes"><?= e($sql) ?></textarea>
                        <span class="field__hint">
                            Un enunciado por consulta. Se anade un limite automatico si la consulta no lo trae.
                        </span>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn--primary">Ejecutar</button>
                    </div>
                </form>
            </div>
        </section>

        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert">
                <span class="alert__icon" aria-hidden="true">!</span>
                <span class="alert__body"><?= e($error) ?></span>
            </div>
        <?php endif ?>

        <?php if ($result !== null): ?>
            <section class="panel">
                <div class="panel__head">
                    <h2 class="section-title">Resultado</h2>
                    <span class="badge"><?= count($result['rows']) ?> fila(s) · <?= $result['durationMs'] ?> ms</span>
                </div>
                <div class="panel__body panel__body--flush">
                    <?php if ($result['rows'] === []): ?>
                        <div class="state" style="margin:var(--space-5)">
                            <p class="state__title">La consulta no devolvio filas</p>
                            <p class="state__text">
                                El enunciado se ejecuto sin errores. Revisa la condicion si esperabas resultados.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php if ($result['truncated']): ?>
                            <div class="alert alert--info" role="status">
                                <span class="alert__icon" aria-hidden="true">i</span>
                                <span class="alert__body">El resultado se corto en el limite de filas configurado.</span>
                            </div>
                        <?php endif ?>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <?php foreach ($result['columns'] as $column): ?>
                                            <th scope="col"><span class="mono"><?= e($column) ?></span></th>
                                        <?php endforeach ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result['rows'] as $row): ?>
                                        <tr>
                                            <?php foreach ($result['columns'] as $column): ?>
                                                <?php $value = $row[$column] ?? null ?>
                                                <td>
                                                    <?php if ($value === null): ?>
                                                        <span class="muted">NULL</span>
                                                    <?php else: ?>
                                                        <?= e((string) $value) ?>
                                                    <?php endif ?>
                                                </td>
                                            <?php endforeach ?>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </section>
        <?php endif ?>
    </div>

    <section class="panel">
        <div class="panel__head"><h2 class="section-title">Consultas recientes</h2></div>
        <div class="panel__body panel__body--flush">
            <?php if ($history === []): ?>
                <div class="state" style="margin:var(--space-5)">
                    <p class="state__title">Sin consultas registradas</p>
                    <p class="state__text">
                        Las consultas que ejecutes quedan aqui para poder volver a usarlas.
                    </p>
                </div>
            <?php else: ?>
                <ul class="feed">
                    <?php foreach ($history as $entry): ?>
                        <li class="feed__item">
                            <span class="feed__mark" aria-hidden="true"></span>
                            <span>
                                <a href="<?= e(Url::query().'?database='.rawurlencode((string) $selected).'&sql='.rawurlencode((string) $entry['statement'])) ?>"
                                   class="mono small" style="word-break:break-all">
                                    <?= e(\DataForge\Support\Str::limit((string) $entry['statement'], 90)) ?>
                                </a>
                                <span class="feed__meta">
                                    <?= (int) $entry['row_count'] ?> fila(s) · <?= (int) $entry['duration_ms'] ?> ms
                                </span>
                            </span>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>
    </section>
</div>
