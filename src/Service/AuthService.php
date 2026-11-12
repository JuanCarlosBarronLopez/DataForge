<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Exception\ValidationException;
use DataForge\Repository\ActivityRepository;
use DataForge\Repository\LoginAttemptRepository;
use DataForge\Repository\UserRepository;
use DataForge\Security\PasswordHasher;
use DataForge\Security\SessionManager;
use DataForge\Support\ColorMode;
use DataForge\Support\Logger;
use DataForge\Support\Theme;
use DataForge\Support\Validator;

/**
 * Reglas de autenticacion: registro, inicio de sesion, perfil y baja de cuenta.
 */
final class AuthService
{
    public const SESSION_USER_KEY = 'auth_user';

    private const LOCKOUT_THRESHOLD = 10;
    private const LOCKOUT_WINDOW    = 900;

    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginAttemptRepository $attempts,
        private readonly ActivityRepository $activity,
        private readonly PasswordHasher $hasher,
        private readonly SessionManager $session,
        private readonly AvatarService $avatars,
        private readonly Logger $logger,
    ) {}

    /**
     * Registra una cuenta nueva y devuelve su identificador.
     *
     * @throws ValidationException
     */
    public function register(string $username, string $email, string $password, string $passwordConfirmation): int
    {
        $validator = (new Validator())
            ->username($username)
            ->email($email)
            ->password($password, $passwordConfirmation);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $this->users->create(
            $username,
            $email,
            $this->hasher->hash($password),
        );
    }

    /**
     * Inicia sesion. Devuelve el usuario autenticado.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, string $ip): array
    {
        $email = mb_strtolower(trim($email));

        if ($email === '' || $password === '') {
            throw new ValidationException(['El correo electronico y la contrasena son obligatorios.']);
        }

        $identifier = $email;
        $recent     = $this->attempts->failedAttemptsSince($identifier, time() - self::LOCKOUT_WINDOW);

        if ($recent >= self::LOCKOUT_THRESHOLD) {
            $this->logger->warning('Bloqueo de inicio de sesion por intentos fallidos', [
                'identifier' => $identifier,
                'attempts'   => $recent,
            ]);

            throw new ValidationException([
                'Demasiados intentos fallidos. Espera unos minutos antes de volver a intentarlo.',
            ]);
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !$this->hasher->verify($password, (string) $user['password_hash'])) {
            $this->attempts->record($identifier, $ip, false);

            throw new ValidationException(['El correo electronico o la contrasena no son correctos.']);
        }

        $this->attempts->record($identifier, $ip, true);
        $this->users->touchLastLogin((int) $user['id']);
        $user['last_login'] = date('Y-m-d H:i:s');

        if ($this->hasher->needsRehash((string) $user['password_hash'])) {
            $this->users->updatePasswordHash((int) $user['id'], $this->hasher->hash($password));
        }

        $this->session->regenerate();
        $this->session->set(self::SESSION_USER_KEY, $this->publicUser($user));

        $this->activity->record(
            (int) $user['id'],
            'login',
            'sesion',
            (string) $user['username'],
        );

        return $this->publicUser($user);
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        $user = $this->session->get(self::SESSION_USER_KEY);

        return is_array($user) ? $user : null;
    }

    public function isAuthenticated(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function requireUser(): array
    {
        $user = $this->user();

        if ($user === null) {
            throw new \RuntimeException('Se requiere una sesion iniciada.');
        }

        return $user;
    }

    /**
     * Actualiza los datos del perfil. El avatar es opcional.
     *
     * @param array<string, mixed>|null $upload
     *
     * @throws ValidationException
     */
    public function updateProfile(
        int $userId,
        string $username,
        string $email,
        string $colorMode,
        ?array $upload = null,
    ): void {
        $validator = (new Validator())
            ->username($username)
            ->email($email);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $mode = ColorMode::tryFromName($colorMode) ?? ColorMode::Dark;

        $current = $this->users->findById($userId);
        if ($current === null) {
            throw new ValidationException(['La cuenta ya no existe.']);
        }

        $filename = null;
        if ($upload !== null) {
            $filename = $this->avatars->store($upload);
        }

        try {
            $this->users->updateProfile($userId, $username, $email, $mode->value, $filename);
        } catch (ValidationException $exception) {
            if ($filename !== null) {
                $this->avatars->delete($filename);
            }

            throw $exception;
        }

        // El avatar antiguo se borra solo cuando el nuevo ya esta en su sitio.
        if ($filename !== null) {
            $previous = $current['profile_pic'] ?? null;
            if (is_string($previous) && $previous !== $filename) {
                $this->avatars->delete($previous);
            }
        }

        $sessionUser = $this->user();
        if ($sessionUser !== null) {
            $sessionUser['username']   = trim($username);
            $sessionUser['email']      = mb_strtolower(trim($email));
            $sessionUser['color_mode'] = $mode->value;
            if ($filename !== null) {
                $sessionUser['profile_pic'] = $filename;
            }
            $this->session->set(self::SESSION_USER_KEY, $sessionUser);
        }

        $this->activity->record($userId, 'update', 'perfil', trim($username));
    }

    /**
     * Cambia la contrasena exigiendo la contrasena actual.
     *
     * @throws ValidationException
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmation): void
    {
        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new ValidationException(['La cuenta ya no existe.']);
        }

        if (!$this->hasher->verify($currentPassword, (string) $user['password_hash'])) {
            throw new ValidationException(['La contrasena actual no es correcta.']);
        }

        $validator = (new Validator())->password($newPassword, $confirmation);
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $this->users->updatePasswordHash($userId, $this->hasher->hash($newPassword));

        $this->activity->record($userId, 'update', 'credenciales', 'contrasena');
    }

    public function changeTheme(int $userId, string $theme): void
    {
        $selected = Theme::tryFromName($theme);

        if ($selected === null) {
            throw new ValidationException(['El tema indicado no existe.']);
        }

        $this->users->updateTheme($userId, $selected->value);

        $sessionUser = $this->user();
        if ($sessionUser !== null) {
            $sessionUser['theme'] = $selected->value;
            $this->session->set(self::SESSION_USER_KEY, $sessionUser);
        }
    }

    public function changeColorMode(int $userId, string $mode): void
    {
        $selected = ColorMode::tryFromName($mode) ?? ColorMode::Dark;

        $this->users->updateColorMode($userId, $selected->value);

        $sessionUser = $this->user();
        if ($sessionUser !== null) {
            $sessionUser['color_mode'] = $selected->value;
            $this->session->set(self::SESSION_USER_KEY, $sessionUser);
        }
    }

    public function completeOnboarding(int $userId, string $theme): void
    {
        $selected = Theme::tryFromName($theme) ?? Theme::Neutral;

        $this->users->updateTheme($userId, $selected->value);
        $this->users->markOnboardingComplete($userId);

        $sessionUser = $this->user();
        if ($sessionUser !== null) {
            $sessionUser['theme']           = $selected->value;
            $sessionUser['onboarding_done'] = 1;
            $this->session->set(self::SESSION_USER_KEY, $sessionUser);
        }
    }

    public function deleteAccount(int $userId): void
    {
        $user = $this->users->findById($userId);

        if ($user !== null) {
            $profilePic = $user['profile_pic'] ?? null;
            if (is_string($profilePic)) {
                $this->avatars->delete($profilePic);
            }
        }

        $this->users->delete($userId);
        $this->session->destroy();
    }

    /**
     * Reduce el usuario a los campos que pueden guardarse en la sesion.
     *
     * @param array<string, mixed> $user
     *
     * @return array<string, mixed>
     */
    private function publicUser(array $user): array
    {
        return [
            'id'              => (int) $user['id'],
            'username'        => (string) $user['username'],
            'email'           => (string) $user['email'],
            'theme'           => (string) $user['theme'],
            'color_mode'      => (string) $user['color_mode'],
            'profile_pic'     => $user['profile_pic'] ?? null,
            'onboarding_done' => (int) ($user['onboarding_done'] ?? 0),
            'created_at'      => (string) ($user['created_at'] ?? ''),
            'last_login'      => $user['last_login'] ?? null,
        ];
    }
}
