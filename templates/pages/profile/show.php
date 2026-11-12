<?php
/**
 * Perfil del usuario.
 *
 * @var string $pageTitle
 * @var array<string, mixed> $currentUser
 * @var list<\DataForge\Support\Theme> $themes
 * @var list<\DataForge\Support\ColorMode> $modes
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 */

use DataForge\Support\Url;

use function format_datetime;

?>
<header class="page-head">
    <div>
        <h1 class="page-head__title">Mi perfil</h1>
        <p class="page-head__lead">
            Datos de la cuenta, apariencia y opciones de salida.
        </p>
    </div>
</header>

<div class="split">
    <div class="stack">
        <section class="panel">
            <div class="panel__head"><h2 class="section-title">Datos de la cuenta</h2></div>
            <div class="panel__body">
                <form class="form" method="post" action="<?= Url::profile() ?>" enctype="multipart/form-data">
                    <?= $csrf->field('/profile') ?>

                    <div class="field">
                        <label class="field__label" for="username">Nombre de usuario</label>
                        <input class="input" type="text" id="username" name="username" required minlength="3"
                               maxlength="30" pattern="[A-Za-z0-9_]{3,30}"
                               value="<?= e((string) $currentUser['username']) ?>">
                    </div>

                    <div class="field">
                        <label class="field__label" for="email">Correo electronico</label>
                        <input class="input" type="email" id="email" name="email" required
                               value="<?= e((string) $currentUser['email']) ?>">
                    </div>

                    <div class="field">
                        <label class="field__label" for="profile_pic">Imagen de perfil</label>
                        <input class="input" type="file" id="profile_pic" name="profile_pic" accept="image/png,image/jpeg,image/webp">
                        <span class="field__hint">
                            PNG, JPEG o WebP. Hasta 2 MB. Se vuelve a codificar en el servidor.
                        </span>
                    </div>

                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </form>
            </div>
        </section>

        <section class="panel" id="temas">
            <div class="panel__head"><h2 class="section-title">Apariencia</h2></div>
            <div class="panel__body stack">
                <form class="form" method="post" action="<?= Url::profileTheme() ?>">
                    <?= $csrf->field('/profile/theme') ?>
                    <fieldset style="border:0;padding:0">
                        <legend class="field__label" style="margin-bottom:.75rem">Paleta</legend>
                        <div class="swatch-grid">
                            <?php foreach ($themes as $theme): ?>
                                <label class="swatch" data-theme="<?= e($theme->value) ?>">
                                    <input class="swatch__input" type="radio" name="theme" value="<?= e($theme->value) ?>"
                                        <?= ($currentUser['theme'] ?? '') === $theme->value ? 'checked' : '' ?>>
                                    <span class="swatch__chips" aria-hidden="true">
                                        <span class="swatch__chip swatch__chip--accent" style="background:var(--accent-dark)"></span>
                                        <span class="swatch__chip swatch__chip--accent" style="background:var(--accent-light)"></span>
                                    </span>
                                    <span class="swatch__name"><?= e($theme->label()) ?></span>
                                    <span class="swatch__industry"><?= e($theme->industry()) ?></span>
                                </label>
                            <?php endforeach ?>
                        </div>
                    </fieldset>
                    <button type="submit" class="btn btn--secondary">Aplicar paleta</button>
                </form>

                <form class="form" method="post" action="<?= Url::profileMode() ?>" data-mode-toggle>
                    <?= $csrf->field('/profile/mode') ?>
                    <div class="field field--inline">
                        <input class="checkbox" type="checkbox" id="mode" name="mode" value="light"
                            <?= ($currentUser['color_mode'] ?? 'dark') === 'light' ? 'checked' : '' ?>>
                        <label class="field__label" for="mode">Modo claro</label>
                    </div>
                    <button type="submit" class="btn btn--secondary">Cambiar modo</button>
                </form>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="panel">
            <div class="panel__head"><h2 class="section-title">Cuenta</h2></div>
            <div class="panel__body">
                <dl class="stack stack--tight" style="font-size:.9rem">
                    <div>
                        <dt class="muted small">Identificador</dt>
                        <dd class="mono">#<?= e((string) $currentUser['id']) ?></dd>
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

        <section class="panel">
            <div class="panel__head"><h2 class="section-title">Cambiar contrasena</h2></div>
            <div class="panel__body">
                <form class="form" method="post" action="<?= Url::profilePassword() ?>">
                    <?= $csrf->field('/profile/password') ?>
                    <div class="field">
                        <label class="field__label" for="current_password">Contrasena actual</label>
                        <input class="input" type="password" id="current_password" name="current_password"
                               autocomplete="current-password" required>
                    </div>
                    <div class="field">
                        <label class="field__label" for="new_password">Contrasena nueva</label>
                        <input class="input" type="password" id="new_password" name="password"
                               autocomplete="new-password" required minlength="12">
                        <span class="field__hint">Minimo 12 caracteres, con letras y digitos.</span>
                    </div>
                    <div class="field">
                        <label class="field__label" for="confirm_password">Repite la contrasena nueva</label>
                        <input class="input" type="password" id="confirm_password" name="password_confirmation"
                               autocomplete="new-password" required minlength="12">
                    </div>
                    <button type="submit" class="btn btn--secondary btn--block">Actualizar contrasena</button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2 class="section-title">Eliminar cuenta</h2></div>
            <div class="panel__body">
                <p class="small muted">
                    Borra tu usuario, tus bases de datos registradas y tu avatar. Las bases de datos se quedan en el
                    servidor MySQL porque la eliminacion es una operacion del motor.
                </p>
                <form method="post" action="<?= Url::profileDelete() ?>"
                      data-confirm="Eliminar la cuenta es permanente. Continuar?">
                    <?= $csrf->field('/profile/delete') ?>
                    <button type="submit" class="btn btn--danger btn--block">Eliminar mi cuenta</button>
                </form>
            </div>
        </section>
    </div>
</div>
