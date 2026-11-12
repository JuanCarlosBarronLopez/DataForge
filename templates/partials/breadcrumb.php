<?php
/**
 * Miga de pan.
 *
 * @var list<array{label: string, href?: string}> $crumbs
 */

if (empty($crumbs)) {
    return;
}
?>
<nav aria-label="Ruta de navegacion">
    <ol class="breadcrumb">
        <?php foreach ($crumbs as $crumb): ?>
            <li>
                <?php if (!empty($crumb['href'])): ?>
                    <a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= e($crumb['label']) ?></span>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ol>
</nav>
