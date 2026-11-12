<?php
/**
 * Bitacora de auditoria.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var list<array<string, mixed>> $entries
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var list<array{type: string, message: string}> $flash
 */

use DataForge\Support\Url;

use function format_datetime;

$pageLink = static fn(int $page): string => Url::activity().($page > 1 ? '?page='.$page : '');
?>
<header class="page-head">
    <div>
        <h1 class="page-head__title">Actividad</h1>
        <p class="page-head__lead">
            Registro de las operaciones que han cambiado datos en tu espacio. <?= (int) $total ?> entrada(s).
        </p>
    </div>
</header>

<section class="panel">
    <div class="panel__body panel__body--flush">
        <?php if ($entries === []): ?>
            <div class="state" style="margin:var(--space-5)">
                <p class="state__title">Aun no hay nada registrado</p>
                <p class="state__text">
                    Cada vez que crees, modifiques o elimines una base de datos, una tabla o un registro, la accion
                    quedara anotada aqui con su fecha.
                </p>
                <a class="btn btn--primary btn--sm" href="<?= Url::databases() ?>">Ir a bases de datos</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Accion</th>
                            <th scope="col">Objetivo</th>
                            <th scope="col">Base</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td class="nowrap"><?= format_datetime((string) $entry['created_at']) ?></td>
                                <td>
                                    <?php
                                    $action = (string) $entry['action'];
                            $tone           = match ($action) {
                                'delete' => 'danger',
                                'alter'  => 'warn',
                                default  => 'accent',
                            };
                            ?>
                                    <span class="badge badge--<?= e($tone) ?>">
                                        <?= e($action) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="muted small"><?= e((string) $entry['target_type']) ?></span>
                                    <span class="mono"><?= e((string) ($entry['target_name'] ?? '')) ?></span>
                                </td>
                                <td class="mono"><?= e((string) ($entry['db_context'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Paginacion de la bitacora">
            <span>Pagina <?= (int) $page ?> de <?= (int) $pages ?></span>
            <div class="pagination__pages">
                <?php if ($page > 1): ?>
                    <a class="pagination__link" href="<?= e($pageLink($page - 1)) ?>" rel="prev">Anterior</a>
                <?php endif ?>
                <a class="pagination__link" href="<?= e($pageLink($page)) ?>" aria-current="page"><?= $page ?></a>
                <?php if ($page < $pages): ?>
                    <a class="pagination__link" href="<?= e($pageLink($page + 1)) ?>" rel="next">Siguiente</a>
                <?php endif ?>
            </div>
        </nav>
    <?php endif ?>
</section>
