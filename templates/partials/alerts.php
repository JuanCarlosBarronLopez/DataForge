<?php
/**
 * Avisos de una sola lectura (flash).
 *
 * @var list<array{type: string, message: string}> $flash
 */

if (empty($flash)) {
    return;
}

$icons = [
    'success' => 'OK',
    'error'   => '!',
    'warning' => '!',
    'info'    => 'i',
];
?>
<div class="stack stack--tight">
    <?php foreach ($flash as $item): ?>
        <?php
        $type = in_array($item['type'], ['success', 'error', 'warning', 'info'], true) ? $item['type'] : 'info';
        $role = $type === 'error' ? 'alert' : 'status';
        ?>
        <div class="alert alert--<?= e($type) ?>" role="<?= e($role) ?>">
            <span class="alert__icon" aria-hidden="true"><?= e($icons[$type]) ?></span>
            <span class="alert__body"><?= e($item['message']) ?></span>
        </div>
    <?php endforeach ?>
</div>
