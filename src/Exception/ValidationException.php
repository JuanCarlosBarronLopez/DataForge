<?php

declare(strict_types=1);

namespace DataForge\Exception;

/**
 * Error de validacion con la lista de mensajes para mostrar al usuario.
 */
final class ValidationException extends \RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public function __construct(private readonly array $errors, string $message = '')
    {
        parent::__construct($message !== '' ? $message : ($errors[0] ?? 'Los datos enviados no son validos.'));
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
        return $this->errors[0] ?? $this->getMessage();
    }
}
