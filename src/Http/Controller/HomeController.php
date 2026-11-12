<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Http\Controller;
use DataForge\Service\AuthService;
use DataForge\Support\Url;

/**
 * Paginas publicas: portada, documentacion y ficha del proyecto.
 */
final class HomeController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function index(): Response
    {
        if ($this->auth->isAuthenticated()) {
            return $this->redirect(Url::dashboard());
        }

        return $this->render('home/index', [
            'pageTitle' => 'DataForge',
        ]);
    }

    public function docs(): Response
    {
        return $this->render('home/docs', [
            'pageTitle' => 'Documentacion',
        ]);
    }

    public function about(): Response
    {
        return $this->render('home/about', [
            'pageTitle' => 'Sobre el proyecto',
        ]);
    }
}
