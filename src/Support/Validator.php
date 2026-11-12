<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Validacion de entrada con mensajes en espanol.
 *
 * Cada metodo devuelve la lista de errores encontrados; una lista vacia
 * significa que la entrada es valida.
 */
final class Validator
{
    /** @var list<string> */
    private array $errors = [];

    public function username(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            $this->errors[] = 'El nombre de usuario es obligatorio.';

            return $this;
        }

        if (preg_match('/^[A-Za-z0-9_]{3,30}$/', $value) !== 1) {
            $this->errors[] = 'El nombre de usuario debe tener entre 3 y 30 caracteres alfanumericos o guion bajo.';
        }

        return $this;
    }

    public function email(string $value): self
    {
        if (filter_var(trim($value), FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = 'El correo electronico no tiene un formato valido.';
        }

        return $this;
    }

    public function password(string $value, ?string $confirmation = null): self
    {
        if (strlen($value) < 12) {
            $this->errors[] = 'La contrasena debe tener al menos 12 caracteres.';
        } elseif (preg_match('/[A-Za-z]/', $value) !== 1 || preg_match('/\d/', $value) !== 1) {
            $this->errors[] = 'La contrasena debe combinar letras y digitos.';
        }

        if ($confirmation !== null && !hash_equals($value, $confirmation)) {
            $this->errors[] = 'Las contrasenas no coinciden.';
        }

        return $this;
    }

    public function required(string $value, string $label): self
    {
        if (trim($value) === '') {
            $this->errors[] = 'El campo '.$label.' es obligatorio.';
        }

        return $this;
    }

    public function maxLength(string $value, int $max, string $label): self
    {
        if (mb_strlen($value) > $max) {
            $this->errors[] = 'El campo '.$label.' no puede superar los '.$max.' caracteres.';
        }

        return $this;
    }

    public function addError(string $message): self
    {
        $this->errors[] = $message;

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return $this->errors[0] ?? '';
    }
}
