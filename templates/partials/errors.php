<?php
/**
 * Lista de vistos buenos de validacion.
 *
 * @var list<string> $errors
 */

if (empty($errors)) {
    return;
}
?>
<div class="alert alert--error" role="alert">
    <span class="alert__icon" aria-hidden="true">!</span>
    <div class="alert__body">
        Revisa los datos enviados:
        <ul class="alert-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
</div>
