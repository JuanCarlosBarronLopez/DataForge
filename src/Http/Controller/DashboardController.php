<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Http\Controller;
use DataForge\Repository\ActivityRepository;
use DataForge\Service\AuthService;
use DataForge\Service\DatabaseService;

/**
 * Panel principal con el resumen del espacio de trabajo.
 */
final class DashboardController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly DatabaseService $databases,
        private readonly ActivityRepository $activity,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function index(): Response
    {
        $user      = $this->auth->requireUser();
        $userId    = (int) $user['id'];
        $databases = $this->databases->listFor($userId);

        $tableCount = 0;
        $rowCount   = 0;
        $unreadable = [];

        foreach ($databases as $database) {
            try {
                $tableCount += $this->databases->tableCount($database);
                $rowCount   += $this->databases->totalRows($database);
            } catch (\Throwable) {
                $unreadable[] = $database;
            }
        }

        return $this->render('dashboard/index', [
            'pageTitle'   => 'Panel',
            'currentUser' => $user,
            'databases'   => $databases,
            'tableCount'  => $tableCount,
            'rowCount'    => $rowCount,
            'unreadable'  => $unreadable,
            'activity'    => $this->activity->recentFor($userId, 8),
        ]);
    }
}
