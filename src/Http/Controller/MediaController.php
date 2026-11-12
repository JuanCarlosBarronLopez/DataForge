<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Http\Controller;
use DataForge\Repository\UserRepository;
use DataForge\Service\AuthService;
use DataForge\Service\AvatarService;

/**
 * Entrega de archivos de usuario (avatares).
 *
 * Los avatares se guardan fuera del directorio publico y solo se sirven al
 * propio propietario.
 */
final class MediaController extends Controller
{
    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly UserRepository $users,
        private readonly AvatarService $avatars,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function avatar(string $filename): Response
    {
        $user = $this->auth->requireUser();

        $record = $this->users->findById((int) $user['id']);
        $stored = is_array($record) ? ($record['profile_pic'] ?? null) : null;

        if (!is_string($stored) || !hash_equals($stored, $filename) || !$this->avatars->exists($filename)) {
            return Response::text('No encontrado', 404);
        }

        $contents = @file_get_contents($this->avatars->path($filename));
        if ($contents === false) {
            return Response::text('No encontrado', 404);
        }

        $type = match (pathinfo($filename, PATHINFO_EXTENSION)) {
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'webp'  => 'image/webp',
            default => 'application/octet-stream',
        };

        return Response::binary($contents, $type, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
