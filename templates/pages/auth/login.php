<?php
/**
 * Formulario de inicio de sesion.
 *
 * @var string          $pageTitle
 * @var string          $email
 * @var string          $next
 * @var list<string>|null $errors
 * @var array<string, mixed>|null $currentUser
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

?>
<div class="auth-shell">
    <section class="panel auth-card">
        <div class="panel__body">
            <h1 class="page-head__title" style="font-size:1.5rem">Iniciar sesion</h1>
            <p class="muted small" style="margin-bottom:1.25rem">
                Entra con el correo con el que creaste tu cuenta.
            </p>

            <?php echo $view->partial('errors', ['errors' => $errors ?? []]) ?>

            <form class="form" method="post" action="<?= Url::login() ?>">
                <?= $csrf->field('/login') ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <div class="field">
                    <label class="field__label" for="email">Correo electronico</label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>"
                           autocomplete="username" required autofocus>
                </div>

                <div class="field">
                    <label class="field__label" for="password">Contrasena</label>
                    <input class="input" type="password" id="password" name="password"
                           autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn--primary btn--block">Entrar</button>
            </form>

            <p class="small muted" style="margin-top:1.25rem">
                No tienes cuenta. <a href="<?= Url::register() ?>">Crea una</a>.
            </p>
        </div>
    </section>
</div>
