<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Exception\ValidationException;
use DataForge\Http\Controller;
use DataForge\Service\AuthService;
use DataForge\Support\Logger;
use DataForge\Support\Theme;
use DataForge\Support\Url;

/**
 * Registro, inicio de sesion, cierre de sesion y primer arranque.
 */
final class AuthController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly Logger $logger,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function showLogin(): Response
    {
        if ($this->auth->isAuthenticated()) {
            return $this->redirect(Url::dashboard());
        }

        return $this->render('auth/login', [
            'pageTitle' => 'Iniciar sesion',
            'email'     => $this->request()->query('email', '') ?? '',
            'next'      => $this->safeNext($this->request()->query('next', '') ?? ''),
            'themes'    => Theme::all(),
        ]);
    }

    public function login(): Response
    {
        $email    = $this->request()->input('email', '')       ?? '';
        $password = $this->request()->input('password', '') ?? '';
        $next     = $this->safeNext($this->request()->input('next', '') ?? '');

        try {
            $this->auth->login($email, $password, (string) ($this->request()->server('REMOTE_ADDR', '') ?? ''));
        } catch (ValidationException $exception) {
            $this->logger->info('Intento de inicio de sesion fallido', ['email' => $email]);

            return $this->render('auth/login', [
                'pageTitle' => 'Iniciar sesion',
                'email'     => $email,
                'next'      => $next,
                'errors'    => $exception->errors(),
                'themes'    => Theme::all(),
            ], 422);
        }

        $user = $this->auth->user();
        $this->flash->success('Sesion iniciada. Bienvenido de vuelta.');

        if ($user !== null && (int) ($user['onboarding_done'] ?? 0) === 0) {
            return $this->redirect(Url::onboarding());
        }

        return $this->redirect($next !== '' ? $next : Url::dashboard());
    }

    public function showRegister(): Response
    {
        if ($this->auth->isAuthenticated()) {
            return $this->redirect(Url::dashboard());
        }

        return $this->render('auth/register', [
            'pageTitle' => 'Crear cuenta',
            'email'     => '',
            'username'  => '',
            'themes'    => Theme::all(),
        ]);
    }

    public function register(): Response
    {
        $username     = $this->request()->input('username', '')                 ?? '';
        $email        = $this->request()->input('email', '')                    ?? '';
        $password     = $this->request()->input('password', '')                 ?? '';
        $confirmation = $this->request()->input('password_confirmation', '') ?? '';

        try {
            $this->auth->register($username, $email, $password, $confirmation);
        } catch (ValidationException $exception) {
            return $this->render('auth/register', [
                'pageTitle' => 'Crear cuenta',
                'email'     => $email,
                'username'  => $username,
                'errors'    => $exception->errors(),
                'themes'    => Theme::all(),
            ], 422);
        }

        $this->flash->success('Cuenta creada. Inicia sesion para continuar.');

        return $this->redirect(Url::login());
    }

    public function logout(): Response
    {
        $this->auth->logout();
        $this->flash->success('Sesion cerrada.');

        return $this->redirect(Url::login());
    }

    public function showOnboarding(): Response
    {
        if (!$this->auth->isAuthenticated()) {
            return $this->redirect(Url::login());
        }

        $user = $this->auth->requireUser();

        if ((int) ($user['onboarding_done'] ?? 0) === 1) {
            return $this->redirect(Url::dashboard());
        }

        return $this->render('auth/onboarding', [
            'pageTitle' => 'Configura tu espacio',
            'themes'    => Theme::all(),
        ]);
    }

    public function onboarding(): Response
    {
        if (!$this->auth->isAuthenticated()) {
            return $this->redirect(Url::login());
        }

        $user  = $this->auth->requireUser();
        $theme = $this->request()->input('theme', '') ?? '';

        try {
            $this->auth->completeOnboarding((int) $user['id'], $theme);
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());

            return $this->redirect(Url::onboarding());
        }

        $this->flash->success('Listo. Tu espacio ya esta configurado.');

        return $this->redirect(Url::dashboard());
    }

    /**
     * Solo admite rutas internas para evitar redirecciones abiertas.
     */
    private function safeNext(string $candidate): string
    {
        if ($candidate === '' || !str_starts_with($candidate, '/') || str_starts_with($candidate, '//')) {
            return '';
        }

        return $candidate;
    }
}
