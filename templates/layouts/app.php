<?php
/**
 * Plantilla principal de la aplicacion.
 *
 * @var string $content
 * @var string $pageTitle
 * @var array<string, mixed>|null $currentUser
 * @var list<array{type: string, message: string}> $flash
 */

use DataForge\Support\Theme;
use DataForge\Support\Url;

$theme     = Theme::tryFromName((string) ($currentUser['theme'] ?? 'neutral')) ?? Theme::Neutral;
$colorMode = $currentUser['color_mode']                                        ?? ($_COOKIE['df_mode'] ?? 'dark');
$colorMode = in_array($colorMode, ['dark', 'light'], true) ? $colorMode : 'dark';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$isActive = static fn(string $target): string => $path === $target ? ' aria-current="page"' : '';

$nonce = csp_nonce();
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= e($theme->value) ?>" data-mode="<?= e($colorMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <meta name="description" content="DataForge: gestor web multiusuario para bases de datos MySQL. Proyecto academico de la UTSJR.">
    <title><?= e($pageTitle === '' || $pageTitle === $appName ? $appName : $pageTitle.' · '.$appName) ?></title>
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23c87941'/><text x='16' y='21' font-family='monospace' font-size='14' font-weight='700' text-anchor='middle' fill='%23140f0b'>DF</text></svg>">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<header class="app-header">
    <div class="app-header__inner">
        <a class="brand" href="<?= Url::home() ?>">
            <span class="brand__mark" aria-hidden="true">DF</span>
            <span>
                <span class="brand__name">DataForge</span>
                <span class="brand__tag">Gestor de bases de datos MySQL</span>
            </span>
        </a>

        <button type="button" class="nav-toggle" id="navToggle" aria-label="Abrir menu de navegacion"
                aria-expanded="false" aria-controls="appNav">
            <span class="nav-toggle__bars" aria-hidden="true"></span>
        </button>

        <nav class="app-nav" id="appNav" aria-label="Navegacion principal">
            <ul class="app-nav__list">
                <li><a class="app-nav__link" href="<?= Url::dashboard() ?>" <?= $isActive('/dashboard') ?>>Panel</a></li>
                <li><a class="app-nav__link" href="<?= Url::databases() ?>" <?= $isActive('/databases') ?>>Bases de datos</a></li>
                <li><a class="app-nav__link" href="<?= Url::query() ?>" <?= $isActive('/query') ?>>Consola</a></li>
                <li><a class="app-nav__link" href="<?= Url::activity() ?>" <?= $isActive('/activity') ?>>Actividad</a></li>
                <li><a class="app-nav__link" href="<?= Url::docs() ?>" <?= $isActive('/docs') ?>>Documentacion</a></li>

                <?php if (!empty($currentUser)): ?>
                    <li>
                        <a class="app-nav__link" href="<?= Url::profile() ?>" <?= $isActive('/profile') ?>>
                            <?= e((string) $currentUser['username']) ?>
                        </a>
                    </li>
                    <li>
                        <form class="inline-form" method="post" action="<?= Url::logout() ?>">
                            <?= $csrf->field('/logout') ?>
                            <button type="submit" class="btn btn--ghost btn--sm">Salir</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li><a class="app-nav__link" href="<?= Url::login() ?>">Iniciar sesion</a></li>
                    <li><a class="btn btn--primary btn--sm" href="<?= Url::register() ?>">Crear cuenta</a></li>
                <?php endif ?>
            </ul>
        </nav>
    </div>
</header>

<main class="app-main" id="contenido" tabindex="-1">
    <?php require __DIR__.'/../partials/alerts.php' ?>
    <?= $content ?>
</main>

<footer class="app-footer">
    <div class="app-footer__inner">
        <p>
            OMNARA
        </p>
        <ul class="app-footer__links">
            <li><a href="<?= Url::docs() ?>">Documentacion</a></li>
            <li><a href="<?= Url::about() ?>">Sobre el proyecto</a></li>
            <li><a href="https://github.com/JuanCarlosBarronLopez/DataForge" rel="noopener">Repositorio</a></li>
        </ul>
    </div>
</footer>

<script src="/assets/js/app.js" nonce="<?= e($nonce) ?>" defer></script>
</body>
</html>
