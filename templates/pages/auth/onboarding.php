<?php
/**
 * Primer arranque: eleccion de tema.
 *
 * @var string $pageTitle
 * @var array<string, mixed>|null $currentUser
 * @var list<array{type: string, message: string}> $flash
 * @var \DataForge\Security\Csrf $csrf
 * @var list<\DataForge\Support\Theme> $themes
 */

use DataForge\Support\Url;

?>
<div class="stack" style="max-width:52rem;margin-inline:auto">
    <header class="page-head" style="margin-bottom:0">
        <div>
            <h1 class="page-head__title">Configura tu espacio</h1>
            <p class="page-head__lead">
                Elige la paleta con la que quieres trabajar. Podras cambiarla en cualquier momento desde tu perfil.
            </p>
        </div>
    </header>

    <section class="panel">
        <div class="panel__body">
            <form class="form" method="post" action="<?= Url::onboarding() ?>">
                <?= $csrf->field('/onboarding') ?>

                <fieldset style="border:0;padding:0">
                    <legend class="field__label" style="margin-bottom:.75rem">Paleta</legend>
                    <div class="swatch-grid">
                        <?php foreach ($themes as $theme): ?>
                            <label class="swatch" data-theme="<?= e($theme->value) ?>">
                                <input class="swatch__input" type="radio" name="theme" value="<?= e($theme->value) ?>"
                                       <?= $theme->value === 'neutral' ? 'checked' : '' ?>>
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

                <div class="btn-group">
                    <button type="submit" class="btn btn--primary">Guardar y continuar</button>
                    <a class="btn btn--secondary" href="<?= Url::dashboard() ?>">Mas tarde</a>
                </div>
            </form>
        </div>
    </section>
</div>
