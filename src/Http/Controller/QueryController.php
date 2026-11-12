<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Exception\ValidationException;
use DataForge\Http\Controller;
use DataForge\Repository\QueryHistoryRepository;
use DataForge\Service\AuthService;
use DataForge\Service\DatabaseService;
use DataForge\Service\QueryService;
use DataForge\Support\Url;

/**
 * Consola SQL de solo lectura.
 */
final class QueryController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly DatabaseService $databases,
        private readonly QueryService $queries,
        private readonly QueryHistoryRepository $history,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function index(): Response
    {
        $user   = $this->auth->requireUser();
        $userId = (int) $user['id'];

        return $this->render('query/index', [
            'pageTitle'   => 'Consola SQL',
            'currentUser' => $user,
            'databases'   => $this->databases->listFor($userId),
            'selected'    => $this->request()->query('database', '') ?? '',
            'sql'         => $this->request()->query('sql', '')      ?? '',
            'history'     => $this->history->recentFor($userId, 10),
            'result'      => null,
            'error'       => null,
        ]);
    }

    public function execute(): Response
    {
        $user   = $this->auth->requireUser();
        $userId = (int) $user['id'];

        $database = trim($this->request()->input('database', '') ?? '');
        $sql      = trim($this->request()->input('sql', '') ?? '');

        try {
            $this->databases->assertOwnership($userId, $database);
        } catch (\DataForge\Exception\NotFoundException) {
            $this->flash->error('Selecciona una base de datos que te pertenezca.');

            return $this->redirect(Url::query());
        }

        try {
            $result = $this->queries->execute($database, $sql);
            $this->history->record($userId, $sql, count($result['rows']), $result['durationMs']);
        } catch (ValidationException $exception) {
            return $this->render('query/index', [
                'pageTitle'   => 'Consola SQL',
                'currentUser' => $user,
                'databases'   => $this->databases->listFor($userId),
                'selected'    => $database,
                'sql'         => $sql,
                'history'     => $this->history->recentFor($userId, 10),
                'result'      => null,
                'error'       => $exception->firstError(),
            ], 422);
        }

        return $this->render('query/index', [
            'pageTitle'   => 'Consola SQL',
            'currentUser' => $user,
            'databases'   => $this->databases->listFor($userId),
            'selected'    => $database,
            'sql'         => $sql,
            'history'     => $this->history->recentFor($userId, 10),
            'result'      => $result,
            'error'       => null,
        ]);
    }
}
