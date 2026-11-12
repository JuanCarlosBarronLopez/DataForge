<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Http\Controller;
use DataForge\Repository\ActivityRepository;
use DataForge\Service\AuthService;

/**
 * Bitacora de auditoria del usuario.
 */
final class ActivityController extends Controller
{
    private const PER_PAGE = 30;

    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly ActivityRepository $activity,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function index(): Response
    {
        $user   = $this->auth->requireUser();
        $userId = (int) $user['id'];

        $page  = max(1, (int) ($this->request()->query('page', '1') ?? '1'));
        $total = $this->activity->countFor($userId);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));

        return $this->render('activity/index', [
            'pageTitle'   => 'Actividad',
            'currentUser' => $user,
            'entries'     => $this->activity->paginateFor($userId, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'total'       => $total,
            'page'        => $page,
            'pages'       => $pages,
        ]);
    }
}
