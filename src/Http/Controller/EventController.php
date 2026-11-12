<?php

declare(strict_types=1);

namespace DataForge\Http\Controller;

use DataForge\Core\Response;
use DataForge\Http\Controller;
use DataForge\Service\AuthService;
use DataForge\Service\LiveFeed;
use DataForge\Support\Logger;

/**
 * Canal de eventos en vivo (Server-Sent Events).
 *
 * Emite el estado inicial del espacio de trabajo y despues las entradas
 * nuevas de la bitacora. La conexion se cierra a los cinco minutos y el
 * navegador la reabre sola.
 */
final class EventController extends Controller
{
    private const DURACION_SEGUNDOS = 300;

    private const INTERVALO_SEGUNDOS = 2;

    private const LATIDO_SEGUNDOS = 15;

    public function __construct(
        \DataForge\Core\View $view,
        \DataForge\Support\Flash $flash,
        \DataForge\Security\Csrf $csrf,
        private readonly AuthService $auth,
        private readonly LiveFeed $feed,
        private readonly Logger $logger,
    ) {
        parent::__construct($view, $flash, $csrf);
    }

    public function stream(): Response
    {
        $user   = $this->auth->requireUser();
        $userId = (int) $user['id'];

        return Response::stream(function () use ($userId): void {
            $this->openStream();

            $lastId   = 0;
            $deadline = time() + self::DURACION_SEGUNDOS;
            $lastBeat = time();

            while (time() < $deadline) {
                if ($lastId === 0) {
                    $snapshot = $this->feed->snapshot($userId);
                    $lastId   = (int) $snapshot['lastId'];
                    $this->emit('snapshot', $snapshot);
                } else {
                    $update = $this->feed->since($userId, $lastId);
                    if ($update['entries'] !== []) {
                        $lastId = (int) $update['lastId'];
                        $this->emit('activity', $update);
                    }
                }

                if (time() - $lastBeat >= self::LATIDO_SEGUNDOS) {
                    $this->emit('ping', ['t' => time()]);
                    $lastBeat = time();
                }

                usleep(self::INTERVALO_SEGUNDOS * 1_000_000);
            }

            $this->emit('close', ['reason' => 'La conexion se cierra cada cinco minutos.']);
            $this->closeStream();
        });
    }

    /**
     * Prepara la respuesta para streaming: sin buffering y con la sesion
     * liberada para que otras peticiones del mismo usuario no se bloqueen.
     */
    private function openStream(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        ignore_user_abort(false);
        set_time_limit(self::DURACION_SEGUNDOS + 10);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function emit(string $event, array $payload): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo 'event: '.$event."\n";
        echo 'data: '.($json === false ? '{}' : $json)."\n\n";

        $this->flush();
    }

    private function flush(): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_flush();
        }

        @flush();
    }

    private function closeStream(): void
    {
        $this->logger->debug('Canal de eventos cerrado', ['motivo' => 'duracion maxima']);
    }
}
