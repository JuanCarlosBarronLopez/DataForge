<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Gestion de la sesion PHP con endurecimiento de cookies, regeneracion de
 * identificador y limites de inactividad y duracion.
 */
final class SessionManager
{
    private const COOKIE_NAME = 'DATAFORGE_SESSION';

    private bool $started = false;

    public function __construct(
        private readonly int $idleTimeout = 1800,
        private readonly int $absoluteTimeout = 28800,
        private readonly bool $forceSecureCookie = false,
    ) {}

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;

            return;
        }

        if (headers_sent()) {
            return;
        }

        session_name(self::COOKIE_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $this->forceSecureCookie || self::isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');

        session_start();
        $this->started = true;

        $this->enforceTimeouts();
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (($_SERVER['SERVER_PORT'] ?? null) === '443') {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    /**
     * Cierra la sesion si supero el limite de inactividad o de duracion total.
     */
    private function enforceTimeouts(): void
    {
        $now = time();

        $lastActivity = (int) ($_SESSION['_last_activity'] ?? 0);
        $createdAt    = (int) ($_SESSION['_created_at'] ?? 0);

        if ($createdAt === 0) {
            $_SESSION['_created_at'] = $now;
        }

        if ($lastActivity > 0 && $this->idleTimeout > 0 && ($now - $lastActivity) > $this->idleTimeout) {
            $this->destroy();

            return;
        }

        if ($createdAt > 0 && $this->absoluteTimeout > 0 && ($now - $createdAt) > $this->absoluteTimeout) {
            $this->destroy();

            return;
        }

        $_SESSION['_last_activity'] = $now;
    }

    /**
     * Regenera el identificador de sesion. Se usa tras iniciar sesion o tras
     * cambiar el nivel de privilegios para evitar fijacion de sesion.
     */
    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $params     = session_get_cookie_params();
            $cookieName = (string) session_name();

            setcookie(
                $cookieName,
                '',
                [
                    'expires'  => time() - 42_000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ],
            );

            session_destroy();
        }

        $this->started = false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function started(): bool
    {
        return $this->started;
    }
}
