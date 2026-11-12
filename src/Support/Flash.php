<?php

declare(strict_types=1);

namespace DataForge\Support;

use DataForge\Security\SessionManager;

/**
 * Mensajes de una sola lectura guardados en la sesion.
 */
final class Flash
{
    private const SESSION_KEY = '_flash_messages';

    public function __construct(private readonly SessionManager $session) {}

    public function success(string $message): void
    {
        $this->add('success', $message);
    }

    public function error(string $message): void
    {
        $this->add('error', $message);
    }

    public function add(string $type, string $message): void
    {
        $messages   = $this->session->get(self::SESSION_KEY, []);
        $messages   = is_array($messages) ? $messages : [];
        $messages[] = ['type' => $type, 'message' => $message];

        $this->session->set(self::SESSION_KEY, $messages);
    }

    /**
     * Devuelve los mensajes pendientes y vacia la cola.
     *
     * @return list<array{type: string, message: string}>
     */
    public function pull(): array
    {
        $messages = $this->session->get(self::SESSION_KEY, []);
        $this->session->forget(self::SESSION_KEY);

        if (!is_array($messages)) {
            return [];
        }

        /** @var list<array{type: string, message: string}> $messages */
        return $messages;
    }
}
