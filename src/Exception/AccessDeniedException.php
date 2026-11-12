<?php

declare(strict_types=1);

namespace DataForge\Exception;

/**
 * El usuario autenticado no tiene permiso para la operacion solicitada.
 */
final class AccessDeniedException extends \RuntimeException {}
