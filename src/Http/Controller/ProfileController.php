<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Exception\ValidationException;
use DataForge\Http\Controller;
use DataForge\Service\AuthService;
use DataForge\Support\ColorMode;
use DataForge\Support\Theme;
use DataForge\Support\Url;

/**
 * Perfil del usuario: datos de cuenta, contrasena, tema y baja.
 */
final class ProfileController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function show(): Response
    {
        $user = $this->auth->requireUser();

        return $this->render('profile/show', [
            'pageTitle'   => 'Mi perfil',
            'currentUser' => $user,
            'themes'      => Theme::all(),
            'modes'       => ColorMode::cases(),
        ]);
    }

    public function update(): Response
    {
        $user = $this->auth->requireUser();

        $username = $this->request()->input('username', '')   ?? '';
        $email    = $this->request()->input('email', '')      ?? '';
        $mode     = $this->request()->input('color_mode', '') ?? '';

        $upload = null;
        $files  = $this->request()->files();
        if (isset($files['profile_pic']) && is_array($files['profile_pic'])
                                         && (int) ($files['profile_pic']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            /** @var array<string, mixed> $upload */
            $upload = $files['profile_pic'];
        }

        try {
            $this->auth->updateProfile((int) $user['id'], $username, $email, $mode, $upload);
            $this->flash->success('Perfil actualizado.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::profile());
    }

    public function changePassword(): Response
    {
        $user = $this->auth->requireUser();

        try {
            $this->auth->changePassword(
                (int) $user['id'],
                $this->request()->input('current_password', '')      ?? '',
                $this->request()->input('password', '')              ?? '',
                $this->request()->input('password_confirmation', '') ?? '',
            );
            $this->flash->success('Contrasena actualizada.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::profile());
    }

    public function changeTheme(): Response
    {
        $user = $this->auth->requireUser();

        try {
            $this->auth->changeTheme((int) $user['id'], $this->request()->input('theme', '') ?? '');
            $this->flash->success('Tema actualizado.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::profile());
    }

    public function changeMode(): Response
    {
        $user = $this->auth->user();
        $mode = $this->request()->input('mode', '') === 'light' ? 'light' : 'dark';

        if ($user !== null) {
            try {
                $this->auth->changeColorMode((int) $user['id'], $mode);
            } catch (ValidationException) {
                // El valor ya se validado arriba; no hay nada que notificar.
            }
        }

        return $this->redirectTo($this->request()->header('Referer', Url::dashboard()) ?? Url::dashboard(), $mode);
    }

    public function destroy(): Response
    {
        $user = $this->auth->requireUser();

        $this->auth->deleteAccount((int) $user['id']);

        return $this->redirect(Url::login());
    }
}
