<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Exception\NotFoundException;
use DataForge\Exception\ValidationException;
use DataForge\Http\Controller;
use DataForge\Repository\ActivityRepository;
use DataForge\Service\AuthService;
use DataForge\Service\DatabaseService;
use DataForge\Service\ExportService;
use DataForge\Service\RecordService;
use DataForge\Service\TableService;
use DataForge\Support\Url;

/**
 * Registros de una tabla: listado, alta, edicion, borrado y exportacion.
 */
final class RecordController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly DatabaseService $databases,
        private readonly TableService $tables,
        private readonly RecordService $records,
        private readonly ExportService $exporter,
        private readonly ActivityRepository $activity,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function index(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        $page   = max(1, (int) ($this->request()->query('page', '1') ?? '1'));
        $search = trim($this->request()->query('q', '') ?? '');
        $sort   = trim($this->request()->query('sort', '') ?? '');
        $dir    = strtolower(trim($this->request()->query('dir', 'asc') ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $result = $this->records->paginate(
            $database,
            $table,
            self::PER_PAGE,
            ($page - 1) * self::PER_PAGE,
            $sort,
            $dir,
            $search,
        );

        return $this->render('records/index', [
            'pageTitle'   => 'Registros de '.$table,
            'currentUser' => $user,
            'database'    => $database,
            'table'       => $table,
            'columns'     => $this->tables->describe($database, $table),
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'primaryKey'  => $result['primaryKey'],
            'page'        => $page,
            'perPage'     => self::PER_PAGE,
            'pages'       => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'search'      => $search,
            'sort'        => $sort,
            'direction'   => $dir,
        ]);
    }

    public function createForm(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        return $this->render('records/form', [
            'pageTitle'   => 'Nuevo registro',
            'currentUser' => $user,
            'database'    => $database,
            'table'       => $table,
            'columns'     => $this->editableColumns($database, $table),
            'record'      => null,
            'action'      => Url::recordCreate($database, $table),
        ]);
    }

    public function create(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        try {
            $this->records->create($database, $table, $this->stringFields($this->request()));
            $this->activity->record((int) $user['id'], 'insert', 'registro', $table, $database);
            $this->flash->success('Registro creado.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());

            return $this->redirect(Url::recordCreate($database, $table));
        }

        return $this->redirect(Url::table($database, $table));
    }

    public function edit(string $database, string $table, int $id): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        $record = $this->records->find($database, $table, $id);

        if ($record === null) {
            $this->flash->error('El registro solicitado no existe.');

            return $this->redirect(Url::table($database, $table));
        }

        return $this->render('records/form', [
            'pageTitle'   => 'Editar registro',
            'currentUser' => $user,
            'database'    => $database,
            'table'       => $table,
            'columns'     => $this->editableColumns($database, $table),
            'record'      => $record,
            'action'      => Url::recordUpdate($database, $table, $id),
        ]);
    }

    public function update(string $database, string $table, int $id): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        try {
            $this->records->update($database, $table, $id, $this->stringFields($this->request()));
            $this->activity->record((int) $user['id'], 'update', 'registro', $table, $database);
            $this->flash->success('Registro actualizado.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());

            return $this->redirect(Url::record($database, $table, $id));
        }

        return $this->redirect(Url::table($database, $table));
    }

    public function destroy(string $database, string $table, int $id): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        try {
            $this->records->delete($database, $table, $id);
            $this->activity->record((int) $user['id'], 'delete', 'registro', $table, $database);
            $this->flash->success('Registro eliminado.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::table($database, $table));
    }

    public function export(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        $csv = $this->exporter->tableToCsv($database, $table);

        return Response::download($csv, $table.'.csv', 'text/csv; charset=UTF-8');
    }

    /**
     * Columnas editables: todas menos la clave primaria.
     *
     * @return list<array<string, mixed>>
     */
    private function editableColumns(string $database, string $table): array
    {
        $primary = $this->tables->primaryKey($database, $table);

        return array_values(array_filter(
            $this->tables->describe($database, $table),
            static fn(array $column): bool => (string) $column['Field'] !== $primary,
        ));
    }

    /**
     * @param array<string, mixed> $user
     */
    private function ownsDatabase(array $user, string $database): bool
    {
        try {
            $this->databases->assertOwnership((int) $user['id'], $database);
        } catch (NotFoundException) {
            return false;
        }

        return true;
    }
}
