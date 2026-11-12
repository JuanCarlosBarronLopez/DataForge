<?php
/**
 * Panel principal.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var list<string> $databases
 * @var int $tableCount
 * @var int $rowCount
 * @var list<string> $unreadable
 * @var list<array<string, mixed>> $activity
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

use function format_datetime;

?>
<header class="page-head">
    <div>
        <h1 class="page-head__title">Hola, <?= e((string) $currentUser['username']) ?></h1>
        <p class="page-head__lead">
            Estas son las bases de datos que tienes registradas en este servidor.
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--primary" href="<?= Url::databases() ?>">Bases de datos</a>
        <a class="btn btn--secondary" href="<?= Url::query() ?>">Consola SQL</a>
    </div>
</header>

<div class="stack">
    <div class="metrics">
        <div class="metric">
            <div class="metric__value"><?= count($databases) ?></div>
            <div class="metric__label">Bases de datos tuyas</div>
        </div>
        <div class="metric">
            <div class="metric__value"><?= $tableCount ?></div>
            <div class="metric__label">Tablas en total</div>
        </div>
        <div class="metric">
            <div class="metric__value"><?= $rowCount ?></div>
            <div class="metric__label">Filas contadas</div>
        </div>
    </div>

    <?php if ($unreadable !== []): ?>
        <div class="alert alert--warning" role="status">
            <span class="alert__icon" aria-hidden="true">!</span>
            <span class="alert__body">
                No se pudieron leer <?= count($unreadable) ?> base(s): <?= e(implode(', ', $unreadable)) ?>.
                El servidor MySQL las reporta pero la cuenta no tiene permiso de lectura.
            </span>
        </div>
    <?php endif ?>

    <div class="split">
        <div class="stack">
            <section class="panel">
                <div class="panel__head">
                    <h2 class="section-title">Bases de datos</h2>
                    <a class="btn btn--ghost btn--sm" href="<?= Url::databases() ?>">Ver todas</a>
                </div>
                <div class="panel__body panel__body--flush">
                    <?php if ($databases === []): ?>
                        <div class="state" style="margin:var(--space-5)">
                            <p class="state__title">Aun no tienes bases de datos</p>
                            <p class="state__text">
                                Crea la primera para poder disenar tablas y trabajar con registros.
                            </p>
                            <a class="btn btn--primary btn--sm" href="<?= Url::databases() ?>">Crear base de datos</a>
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
                                    <?php foreach (array_slice($databases, 0, 6) as $database): ?>
                                        <tr>
                                            <td class="mono"><?= e($database) ?></td>
                                            <td class="td--actions">
                                                <a class="btn btn--ghost btn--sm" href="<?= Url::database($database) ?>">Abrir</a>
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
                    <h2 class="section-title">Actividad reciente</h2>
                    <a class="btn btn--ghost btn--sm" href="<?= Url::activity() ?>">Bitacora completa</a>
                </div>
                <div class="panel__body panel__body--flush">
                    <?php if ($activity === []): ?>
                        <div class="state" style="margin:var(--space-5)">
                            <p class="state__title">Sin actividad todavia</p>
                            <p class="state__text">
                                Cada operacion que cambie datos quedara registrada aqui, con la fecha y el objetivo.
                            </p>
                        </div>
                    <?php else: ?>
                        <ul class="feed">
                            <?php foreach ($activity as $entry): ?>
                                <li class="feed__item">
                                    <span class="feed__mark feed__mark--<?= e((string) $entry['action']) ?>"
                                          aria-hidden="true"></span>
                                    <span>
                                        <span class="badge"><?= e((string) $entry['action']) ?></span>
                                        <span class="mono"><?= e((string) $entry['target_name']) ?></span>
                                        <?php if (!empty($entry['db_context'])): ?>
                                            <span class="muted">en</span>
                                            <span class="mono"><?= e((string) $entry['db_context']) ?></span>
                                        <?php endif ?>
                                        <span class="feed__meta"><?= format_datetime((string) $entry['created_at']) ?></span>
                                    </span>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="panel">
                <div class="panel__head"><h2 class="section-title">Atajos</h2></div>
                <div class="panel__body">
                    <ul class="stack stack--tight" style="list-style:none;padding:0">
                        <li><a class="btn btn--secondary btn--block" href="<?= Url::databases() ?>">Nueva base de datos</a></li>
                        <li><a class="btn btn--secondary btn--block" href="<?= Url::query() ?>">Consola SQL</a></li>
                        <li><a class="btn btn--secondary btn--block" href="<?= Url::profile() ?>">Mi perfil</a></li>
                        <li><a class="btn btn--secondary btn--block" href="<?= Url::docs() ?>">Documentacion</a></li>
                    </ul>
                </div>
            </section>

            <section class="panel">
                <div class="panel__head"><h2 class="section-title">Servidor</h2></div>
                <div class="panel__body">
                    <dl class="stack stack--tight" style="font-size:.9rem">
                        <div>
                            <dt class="muted small">Tema activo</dt>
                            <dd><?= e(\DataForge\Support\Theme::tryFromName((string) $currentUser['theme'])?->label() ?? 'Grafito') ?></dd>
                        </div>
                        <div>
                            <dt class="muted small">Cuenta creada</dt>
                            <dd><?= format_datetime((string) ($currentUser['created_at'] ?? '')) ?></dd>
                        </div>
                        <div>
                            <dt class="muted small">Ultimo acceso</dt>
                            <dd><?= format_datetime($currentUser['last_login'] ?? null) ?></dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</div>
