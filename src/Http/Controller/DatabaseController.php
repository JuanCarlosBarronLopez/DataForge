<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Exception\NotFoundException;
use DataForge\Exception\ValidationException;
use DataForge\Http\Controller;
use DataForge\Service\AuthService;
use DataForge\Service\DatabaseService;
use DataForge\Service\ExportService;
use DataForge\Support\Url;

/**
 * Bases de datos del usuario: listado, alta, borrado y volcado.
 */
final class DatabaseController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly DatabaseService $databases,
        private readonly ExportService $exporter,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function index(): Response
    {
        $user = $this->auth->requireUser();

        return $this->render('databases/index', [
            'pageTitle'   => 'Bases de datos',
            'currentUser' => $user,
            'databases'   => $this->databases->listFor((int) $user['id']),
        ]);
    }

    public function create(): Response
    {
        $user = $this->auth->requireUser();
        $name = trim($this->request()->input('name', '') ?? '');

        try {
            $this->databases->create((int) $user['id'], $name);
            $this->flash->success('Base de datos creada: '.$name);
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::databases());
    }

    public function show(string $database): Response
    {
        $user = $this->auth->requireUser();

        try {
            $database = $this->databases->assertOwnership((int) $user['id'], $database);
        } catch (NotFoundException) {
            $this->flash->error('La base de datos solicitada no existe o no te pertenece.');

            return $this->redirect(Url::databases());
        }

        return $this->render('databases/show', [
            'pageTitle'   => 'Base '.$database,
            'currentUser' => $user,
            'database'    => $database,
            'tables'      => $this->databases->tables($database),
        ]);
    }

    public function destroy(string $database): Response
    {
        $user = $this->auth->requireUser();

        try {
            $this->databases->drop((int) $user['id'], $database);
            $this->flash->success('Base de datos eliminada: '.$database);
        } catch (NotFoundException) {
            $this->flash->error('La base de datos solicitada no existe o no te pertenece.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::databases());
    }

    public function export(string $database): Response
    {
        $user = $this->auth->requireUser();

        try {
            $database = $this->databases->assertOwnership((int) $user['id'], $database);
        } catch (NotFoundException) {
            $this->flash->error('La base de datos solicitada no existe o no te pertenece.');

            return $this->redirect(Url::databases());
        }

        $dump = $this->exporter->databaseToSql($database);

        return Response::download($dump, $database.'.sql', 'application/sql; charset=UTF-8');
    }
}
