<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Proteccion CSRF con un token por accion.
 *
 * Cada formulario recibe un token ligado a la accion que ejecuta. El token se
 * compara con hash_equals() y se invalida despues de cada intento, de modo que
 * un token capturado no puede reutilizarse. El token nunca se envia por URL.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_tokens';
    private const MAX_TOKENS  = 20;

    public function __construct(private readonly SessionManager $session) {}

    /**
     * Devuelve el token vigente para la accion indicada, creandolo si hace falta.
     */
    public function token(string $action): string
    {
        $tokens = $this->tokens();

        if (!isset($tokens[$action]['token'])) {
            $tokens[$action] = [
                'token'  => bin2hex(random_bytes(32)),
                'issued' => time(),
            ];

            $this->store($tokens);
        }

        return (string) $tokens[$action]['token'];
    }

    /**
     * Comprueba el token recibido y lo invalida, tanto si la comprobacion
     * tiene exito como si falla.
     */
    public function validate(string $action, string $token): bool
    {
        $tokens = $this->tokens();
        $stored = $tokens[$action]['token'] ?? null;

        // El token se consume siempre: un intento fallido tampoco debe dejar
        // vivo el token original.
        unset($tokens[$action]);
        $this->store($tokens);

        if (!is_string($stored) || $token === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /**
     * Campo oculto listo para insertar en un formulario.
     */
    public function field(string $action): string
    {
        return sprintf(
            '<input type="hidden" name="csrf_token" value="%s">',
            htmlspecialchars($this->token($action), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    /**
     * @return array<string, array{token: string, issued: int}>
     */
    private function tokens(): array
    {
        $tokens = $this->session->get(self::SESSION_KEY, []);
        if (!is_array($tokens)) {
            return [];
        }

        /** @var array<string, array{token: string, issued: int}> $tokens */
        return $tokens;
    }

    /**
     * @param array<string, array{token: string, issued: int}> $tokens
     */
    private function store(array $tokens): void
    {
        if (count($tokens) > self::MAX_TOKENS) {
            asort($tokens);
            $tokens = array_slice($tokens, -self::MAX_TOKENS, null, true);
        }

        $this->session->set(self::SESSION_KEY, $tokens);
    }
}
