<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Hash de contrasenas.
 *
 * Usa Argon2id cuando la extension.argon2 esta disponible (es el algoritmo
 * recomendado por OWASP) y bcrypt como alternativa compatible. Un hash creado
 * con el algoritmo anterior se recalcula en el siguiente inicio de sesion.
 */
final class PasswordHasher
{
    private const BCRYPT_OPTIONS = ['cost' => 12];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(private readonly array $options = []) {}

    public function hash(string $plainPassword): string
    {
        if ($this->supportsArgon2()) {
            return (string) password_hash($plainPassword, PASSWORD_ARGON2ID, $this->argonOptions());
        }

        return (string) password_hash($plainPassword, PASSWORD_BCRYPT, self::BCRYPT_OPTIONS);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        if ($hash === '') {
            return false;
        }

        return password_verify($plainPassword, $hash);
    }

    /**
     * Indica si el hash stored usa un algoritmo o parametros obsoletos.
     */
    public function needsRehash(string $hash): bool
    {
        if ($this->supportsArgon2() && str_starts_with($hash, '$argon2id$')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, $this->argonOptions());
        }

        return password_needs_rehash($hash, PASSWORD_BCRYPT, self::BCRYPT_OPTIONS);
    }

    public function supportsArgon2(): bool
    {
        return defined('PASSWORD_ARGON2ID');
    }

    /**
     * @return array<string, int>
     */
    private function argonOptions(): array
    {
        $defaults = [
            'memory_cost' => 65_536,
            'time_cost'   => 4,
            'threads'     => 2,
        ];

        /** @var array<string, int> $merged */
        $merged = array_merge($defaults, $this->options);

        return $merged;
    }
}
