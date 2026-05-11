<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct(string $routeName)
    {
        parent::__construct(
            sprintf('Route [%s] not found.', $routeName)
        );
    }
}
