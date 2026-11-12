<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Database\ColumnDefinition;
use DataForge\Database\ColumnType;
use DataForge\Exception\NotFoundException;
use DataForge\Exception\ValidationException;
use DataForge\Http\Controller;
use DataForge\Repository\ActivityRepository;
use DataForge\Service\AuthService;
use DataForge\Service\DatabaseService;
use DataForge\Service\TableService;
use DataForge\Support\Url;

/**
 * Estructura de las tablas: alta, edicion y borrado.
 */
final class TableController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly DatabaseService $databases,
        private readonly TableService $tables,
        private readonly ActivityRepository $activity,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function createForm(string $database): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database)) {
            return $this->redirect(Url::databases());
        }

        return $this->render('tables/create', [
            'pageTitle'   => 'Nueva tabla',
            'currentUser' => $user,
            'database'    => $database,
            'types'       => ColumnType::cases(),
        ]);
    }

    public function create(string $database): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database)) {
            return $this->redirect(Url::databases());
        }

        $table = trim($this->request()->input('table', '') ?? '');
        $raw   = $this->request()->all()['columns'] ?? [];
        $raw   = is_array($raw) ? $raw : [];

        $columns = [];
        foreach ($raw as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $column = ColumnDefinition::fromInput($definition);
            if ($column !== null) {
                $columns[] = $column;
            }
        }

        try {
            $this->tables->create($database, $table, $columns);
            $this->activity->record((int) $user['id'], 'create', 'tabla', $table, $database);
            $this->flash->success('Tabla creada: '.$table);
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());

            return $this->redirect(Url::tableCreate($database));
        }

        return $this->redirect(Url::table($database, $table));
    }

    public function structure(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        return $this->render('tables/structure', [
            'pageTitle'   => 'Estructura de '.$table,
            'currentUser' => $user,
            'database'    => $database,
            'table'       => $table,
            'columns'     => $this->tables->describe($database, $table),
            'types'       => ColumnType::cases(),
        ]);
    }

    public function saveStructure(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        $raw = $this->request()->all()['columns'] ?? [];
        $raw = is_array($raw) ? $raw : [];

        $extra = $this->request()->all()['new'] ?? [];
        if (is_array($extra) && trim((string) ($extra['name'] ?? '')) !== '') {
            $raw[] = [
                'name'     => $extra['name']     ?? '',
                'type'     => $extra['type']     ?? 'VARCHAR',
                'length'   => $extra['length']   ?? 255,
                'nullable' => $extra['nullable'] ?? false,
            ];
        }

        $columns = [];
        foreach ($raw as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $column = ColumnDefinition::fromInput($definition);
            if ($column !== null) {
                $columns[] = $column;
            }
        }

        try {
            $this->tables->alter($database, $table, $columns);
            $this->activity->record((int) $user['id'], 'alter', 'tabla', $table, $database);
            $this->flash->success('Estructura actualizada.');
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::tableStructure($database, $table));
    }

    public function destroy(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database)) {
            return $this->redirect(Url::databases());
        }

        try {
            $this->tables->drop($database, $table);
            $this->activity->record((int) $user['id'], 'delete', 'tabla', $table, $database);
            $this->flash->success('Tabla eliminada: '.$table);
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::database($database));
    }

    public function destroyColumn(string $database, string $table): Response
    {
        $user = $this->auth->requireUser();

        if (!$this->ownsDatabase($user, $database) || !$this->tables->exists($database, $table)) {
            $this->flash->error('La tabla solicitada no existe.');

            return $this->redirect(Url::database($database));
        }

        $column = trim($this->request()->input('column', '') ?? '');

        try {
            $this->tables->dropColumn($database, $table, $column);
            $this->activity->record((int) $user['id'], 'alter', 'columna', $column, $database);
            $this->flash->success('Columna eliminada: '.$column);
        } catch (ValidationException $exception) {
            $this->flash->error($exception->firstError());
        }

        return $this->redirect(Url::tableStructure($database, $table));
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
