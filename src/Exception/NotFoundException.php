<?php

declare(strict_types=1);

namespace DataForge\Exception;

/**
 * El recurso solicitado no existe o no pertenece al usuario autenticado.
 */
final class NotFoundException extends \RuntimeException {}
