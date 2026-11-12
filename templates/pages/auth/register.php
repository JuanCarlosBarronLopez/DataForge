<?php
/**
 * Formulario de registro.
 *
 * @var string          $pageTitle
 * @var string          $email
 * @var string          $username
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
            <h1 class="page-head__title" style="font-size:1.5rem">Crear cuenta</h1>
            <p class="muted small" style="margin-bottom:1.25rem">
                Cada cuenta solo ve las bases de datos que crea.
            </p>

            <?php echo $view->partial('errors', ['errors' => $errors ?? []]) ?>

            <form class="form" method="post" action="<?= Url::register() ?>">
                <?= $csrf->field('/register') ?>

                <div class="field">
                    <label class="field__label" for="username">Nombre de usuario</label>
                    <input class="input" type="text" id="username" name="username" value="<?= e($username) ?>"
                           autocomplete="username" required minlength="3" maxlength="30"
                           pattern="[A-Za-z0-9_]{3,30}" aria-describedby="username-hint">
                    <span class="field__hint" id="username-hint">
                        De 3 a 30 caracteres: letras, digitos o guion bajo.
                    </span>
                </div>

                <div class="field">
                    <label class="field__label" for="email">Correo electronico</label>
                    <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>"
                           autocomplete="email" required>
                </div>

                <div class="field">
                    <label class="field__label" for="password">Contrasena</label>
                    <input class="input" type="password" id="password" name="password"
                           autocomplete="new-password" required minlength="12"
                           aria-describedby="password-hint">
                    <span class="field__hint" id="password-hint">
                        Minimo 12 caracteres, con letras y digitos.
                    </span>
                </div>

                <div class="field">
                    <label class="field__label" for="password_confirmation">Repite la contrasena</label>
                    <input class="input" type="password" id="password_confirmation" name="password_confirmation"
                           autocomplete="new-password" required minlength="12">
                </div>

                <button type="submit" class="btn btn--primary btn--block">Crear cuenta</button>
            </form>

            <p class="small muted" style="margin-top:1.25rem">
                Ya tienes cuenta. <a href="<?= Url::login() ?>">Inicia sesion</a>.
            </p>
        </div>
    </section>
</div>
