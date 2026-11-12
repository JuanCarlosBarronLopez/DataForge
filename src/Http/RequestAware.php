<?php

declare(strict_types=1);

namespace DataForge\Http;

use DataForge\Core\Request;

/**
 * Marca los controladores que necesitan la peticion en curso.
 */
interface RequestAware
{
    public function setRequest(Request $request): void;
}
